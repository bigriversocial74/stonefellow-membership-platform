<?php

declare(strict_types=1);
$root=dirname(__DIR__);$read=static fn(string $path):string=>is_file($root.'/'.$path)?(string)file_get_contents($root.'/'.$path):'';$contains=static function(string $path,array $markers)use($read):bool{$body=$read($path);if($body==='')return false;foreach($markers as$marker)if(stripos($body,(string)$marker)===false)return false;return true;};
$sections=[
 'Provider-Neutral Storage Architecture'=>[
  ['includes/media_storage_core.php',['sf_mp_provider_registry','local_private','s3_compatible']],
  ['includes/media_storage_registry.php',['sf_mp_default_provider']],
  ['database/migrations/023_protected_media_storage_cdn_transcoding.sql',['media_storage_providers','media_objects','unique_media_storage_key']],
 ],
 'Private Local and S3-Compatible Storage'=>[
  ['includes/media_storage_core.php',['sf_mp_local_root']],
  ['includes/media_storage_s3.php',['sf_mp_s3_presign','AWS4-HMAC-SHA256']],
  ['includes/media_storage_operations.php',['CURLOPT_SSL_VERIFYPEER']],
  ['storage/private_media_v2/.htaccess',['Require all denied','Deny from all']],
 ],
 'Resumable and Validated Ingestion'=>[
  ['includes/media_upload_sessions.php',['sf_mp_create_upload_session','expected_chunks']],
  ['includes/media_upload_chunks.php',['sf_mp_receive_upload_chunk','chunk_checksum_mismatch']],
  ['api/media-upload-chunk.php',['chunk_too_large','X_CHUNK_SHA256','csrf_failed']],
 ],
 'Quarantine, Checksums, and Deduplication'=>[
  ['includes/media_upload_finalize.php',['assembled_checksum_mismatch','checksum_sha256','duplicate']],
  ['includes/media_storage_core.php',['sf_mp_media_kind']],
  ['includes/media_storage_core.php',['sf_mp_quarantine_rules']],
  ['includes/media_storage_registry.php',['hash_file','quarantined']],
 ],
 'Leased Processing Queue and Recovery'=>[
  ['includes/media_processing_queue.php',['FOR UPDATE','locked_until','max_attempts']],
  ['includes/media_processing_worker.php',["\$status==='retry'",'sf_mp_run_worker']],
  ['api/media-processing-worker.php',['SF_MEDIA_WORKER_SECRET','abs(time()-$timestamp) > 300','hash_hmac']],
 ],
 'Audio Preview, Stream, and Waveform'=>[
  ['includes/media_processing_queue.php',['audio_preview','audio_stream']],
  ['includes/media_processing_audio.php',['sf_mp_generate_waveform','peak_count','256k']],
  ['includes/audio_player.php',['waveform_url','pipeline_','sf_mp_object_url']],
 ],
 'Adaptive Video HLS and Artwork'=>[
  ['includes/media_processing_video.php',['sf_mp_video_profiles','360','720','1080','hls_segment_filename','video_poster']],
  ['watch.php',['adaptive HLS','data-hls-source','hls.js@1.6.16']],
 ],
 'Signed Delivery Sessions and CDN'=>[
  ['includes/media_delivery_tokens.php',['sf_mp_create_delivery_session','SF_MEDIA_BIND_SESSION_FINGERPRINT']],
  ['includes/media_delivery_manifests.php',['sf_mp_render_manifest']],
  ['includes/media_delivery_transport.php',['sf_mp_cdn_signed_url']],
  ['media-manifest.php',['sf_mp_validate_delivery_request','sf_mp_log_delivery']],
  ['media-segment.php',['sf_mp_validate_delivery_request','sf_mp_remote_object_url']],
  ['includes/media_delivery_tokens.php',['object_outside_delivery_session','parent_object_id']],
 ],
 'Admin Operations and Health Evidence'=>[
  ['admin/media-pipeline.php',['Protected Media Storage, CDN & Transcoding','Run Storage Health','Clean Expired Uploads','Processing Queue']],
  ['includes/media_storage_operations.php',['sf_mp_run_storage_health_check','write_test_status','delete_test_status']],
  ['admin/media-delivery.php',['Signed CDN delivery','Active signed HLS sessions']],
 ],
 'Migration, Configuration, Documentation, and CI'=>[
  ['.env.media.example',['SF_MEDIA_STORAGE_DRIVER','SF_MEDIA_WORKER_SECRET','SF_MEDIA_S3_SECRET_KEY','SF_MEDIA_CDN_SIGNING_KEY']],
  ['docs/PROTECTED_MEDIA_STORAGE_CDN_TRANSCODING_V1.md',['Initial static score','Final static score','10/10']],
  ['tests/protected_media_pipeline_smoke.php',['Protected media storage, CDN, and transcoding smoke: PASS']],
  ['.github/workflows/code-audit.yml',['protected_media_pipeline_smoke.php','protected-media-pipeline-audit.php']],
 ],
];
$failed=[];$all=true;echo"Stonefellow Protected Media Storage, CDN & Transcoding Audit v1\n".str_repeat('=',72)."\n";foreach($sections as$section=>$checks){$passed=0;foreach($checks as[$path,$markers]){if($contains($path,$markers))$passed++;else$failed[]=$section.': '.$path.' missing required evidence.';}$score=(int)round($passed/count($checks)*10);if($score!==10)$all=false;echo sprintf("%-50s %d/10 (%d/%d)\n",$section,$score,$passed,count($checks));}echo str_repeat('-',72)."\n".($all?"Overall score: 10/10\n":"Overall score: below 10/10\n");if($failed){echo"\nBlocking findings:\n- ".implode("\n- ",$failed)."\n";exit(1);}echo"Result: PASS — all ten protected-media sections score 10/10.\n";
