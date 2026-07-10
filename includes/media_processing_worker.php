<?php

declare(strict_types=1);

function sf_mp_integrity_check(array $object,string $source):array{
    $size=(int)filesize($source);$hash=sf_mp_hash_file($source);$expectedSize=(int)$object['size_bytes'];$expectedHash=(string)($object['checksum_sha256']??'');
    if($expectedSize>0&&$size!==$expectedSize)return['ok'=>false,'error'=>'integrity_size_mismatch'];
    if($expectedHash!==''&&!hash_equals($expectedHash,$hash))return['ok'=>false,'error'=>'integrity_checksum_mismatch'];
    return['ok'=>true,'size_bytes'=>$size,'checksum_sha256'=>$hash];
}

function sf_mp_process_job(array $job): array {
    $object=sf_mp_object_by_id((int)$job['object_id']);if(!$object)return['ok'=>false,'error'=>'media_object_missing'];
    $source=sf_mp_source_path($object);if(empty($source['ok']))return$source;
    $path=$source['path'];$dir=sf_mp_output_temp_dir((int)$object['id'],(string)$job['job_type']);if(!$dir){if(!empty($source['temporary']))@unlink($path);return['ok'=>false,'error'=>'processing_directory_failed'];}
    $probe=sf_mp_probe_file($path);$result=[];$type=(string)$job['job_type'];
    if($type==='probe'){$result=$probe;if(!empty($result['ok']))sf_mp_update_probe_metadata($object,$result);}
    elseif($type==='integrity_check')$result=sf_mp_integrity_check($object,$path);
    elseif(str_starts_with($type,'audio_'))$result=sf_mp_process_audio($object,$path,$type,$dir);
    elseif(str_starts_with($type,'video_')){$probeOk=!empty($probe['ok'])?$probe:['ok'=>true,'duration_seconds'=>0,'video'=>[]];$result=sf_mp_process_video($object,$path,$type,$dir,$probeOk);}
    else $result=['ok'=>false,'error'=>'job_not_implemented'];
    if(!empty($source['temporary']))@unlink($path);
    sf_mp_remove_tree($dir);
    return$result;
}

function sf_mp_remove_tree(string $path):void{if(!is_dir($path))return;foreach(scandir($path)?:[] as$item){if($item==='.'||$item==='..')continue;$child=$path.'/'.$item;if(is_dir($child))sf_mp_remove_tree($child);else@unlink($child);}@rmdir($path);}

function sf_mp_finish_job(array $job,array $result):void{
    $pdo=sf_db();if(!$pdo)return;$ok=!empty($result['ok']);$attempts=(int)$job['attempts'];$max=(int)$job['max_attempts'];$status=$ok?'completed':($attempts<$max?'retry':'failed');$runAfter=$status==='retry'?gmdate('Y-m-d H:i:s',time()+min(3600,60*(2**max(0,$attempts-1)))):gmdate('Y-m-d H:i:s');
    try{$stmt=$pdo->prepare('UPDATE media_processing_jobs SET status=?,progress_percent=?,run_after=?,locked_until=NULL,lock_token=NULL,output_json=?,error_message=?,completed_at=IF(?="completed",NOW(),completed_at) WHERE id=? AND lock_token=?');$stmt->execute([$status,$ok?100:0,$runAfter,json_encode($result,JSON_UNESCAPED_SLASHES),$ok?null:(string)($result['error']??'processing_failed'),$status,(int)$job['id'],$job['lock_token']]);
        if(!$ok&&$status==='failed')sf_mp_mark_object((int)$job['object_id'],'failed',['failed_job'=>$job['job_type'],'error'=>$result['error']??'processing_failed']);
        if($ok){$remaining=$pdo->prepare("SELECT COUNT(*) FROM media_processing_jobs WHERE object_id=? AND status IN ('queued','running','retry','failed')");$remaining->execute([(int)$job['object_id']]);if((int)$remaining->fetchColumn()===0)sf_mp_mark_object((int)$job['object_id'],'ready',['pipeline_complete'=>true]);}
    }catch(Throwable $e){error_log('Stonefellow media job finish failed: '.$e->getMessage());}
}

function sf_mp_run_worker(int $maxJobs=3):array{$maxJobs=max(1,min(20,$maxJobs));$processed=[];for($i=0;$i<$maxJobs;$i++){$job=sf_mp_claim_job();if(!$job)break;$result=sf_mp_process_job($job);sf_mp_finish_job($job,$result);$processed[]=['job_key'=>$job['job_key'],'job_type'=>$job['job_type'],'ok'=>!empty($result['ok']),'error'=>$result['error']??null];}return['ok'=>true,'processed'=>$processed,'count'=>count($processed)];}

function sf_mp_queue_summary():array{$pdo=sf_db();if(!$pdo||!sf_mp_table_exists('media_processing_jobs'))return[];try{$rows=$pdo->query('SELECT status,COUNT(*) total FROM media_processing_jobs GROUP BY status')->fetchAll()?:[];$out=[];foreach($rows as$row)$out[$row['status']]=(int)$row['total'];return$out;}catch(Throwable $e){return[];}}
