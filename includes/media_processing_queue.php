<?php

declare(strict_types=1);

function sf_mp_binary_path(string $kind): string {
    $map = [
        'ffmpeg' => ['SF_FFMPEG_PATH', '/usr/bin/ffmpeg'],
        'ffprobe' => ['SF_FFPROBE_PATH', '/usr/bin/ffprobe'],
        'audiowaveform' => ['SF_AUDIOWAVEFORM_PATH', '/usr/bin/audiowaveform'],
    ];
    if (!isset($map[$kind])) return '';
    [$env, $fallback] = $map[$kind];
    $path = sf_mp_env($env, $fallback);
    return is_file($path) && is_executable($path) ? $path : '';
}

function sf_mp_binary_status(): array {
    return [
        'ffmpeg' => sf_mp_binary_path('ffmpeg') !== '',
        'ffprobe' => sf_mp_binary_path('ffprobe') !== '',
        'audiowaveform' => sf_mp_binary_path('audiowaveform') !== '',
    ];
}

function sf_mp_job_catalog(): array {
    return [
        'probe' => ['requires'=>'ffprobe','output_role'=>null],
        'audio_preview' => ['requires'=>'ffmpeg','output_role'=>'preview'],
        'audio_stream' => ['requires'=>'ffmpeg','output_role'=>'stream'],
        'audio_waveform' => ['requires'=>'ffmpeg','output_role'=>'waveform'],
        'video_hls' => ['requires'=>'ffmpeg','output_role'=>'manifest'],
        'video_preview' => ['requires'=>'ffmpeg','output_role'=>'preview'],
        'video_poster' => ['requires'=>'ffmpeg','output_role'=>'poster'],
        'integrity_check' => ['requires'=>null,'output_role'=>null],
        'storage_copy' => ['requires'=>null,'output_role'=>null],
    ];
}

function sf_mp_enqueue_job(int $objectId, string $jobType, int $priority = 100, int $maxAttempts = 3): array {
    if (!isset(sf_mp_job_catalog()[$jobType])) return ['ok'=>false,'error'=>'invalid_job_type'];
    $pdo = sf_db();
    if (!$pdo || !sf_mp_table_exists('media_processing_jobs')) return ['ok'=>false,'error'=>'media_schema_missing'];
    $key = sf_mp_random_key(24);
    try {
        $existing = $pdo->prepare("SELECT id,job_key,status FROM media_processing_jobs WHERE object_id=? AND job_type=? AND status IN ('queued','running','retry') ORDER BY id DESC LIMIT 1");
        $existing->execute([$objectId,$jobType]);
        $row = $existing->fetch();
        if ($row) return ['ok'=>true,'duplicate'=>true,'id'=>(int)$row['id'],'job_key'=>$row['job_key']];
        $stmt = $pdo->prepare('INSERT INTO media_processing_jobs (job_key,object_id,job_type,status,priority,max_attempts,run_after) VALUES (?,?,?,"queued",?,?,NOW())');
        $stmt->execute([$key,$objectId,$jobType,max(1,min(1000,$priority)),max(1,min(10,$maxAttempts))]);
        return ['ok'=>true,'id'=>(int)$pdo->lastInsertId(),'job_key'=>$key];
    } catch (Throwable $e) {
        error_log('Stonefellow media job enqueue failed: ' . $e->getMessage());
        return ['ok'=>false,'error'=>'job_enqueue_failed'];
    }
}

function sf_mp_enqueue_default_jobs(int $objectId, string $kind, string $role): array {
    $jobs = ['probe','integrity_check'];
    if ($role === 'original' && $kind === 'audio') $jobs = array_merge($jobs, ['audio_stream','audio_preview','audio_waveform']);
    if ($role === 'original' && $kind === 'video') $jobs = array_merge($jobs, ['video_hls','video_preview','video_poster']);
    $queued = [];
    foreach ($jobs as $index => $job) {
        $result = sf_mp_enqueue_job($objectId, $job, 50 + ($index * 10));
        if (!empty($result['ok'])) $queued[] = $job;
    }
    sf_mp_mark_object($objectId, 'processing', ['queued_jobs'=>$queued]);
    return $queued;
}

function sf_mp_claim_job(): ?array {
    $pdo = sf_db();
    if (!$pdo || !sf_mp_table_exists('media_processing_jobs')) return null;
    $lock = sf_mp_random_key(32);
    $lease = sf_mp_env_int('SF_MEDIA_JOB_LEASE_SECONDS', 1800, 60, 14400);
    try {
        $pdo->beginTransaction();
        $row = $pdo->query("SELECT * FROM media_processing_jobs WHERE status IN ('queued','retry') AND run_after<=NOW() AND (locked_until IS NULL OR locked_until<NOW()) ORDER BY priority ASC,id ASC LIMIT 1 FOR UPDATE")->fetch();
        if (!$row) { $pdo->commit(); return null; }
        $lockedUntil = gmdate('Y-m-d H:i:s', time() + $lease);
        $stmt = $pdo->prepare("UPDATE media_processing_jobs SET status='running',attempts=attempts+1,lock_token=?,locked_until=?,started_at=COALESCE(started_at,NOW()),progress_percent=1 WHERE id=?");
        $stmt->execute([$lock,$lockedUntil,(int)$row['id']]);
        $pdo->commit();
        $row['lock_token'] = $lock;
        $row['attempts'] = (int)$row['attempts'] + 1;
        return $row;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Stonefellow media job claim failed: ' . $e->getMessage());
        return null;
    }
}
