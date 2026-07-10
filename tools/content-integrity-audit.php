<?php

declare(strict_types=1);
$root=dirname(__DIR__);$read=static fn(string $f):string=>is_file($root.'/'.$f)?(string)file_get_contents($root.'/'.$f):'';
$sections=[
 'Publishing validation'=>[
  ['includes/publishing.php',['sf_content_publish_statuses','sf_content_access_levels','sf_content_normalize_datetime','publish_window_end','sf_content_enum_allows']],
  ['includes/publishing.php',['beginTransaction','FOR UPDATE','rollBack','publishing_events']],
  ['admin/publishing.php',['Publishing settings were rejected','sf_publish_apply']],
 ],
 'Scheduled publishing execution'=>[
  ['includes/publishing.php',['sf_publish_run_due','sf_content_advisory_lock','publish_due_run','RELEASE_LOCK']],
  ['api/publishing-run.php',['REQUEST_METHOD','SF_PUBLISHING_RUN_SECRET','hash_equals','rate_limited']],
  ['admin/publishing.php',['action" value="run_due"','never run during a page view']],
 ],
 'Search index integrity'=>[
  ['includes/publishing.php',['sf_publish_reindex','content_search_index','ON DUPLICATE KEY UPDATE']],
  ['includes/search.php',['sf_search_source_available','sf_publish_is_available','sf_search_safe_url']],
  ['includes/search.php',['sf_access_allows','status=\'published\'']],
 ],
 'Import upload boundaries'=>[
  ['includes/importer.php',['SF_IMPORT_MAX_BYTES','SF_IMPORT_MAX_ROWS','FILEINFO_MIME_TYPE','Only CSV and JSON imports are allowed']],
  ['includes/importer.php',['100 columns','CSV row width','JSON_THROW_ON_ERROR']],
  ['.env.example',['SF_IMPORT_MAX_BYTES=2097152','SF_IMPORT_MAX_ROWS=500']],
 ],
 'Draft-first import policy'=>[
  ['includes/importer.php',["'status'=>'draft'","'status'=>'inactive'",'is_primary'=>0]],
  ['admin/import.php',['Draft First','does not become public automatically']],
  ['includes/importer.php',['Invalid status for target table','Invalid access_level']],
 ],
 'Atomic import and rollback'=>[
  ['includes/importer.php',['sf_content_advisory_lock','beginTransaction','Import completed atomically','no content changes were committed']],
  ['includes/importer.php',['sf_importer_rollback_batch','FOR UPDATE','rolled back atomically']],
  ['admin/import.php',['Type IMPORT','one transaction','preview_digest']],
 ],
 'Search query safety'=>[
  ['includes/search.php',['sf_content_like_escape','ESCAPE','sf_search_query','100']],
  ['includes/search.php',['sf_search_index_ready','sf_search_static_index','!$indexReady']],
  ['includes/search.php',['sf_content_rate_limit','sf_content_client_hash','array_slice($rows,0,100)']],
 ],
 'Comment validation and privacy'=>[
  ['includes/engagement.php',['sf_content_comment_body','sf_content_exists','sf_content_user_active','Duplicate comment detected']],
  ['includes/engagement.php',['comment-user-','comment-ip-','sf_content_client_hash']],
  ['comments.php',['minlength="2"','sf_eng_is_admin','approved comments']],
 ],
 'Reaction and moderation safety'=>[
  ['includes/engagement.php',['sf_comment_react','Reaction limit reached','status="approved" FOR UPDATE','reaction_count=reaction_count+1']],
  ['includes/engagement.php',['sf_comment_update_status','admin.content.manage','comment_moderation_events','beginTransaction']],
  ['includes/engagement.php',['sf_comment_moderation_queue','sf_eng_is_admin','return []']],
 ],
 'API and operator controls'=>[
  ['api/comments.php',['method_not_allowed','cross_origin_request_blocked','HTTP_X_CSRF_TOKEN','csrf_failed']],
  ['admin/import.php',['confirm_import','IMPORT','atomic']],
  ['.env.example',['SF_COMMENTS_REQUIRE_APPROVAL=1','SF_PUBLISHING_RUN_SECRET=']],
 ],
];
$fail=[];$earned=0;$total=0;echo "Stonefellow Content Publishing, Search, Import & Moderation Audit v1\n".str_repeat('=',70)."\n";
foreach($sections as $section=>$checks){$pass=0;foreach($checks as [$file,$markers]){$total++;$body=$read($file);$missing=[];foreach($markers as $m)if($body===''||stripos($body,$m)===false)$missing[]=$m;if(!$missing){$pass++;$earned++;}else$fail[]=$section.': '.$file.' missing ['.implode(', ',$missing).'].';$score=(int)round($pass/count($checks)*10);echo sprintf("%-42s %d/10 (%d/%d)\n",$section,$score,$pass,count($checks));}
$overall=$total?round($earned/$total*10,1):0;echo str_repeat('-',70)."\nOverall score: {$overall}/10\n";if($fail){echo "\nBlocking findings:\n- ".implode("\n- ",$fail)."\n";exit(1);}echo "Result: PASS — all ten sections score 10/10.\n";
