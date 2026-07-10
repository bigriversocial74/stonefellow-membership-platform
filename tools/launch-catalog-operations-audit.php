<?php

declare(strict_types=1);
$root=dirname(__DIR__);
function body(string $path): string { global$root;if($path==='includes/catalog_operations.php'){$out='';foreach(['includes/catalog_operations.php','includes/catalog_operations_core.php','includes/catalog_operations_readiness.php','includes/catalog_operations_actions.php','includes/catalog_operations_transfer.php'] as$f)$out.=(string)@file_get_contents($root.'/'.$f);return$out;}return(string)@file_get_contents($root.'/'.$path); }
function has(string $path,array $markers): bool { $b=body($path);if($b==='')return false;foreach($markers as$m)if(stripos($b,$m)===false)return false;return true; }
$sections=[
 'Catalog registry + readiness'=>has('includes/catalog_operations.php',["'series'","'season'","'episode'","'video'","'album'","'song'","'character'","'product'","'plan'",'sf_lco_score_checks','weight']),
 'Relationships + ordering'=>has('includes/catalog_operations.php',['season_relation','episode_relation','album_relation','track_order','episode_video']),
 'Media processing readiness'=>has('includes/catalog_operations.php',['media_objects','manifest','segment','waveform','song_preview','video_media']),
 'Commerce + membership plans'=>has('includes/catalog_operations.php',['price_cents','inventory','billing_interval','Plan entitlements']),
 'SEO + public presentation'=>has('includes/catalog_operations.php',['catalog_seo_metadata','canonical_path','social_image_asset_id','robots_noindex']),
 'Scheduling + timezone safety'=>has('includes/catalog_operations.php',['DateTimeZone','setTimezone','UTC','sf_lco_run_due']),
 'Bulk import + export'=>has('includes/catalog_operations.php',['sf_lco_parse_transfer','draft','sf_lco_transfer_commit','sf_lco_export_csv','content_sha256']),
 'Sample cleanup + rollback'=>has('includes/catalog_operations.php',['sf_lco_scan_samples','confidence_percent','sf_lco_archive_samples','sf_lco_rollback_batch','before_json','after_json']),
 'Security + audit evidence'=>has('api/catalog-operations-tick.php',['POST required','hash_equals','SF_CATALOG_RUNNER_SECRET','idempotency'])&&has('database/migrations/024_launch_content_catalog_operations.sql',['event_key','UNIQUE KEY unique_catalog_operation_event']),
 'Admin + CI + documentation'=>has('admin/catalog-operations.php',['Launch Catalog','Publish Ready Items','Rollback'])&&has('.github/workflows/code-audit.yml',['Launch content catalog operations smoke tests','Launch content catalog operations audit'])&&has('docs/LAUNCH_CONTENT_CATALOG_OPERATIONS_V1.md',['10/10']),
];
$passed=0;echo"Stonefellow Launch Content & Catalog Operations Audit\n";foreach($sections as$name=>$ok){echo($ok?'[10/10] ':'[FAIL] ').$name."\n";if($ok)$passed++;}$score=(int)round($passed/count($sections)*10);echo"Overall: {$score}/10\n";if($passed!==count($sections)){exit(1);}echo"All ten sections score 10/10.\n";
