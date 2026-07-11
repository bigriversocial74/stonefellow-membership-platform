<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$checks=[
    'includes/vp3_clips.php'=>['vp3_clips_core.php','vp3_clips_render.php','vp3_clips_bridge.php','vp3_clips_jobs.php'],
    'includes/vp3_clips_core.php'=>['aes-256-gcm','source_media_object_id','source_updated_at'],
    'includes/vp3_clips_render.php'=>['sf_vp3_clip_render','libx264','force_original_aspect_ratio=increase'],
    'includes/vp3_clips_bridge.php'=>['X-VP3-Signature','X-VP3-Nonce','sf_vp3_clip_withdraw','hash_hmac'],
    'admin/vp3-clips.php'=>['Clip Creator & Publishing Bridge','Run next job'],
    'admin/vp3-clip-editor.php'=>['Save & render','Publish / update VP3','rights_confirmed'],
    'admin/vp3-clips-settings.php'=>['complete product key is never','Test signed connection'],
    'vp3-clip-media.php'=>['Accept-Ranges','public_token'],
    'database/migrations/027_vp3_clips_publisher_bridge.sql'=>['vp3_clips','vp3_clip_jobs','vp3_clip_sync_events'],
    'jobs/vp3-clips-worker.php'=>['sf_vp3_clip_process_next'],
];
$runtime='';
foreach($checks as $file=>$needles){
    $path=$root.'/'.$file;
    if(!is_file($path))throw new RuntimeException("Missing {$file}");
    $body=(string)file_get_contents($path);
    if(str_starts_with($file,'includes/vp3_clips'))$runtime.=$body;
    foreach($needles as $needle)if(stripos($body,$needle)===false)throw new RuntimeException("{$file} missing {$needle}");
}
if(strpos($runtime,'license_key')!==false)throw new RuntimeException('The clip bridge must not store or transmit the product license key.');
if(strpos($runtime,'hash_hmac')===false||strpos($runtime,'X-VP3-Nonce')===false)throw new RuntimeException('Signed replay-resistant requests are required.');
echo "Stonefellow VP3 Clips bridge smoke: PASS\n";
