<?php
declare(strict_types=1);
require_once __DIR__.'/includes/vp3_clips.php';
$uuid=trim((string)($_GET['clip']??''));$token=trim((string)($_GET['token']??''));$kind=(string)($_GET['kind']??'video');$clip=sf_vp3_clip_by_uuid($uuid);
if(!$clip||!hash_equals((string)$clip['public_token'],$token)||empty($clip['rights_confirmed'])||in_array((string)$clip['local_status'],['draft','render_queued','rendering','failed','withdrawn'],true)){http_response_code(404);exit;}
$path=$kind==='poster'?(string)$clip['rendered_poster_path']:(string)$clip['rendered_video_path'];if($path===''||!is_file($path)){http_response_code(404);exit;}
$mime=$kind==='poster'?'image/jpeg':'video/mp4';$size=filesize($path);$start=0;$end=max(0,$size-1);$status=200;
header('Content-Type: '.$mime);header('Accept-Ranges: bytes');header('Cache-Control: public, max-age=300, must-revalidate');header('X-Content-Type-Options: nosniff');header('Content-Disposition: inline');
if($kind!=='poster'&&isset($_SERVER['HTTP_RANGE'])&&preg_match('/bytes=(\d*)-(\d*)/',(string)$_SERVER['HTTP_RANGE'],$m)){$start=$m[1]!==''?(int)$m[1]:0;$end=$m[2]!==''?(int)$m[2]:$end;if($start<0||$end<$start||$end>=$size){header('Content-Range: bytes */'.$size);http_response_code(416);exit;}$status=206;http_response_code(206);header("Content-Range: bytes {$start}-{$end}/{$size}");}
$length=$end-$start+1;header('Content-Length: '.$length);$fh=fopen($path,'rb');if(!$fh){http_response_code(500);exit;}fseek($fh,$start);$remaining=$length;while($remaining>0&&!feof($fh)){$chunk=fread($fh,min(1048576,$remaining));if($chunk===false)break;echo$chunk;$remaining-=strlen($chunk);if(function_exists('fastcgi_finish_request')&&connection_aborted())break;}fclose($fh);
