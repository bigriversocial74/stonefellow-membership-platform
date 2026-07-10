<?php

declare(strict_types=1);
putenv('SF_SKIP_INSTALL_REDIRECT=1');
putenv('SF_ENV=testing');
putenv('SF_APP_KEY=content-smoke-app-key-123456789012345678901234567890');
putenv('SF_HASH_SALT=content-smoke-hash-key-123456789012345678901234567890');
putenv('SF_IMPORT_MAX_BYTES=2097152');
putenv('SF_IMPORT_MAX_ROWS=500');
$_SERVER['HTTP_HOST']='stonefellow.test';
$_SERVER['REMOTE_ADDR']='192.0.2.80';
require_once __DIR__.'/../includes/content_integrity.php';
require_once __DIR__.'/../includes/publishing.php';
require_once __DIR__.'/../includes/importer.php';
require_once __DIR__.'/../includes/search.php';

$failures=[];$assert=static function(bool $ok,string $message)use(&$failures):void{if(!$ok)$failures[]=$message;};
$assert(sf_content_safe_slug(' Hello, Stonefellow! ')==='hello-stonefellow','Slug normalization should be deterministic.');
$assert(sf_content_normalize_datetime('2026-07-10 10:30')==='2026-07-10 10:30:00','Valid datetimes should normalize.');
$assert(sf_content_normalize_datetime('not-a-date')===null,'Invalid datetimes should fail closed.');
$assert(sf_content_like_escape('50%_off')==='50\\%\\_off','LIKE wildcards should be escaped.');
$assert(sf_search_query(str_repeat('x',200))===str_repeat('x',100),'Search queries should be bounded to 100 characters.');
$assert(sf_search_safe_url('javascript:alert(1)')==='#','Unsafe search URLs should be rejected.');
$assert(sf_search_safe_url('/song.php?slug=test')==='/song.php?slug=test','Root-relative search URLs should be allowed.');

$good=sf_content_comment_body('This is a useful comment.');
$assert($good['ok']===true,'Normal comments should pass validation.');
$assert(sf_content_comment_body('x')['ok']===false,'One-character comments should fail.');
$assert(sf_content_comment_body('https://a.test https://b.test https://c.test')['ok']===false,'Comments with excessive links should fail.');
$assert(sf_content_comment_body(str_repeat('a',20))['ok']===false,'Repeated-character spam should fail.');

foreach(['album','episode','song','video','product'] as $type){$config=sf_importer_config($type);$assert(is_array($config),$type.' import config should exist.');$status=(string)($config['defaults']['status']??'');$assert(in_array($status,['draft','inactive'],true),$type.' imports should default to draft or inactive.');}
$parsed=sf_importer_parse_payload(null,'[{"title":"Test Album","slug":"test-album"}]');
$assert(count($parsed)===1,'Bounded JSON import parsing should return one row.');
$preview=sf_importer_preview('album',$parsed);
$assert(isset($preview['digest'])&&strlen((string)$preview['digest'])===64,'Import previews should include a SHA-256 digest.');

$future=date('Y-m-d H:i:s',time()+3600);$past=date('Y-m-d H:i:s',time()-3600);
$assert(sf_publish_row_state(['status'=>'published','release_at'=>$future])==='scheduled','Future published content should compute as scheduled.');
$assert(sf_publish_row_state(['status'=>'published','publish_window_end'=>$past])==='expired','Expired windows should compute as expired.');

$root=dirname(__DIR__);$markers=[
 'includes/publishing.php'=>['beginTransaction','FOR UPDATE','sf_publish_reindex','sf_content_advisory_lock','publish_due_run'],
 'api/publishing-run.php'=>['REQUEST_METHOD','hash_equals','SF_PUBLISHING_RUN_SECRET'],
 'includes/importer.php'=>['SF_IMPORT_MAX_BYTES','SF_IMPORT_MAX_ROWS','FILEINFO_MIME_TYPE','JSON_THROW_ON_ERROR','beginTransaction','rollBack'],
 'includes/search.php'=>['sf_content_like_escape','sf_search_source_available','sf_access_allows','sf_search_safe_url'],
 'includes/engagement.php'=>['sf_content_comment_body','comment-user-','sf_content_client_hash','Duplicate comment detected','beginTransaction'],
 'api/comments.php'=>['cross_origin_request_blocked','HTTP_X_CSRF_TOKEN','method_not_allowed'],
 'admin/publishing.php'=>['run_due','never run during a page view'],
 'admin/import.php'=>['Type IMPORT','commit every content change in one transaction'],
];
foreach($markers as $file=>$needles){$body=(string)file_get_contents($root.'/'.$file);foreach($needles as $needle)$assert(stripos($body,$needle)!==false,$file.' should contain '.$needle.'.');}
if($failures){fwrite(STDERR,"Content integrity smoke failures:\n- ".implode("\n- ",$failures)."\n");exit(1);}echo "Content integrity smoke: PASS\n";
