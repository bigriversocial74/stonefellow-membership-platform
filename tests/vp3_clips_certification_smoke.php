<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$checks=[
 'includes/vp3_clips.php'=>['vp3_clips_certification.php'],
 'includes/vp3_clips_certification.php'=>['sf_vp3_clip_render_probe','testsrc2','signed_context','api/v1/clips/bridge/certification.php','import_migration_028'],
 'includes/vp3_clips_bridge.php'=>['bridge_certification_required','publishing_mode'],
 'admin/vp3-clips-certification.php'=>['Run full certification','Refresh VP3 approval','synthetic render'],
 'admin/vp3-clips-settings.php'=>['Open live certification','Publishing mode'],
 'database/migrations/028_vp3_clips_live_certification.sql'=>['vp3_clip_certifications','publishing_mode'],
];
foreach($checks as $file=>$needles){$body=(string)file_get_contents($root.'/'.$file);if($body==='')throw new RuntimeException("Missing {$file}");foreach($needles as $needle)if(stripos($body,$needle)===false)throw new RuntimeException("{$file} missing {$needle}");}
$runtime=(string)file_get_contents($root.'/includes/vp3_clips_certification.php').(string)file_get_contents($root.'/includes/vp3_clips_bridge.php');
if(strpos($runtime,'license_key')!==false)throw new RuntimeException('Certification must not use the product license key.');
if(strpos($runtime,'@unlink($video)')===false||strpos($runtime,'@unlink($poster)')===false)throw new RuntimeException('Synthetic certification media must be removed.');
echo "Stonefellow VP3 Clips live certification smoke: PASS\n";
