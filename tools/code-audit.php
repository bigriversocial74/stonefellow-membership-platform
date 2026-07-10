<?php
declare(strict_types=1);
$root=dirname(__DIR__);
function text(string $path): string { global $root; $file=$root.'/'.$path; return is_file($file)?(string)file_get_contents($file):''; }
function ok(string $label,bool $pass): array { return ['label'=>$label,'pass'=>$pass]; }
function has(string $path,string $needle): bool { return str_contains(text($path),$needle); }
$sections=[
 'Runtime & configuration'=>[
  ok('Security bootstrap exists',is_file($root.'/includes/security.php')),
  ok('Strict session cookies',has('includes/security.php','session_set_cookie_params')),
  ok('Host validation and security headers',has('includes/security.php','sf_security_allowed_hosts')&&has('includes/security.php','Strict-Transport-Security')),
  ok('Subdirectory-safe URL helpers',has('includes/config.php','sf_base_path')),
 ],
 'Database safety'=>[
  ok('Native prepared statements',has('includes/db.php','PDO::ATTR_EMULATE_PREPARES => false')),
  ok('Multi-statements disabled',has('includes/db.php','MYSQL_ATTR_MULTI_STATEMENTS')),
  ok('DSN inputs validated',has('includes/db.php','unsafe DSN characters')),
  ok('No static client-hash fallback',has('includes/db.php','SF_APP_KEY')),
 ],
 'Authentication & sessions'=>[
  ok('Login throttling guard',has('includes/runtime_guards.php','login_attempts')),
  ok('Production password floor',has('includes/runtime_guards.php','Passwords must contain at least')),
  ok('Remember tokens rotate',has('includes/runtime_guards.php','remember-token rotation')),
  ok('Public first-admin creation blocked',has('includes/runtime_guards.php','first owner account')),
 ],
 'Admin authorization'=>[
  ok('Admin routes fail closed',has('includes/runtime_guards.php','sf_runtime_guard_admin')),
  ok('Active admin role required',has('includes/runtime_guards.php',"role,status FROM users")),
  ok('Admin POST CSRF enforced',has('includes/runtime_guards.php','admin security token')),
  ok('Preview is explicit and nonproduction',has('includes/runtime_guards.php','SF_ALLOW_ADMIN_PREVIEW')),
 ],
 'API & webhook integrity'=>[
  ok('Payment webhook is POST-only',has('api/payment-webhook.php',"sf_security_require_method('POST')")),
  ok('Webhook bodies are bounded',has('api/payment-webhook.php','1048576')),
  ok('Invalid signatures return 401',has('api/payment-webhook.php',"'invalid_signature'],401")),
  ok('Billing events are idempotent',has('api/billing-webhook.php','INSERT IGNORE')),
 ],
 'Billing & commerce'=>[
  ok('Sandbox activation blocked in production',has('includes/runtime_guards.php','SF_ALLOW_SANDBOX_SUBSCRIPTIONS')),
  ok('PayPal fails closed pending verification',has('api/payment-webhook.php','paypal_webhook_verification_not_configured')),
  ok('Provider checkout runtime is transactional',has('includes/billing_provider_runtime.php','beginTransaction')),
  ok('Entitlements created after paid checkout',has('includes/billing_provider_runtime.php','sf_billing_create_entitlement_grants')),
 ],
 'Media & uploads'=>[
  ok('Upload MIME allowlists',has('includes/admin_uploads.php','FILEINFO_MIME_TYPE')),
  ok('Extension and size allowlists',has('includes/admin_uploads.php','max_bytes')),
  ok('Randomized upload names',has('includes/admin_uploads.php','random_bytes(20)')),
  ok('Signed media delivery retained',has('includes/media_delivery.php','hash_hmac')),
 ],
 'Installer & deployment'=>[
  ok('Installer CSRF guard',has('includes/installer.php','sf_install_csrf_token')),
  ok('Installer lock enforced',has('includes/installer.php','sf_install_is_locked')),
  ok('Owner password floor',has('includes/installer.php','at least 12 characters')),
  ok('Production environment template',has('.env.example','SF_ALLOWED_HOSTS')),
 ],
 'QA & observability'=>[
  ok('Request IDs emitted',has('includes/security.php','X-Request-Id')),
  ok('Security smoke tests exist',is_file($root.'/tests/security_smoke.php')),
  ok('Full PHP lint runs in CI',has('.github/workflows/code-audit.yml','php -l')),
  ok('Static audit runs in CI',has('.github/workflows/code-audit.yml','tools/code-audit.php')),
 ],
 'Maintainability & documentation'=>[
  ok('Cross-cutting guards isolated',is_file($root.'/includes/runtime_guards.php')),
  ok('Upload policy isolated',is_file($root.'/includes/admin_uploads.php')),
  ok('Audit criteria are executable',is_file($root.'/tools/code-audit.php')),
  ok('Audit report is versioned',is_file($root.'/docs/CODE_AUDIT_FULL_PLATFORM_V1.md')),
 ],
];
$all=true;$scores=[];
foreach($sections as $name=>$checks){$passed=count(array_filter($checks,fn($c)=>$c['pass']));$score=round($passed/count($checks)*10,1);$scores[]=$score;if($score<10)$all=false;echo "\n{$name}: {$score}/10\n";foreach($checks as $c)echo ($c['pass']?'  [PASS] ':'  [FAIL] ').$c['label']."\n";}
echo "\nOverall: ".round(array_sum($scores)/count($scores),1)."/10\n";exit($all?0:1);
