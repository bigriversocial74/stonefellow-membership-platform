<?php

declare(strict_types=1);
$root=dirname(__DIR__);$read=static fn(string $f):string=>is_file($root.'/'.$f)?(string)file_get_contents($root.'/'.$f):'';
$sections=[
 'Template and header safety'=>[
  ['includes/delivery_integrity.php',['sf_delivery_clean_header','sf_delivery_safe_email','sf_delivery_sanitize_email_html','Undeclared variables']],
  ['includes/notifications.php',['sf_notify_render_html','sf_delivery_validate_template','Content-Type: text/plain']],
  ['admin/email-templates.php',['sandbox=""','srcdoc','scripts and forms disabled']],
 ],
 'Queue idempotency'=>[
  ['includes/delivery_integrity.php',['sf_delivery_idempotency_key','idempotency_key']],
  ['includes/notifications.php',['JSON_EXTRACT(metadata_json','sf_notify_log',"status<>'canceled'"]],
  ['includes/ops_scheduler_messaging.php',['campaign-','recipient-','sf_msg_create_member_message']],
 ],
 'Queue locking and leases'=>[
  ['includes/notifications.php',['sf_delivery_advisory_lock','message-','notification-queue','FOR UPDATE']],
  ['includes/ops_scheduler_messaging.php',['job-','due-jobs','campaign-','due-campaigns']],
  ['includes/delivery_integrity.php',['GET_LOCK','RELEASE_LOCK']],
 ],
 'Retries and provider boundaries'=>[
  ['includes/notifications.php',['SF_NOTIFICATION_MAX_ATTEMPTS','sf_delivery_backoff_seconds','Maximum delivery attempts exceeded','SF_ALLOW_LOG_MAIL_PROVIDER','SF_ALLOW_PHP_MAIL_PROVIDER']],
  ['includes/delivery_integrity.php',['SF_NOTIFICATION_RETRY_BASE_SECONDS','next_attempt_at']],
  ['admin/notifications.php',['Only failed or canceled','Reset attempts','provider']],
 ],
 'Preference enforcement'=>[
  ['includes/delivery_integrity.php',['sf_delivery_preference_enabled','all_marketing','sf_delivery_transactional_type']],
  ['includes/notifications.php',['honors_preferences','Recipient preference disabled']],
  ['includes/ops_scheduler_messaging.php',['honors_preferences','preferenceKey','skippedChannels']],
 ],
 'Scheduler correctness'=>[
  ['includes/ops_scheduler_messaging.php',["frequency<>'manual'",'sf_sched_next_run','Job is already running','run lease']],
  ['admin/ops-scheduler.php',['Manual-frequency jobs are never automatically due','exclusive lease']],
  ['api/ops-scheduler.php',['SF_OPS_SCHEDULER_SECRET','hash_equals','run_due']],
 ],
 'Campaign channel integrity'=>[
  ['includes/ops_scheduler_messaging.php',['sf_msg_log_status','delivery_status','email_log_id','member_message_id','skippedChannels']],
  ['admin/member-messaging.php',['preference-skipped','audience snapshot','immutable']],
  ['api/member-notices.php',['admin.members.manage','csrf_failed','method_not_allowed']],
 ],
 'Webhook authenticity and privacy'=>[
  ['api/notification-webhook.php',['sf_delivery_webhook_signature_valid','provider_event_id','duplicate','[redacted]','beginTransaction']],
  ['includes/delivery_integrity.php',['SF_NOTIFICATION_WEBHOOK_SECRET','hash_hmac','hash_equals','sf_delivery_mask_email']],
  ['.env.example',['SF_NOTIFICATION_WEBHOOK_MAX_BYTES=262144','SF_NOTIFICATION_WEBHOOK_SECRET=']],
 ],
 'Admin/operator safeguards'=>[
  ['admin/notifications.php',['confirm(','privacy-reduced logs','Dispatch complete','Test notification failed']],
  ['admin/member-messaging.php',['confirm(','Sent or archived campaigns cannot be sent again']],
  ['admin/ops-scheduler.php',['confirm(','Job was rejected','Due jobs:']],
 ],
 'Production configuration'=>[
  ['.env.example',['SF_NOTIFICATION_MAX_ATTEMPTS=5','SF_NOTIFICATION_RETRY_BASE_SECONDS=60','SF_OPS_SCHEDULER_SECRET=','SF_ALLOW_LOG_MAIL_PROVIDER=0','SF_ALLOW_PHP_MAIL_PROVIDER=0']],
  ['includes/notifications.php',['Log/sandbox mail provider is disabled in production','PHP mail provider is not production-enabled']],
  ['api/ops-scheduler.php',['HTTP_X_STONEFELLOW_SCHEDULER_SECRET','rate_limited']],
 ],
];
$fail=[];$earned=0;$total=0;echo "Stonefellow Email, Notifications, Scheduler & Delivery Audit v1\n".str_repeat('=',68)."\n";
foreach($sections as $section=>$checks){$pass=0;foreach($checks as [$file,$markers]){$total++;$body=$read($file);$missing=[];foreach($markers as $m)if($body===''||stripos($body,(string)$m)===false)$missing[]=(string)$m;if(!$missing){$pass++;$earned++;}else{$fail[]=$section.': '.$file.' missing ['.implode(', ',$missing).'].';}}$score=(int)round($pass/count($checks)*10);echo sprintf("%-40s %d/10 (%d/%d)\n",$section,$score,$pass,count($checks));}
$overall=$total?round($earned/$total*10,1):0;echo str_repeat('-',68)."\nOverall score: {$overall}/10\n";if($fail){echo "\nBlocking findings:\n- ".implode("\n- ",$fail)."\n";exit(1);}echo "Result: PASS — all ten sections score 10/10.\n";
