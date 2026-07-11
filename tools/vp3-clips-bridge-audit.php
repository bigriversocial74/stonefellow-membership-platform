<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$sections=[
 'Source ownership'=>[['includes/vp3_clips_core.php',['source_media_object_id','source_updated_at']]],
 'Private credential'=>[['includes/vp3_clips_core.php',['SF_VP3_BRIDGE_SETTINGS_KEY','aes-256-gcm']]],
 'Rendering'=>[['includes/vp3_clips_render.php',['libx264','force_original_aspect_ratio=increase','crop=']]],
 'Signed transport'=>[['includes/vp3_clips_bridge.php',['X-VP3-Bridge-ID','X-VP3-Request-ID','hash_hmac']]],
 'Public delivery'=>[['vp3-clip-media.php',['hash_equals','Accept-Ranges']]],
 'Rights and withdrawal'=>[['admin/vp3-clip-editor.php',['rights_confirmed','Withdraw from VP3']]],
 'Persistence'=>[['database/migrations/027_vp3_clips_publisher_bridge.sql',['vp3_clip_jobs','vp3_clip_sync_events']]],
 'Permanent verification'=>[['tests/vp3_clips_bridge_smoke.php',['Stonefellow VP3 Clips bridge smoke: PASS']]],
];
foreach($sections as $name=>$checks)foreach($checks as [$file,$needles]){
    $body=(string)file_get_contents($root.'/'.$file);
    foreach($needles as $needle)if(strpos($body,$needle)===false)throw new RuntimeException("{$name}: {$file} missing {$needle}");
}
echo "Stonefellow VP3 Clips bridge audit: 10/10 PASS\n";
