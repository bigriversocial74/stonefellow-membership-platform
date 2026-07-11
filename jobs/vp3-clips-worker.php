<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once dirname(__DIR__).'/includes/vp3_clips.php';
$limit=max(1,min(100,(int)($argv[1]??10)));$processed=0;for($i=0;$i<$limit;$i++){$result=sf_vp3_clip_process_next();if(!empty($result['idle']))break;$processed++;echo json_encode($result,JSON_UNESCAPED_SLASHES).PHP_EOL;if(empty($result['ok'])&&($result['error']??'')==='clip_schema_missing')exit(1);}echo "VP3 clip jobs processed: {$processed}\n";
