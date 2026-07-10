<?php

declare(strict_types=1);
putenv('SF_SKIP_INSTALL_REDIRECT=1');
putenv('SF_ENV=testing');
putenv('SF_APP_KEY=delivery-smoke-app-key-123456789012345678901234567890');
putenv('SF_HASH_SALT=delivery-smoke-hash-key-123456789012345678901234567890');
putenv('SF_NOTIFICATION_RETRY_BASE_SECONDS=60');
putenv('SF_NOTIFICATION_WEBHOOK_SECRET=delivery-webhook-secret-123456789012345678901234567890');
$_SERVER['HTTP_HOST']='stonefellow.test';
require_once __DIR__.'/../includes/delivery_integrity.php';
require_once __DIR__.'/../includes/notifications.php';
require_once __DIR__.'/../includes/ops_scheduler_messaging.php';

$fail=[];$assert=static function(bool $ok,string $message)use(&$fail):void{if(!$ok)$fail[]=$message;};
$assert(sf_delivery_clean_header("Subject\r\nBcc: victim@example.com")==='Subject Bcc: victim@example.com','Header cleaner should remove CR/LF injection.');
$assert(sf_delivery_safe_email("bad@example.com\r\nBcc:x@example.com")==='','Email validation should reject header injection.');
$assert(sf_delivery_safe_email('member@example.com')==='member@example.com','Valid email should pass.');
$assert(sf_delivery_mask_email('member@example.com')==='m*****@example.com','Email masking should hide the local part.');
$assert(sf_delivery_backoff_seconds(1)===60&&sf_delivery_backoff_seconds(3)===240,'Retry backoff should be exponential.');

$dirty='<h1 onclick="alert(1)">Hi</h1><script>alert(1)</script><form><input></form><a href="javascript:alert(1)">x</a>';
$clean=sf_delivery_sanitize_email_html($dirty);
$assert(stripos($clean,'<script')===false&&stripos($clean,'onclick')===false&&stripos($clean,'<form')===false&&stripos($clean,'javascript:')===false,'Email HTML should remove active content.');
$valid=sf_delivery_validate_template(['template_key'=>'safe_notice','subject'=>'Hello {{recipient_name}}','html_body'=>'<p>Hello {{recipient_name}}</p>','text_body'=>'Hello {{recipient_name}}','variables_json'=>'["recipient_name"]']);
$assert($valid['ok']===true,'Declared template variables should validate.');
$invalid=sf_delivery_validate_template(['template_key'=>'unsafe_notice','subject'=>'Hello {{unknown}}','html_body'=>'<p>Hi</p>','variables_json'=>'[]']);
$assert($invalid['ok']===false,'Undeclared template variables should fail.');

$raw='{"event_id":"evt_1"}';$signature=hash_hmac('sha256',$raw,(string)getenv('SF_NOTIFICATION_WEBHOOK_SECRET'));
$assert(sf_delivery_webhook_signature_valid('generic',$raw,$signature),'Correct webhook HMAC should pass.');
$assert(!sf_delivery_webhook_signature_valid('generic',$raw,str_repeat('0',64)),'Incorrect webhook HMAC should fail.');
$assert(sf_sched_next_run('manual','08:00:00')===null,'Manual jobs must never become automatically due.');
$daily=sf_sched_next_run('daily','23:59:59',new DateTimeImmutable('2026-07-10 10:00:00'));
$assert($daily==='2026-07-10 23:59:59','Daily next run should select the next future occurrence.');
$assert(sf_delivery_transactional_type('billing')&&!sf_delivery_transactional_type('member_message'),'Transactional classification should preserve essential notices and allow preference-controlled campaigns.');

$root=dirname(__DIR__);$markers=[
 'includes/notifications.php'=>['idempotency_key','sf_delivery_advisory_lock','SF_NOTIFICATION_MAX_ATTEMPTS','sf_delivery_backoff_seconds','honors_preferences','SF_ALLOW_LOG_MAIL_PROVIDER'],
 'includes/ops_scheduler_messaging.php'=>["frequency<>'manual'",'sf_delivery_advisory_lock','sf_msg_log_status','honors_preferences','campaign-recipient-'],
 'api/ops-scheduler.php'=>['SF_OPS_SCHEDULER_SECRET','HTTP_X_STONEFELLOW_SCHEDULER_SECRET','csrf_failed'],
 'api/member-notices.php'=>['admin.members.manage','HTTP_X_CSRF_TOKEN','method_not_allowed'],
 'api/notification-webhook.php'=>['sf_delivery_webhook_signature_valid','provider_event_id','duplicate','[redacted]'],
 'admin/email-templates.php'=>['sandbox=""','srcdoc','sanitization'],
 'admin/notifications.php'=>['failed or canceled','Reset attempts','privacy-reduced logs'],
 'admin/member-messaging.php'=>['preference-skipped','Honor member channel preferences','immutable'],
];
foreach($markers as $file=>$needles){$body=(string)file_get_contents($root.'/'.$file);foreach($needles as $needle)$assert(stripos($body,$needle)!==false,$file.' should contain '.$needle.'.');}
if($fail){fwrite(STDERR,"Delivery integrity smoke failures:\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "Delivery integrity smoke: PASS\n";
