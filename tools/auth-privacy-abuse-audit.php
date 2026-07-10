<?php

declare(strict_types=1);
$root=dirname(__DIR__);
$read=static fn(string $file): string=>is_file($root.'/'.$file)?(string)file_get_contents($root.'/'.$file):'';
$sections=[
  'Password policy'=>[
    ['includes/auth_hardening.php',['SF_PASSWORD_MIN_LENGTH','sf_auth_password_policy_error','Password must not contain your email name','less common password']],
    ['signup.php',['sf_auth_password_min_length','minlength=','maxlength="4096"']],
    ['reset-password.php',['sf_auth_secure_reset_apply','sf_auth_password_min_length']],
  ],
  'Login throttling and abuse'=>[
    ['includes/auth_hardening.php',['sf_auth_hardening_login_allowed','SF_LOGIN_FAILURE_LIMIT','SF_LOGIN_WINDOW_SECONDS','Too many sign-in attempts']],
    ['signin.php',['sf_auth_secure_login']],
    ['tests/auth_privacy_abuse_smoke.php',['sf_security_session_rate_limit']],
  ],
  'Session lifecycle'=>[
    ['includes/auth_hardening.php',['SF_AUTH_IDLE_SESSION_SECONDS','SF_AUTH_ABSOLUTE_SESSION_SECONDS','sf_auth_fingerprint','session_regenerate_id']],
    ['includes/auth_logout_hardening.php',['admin_security_sessions','session_regenerate_id(true)','sf_session_key']],
    ['logout.php',['sf_auth_secure_logout']],
  ],
  'Remember token policy'=>[
    ['includes/auth_hardening.php',['SF_ALLOW_REMEMBER_ME','user_auth_tokens','unset($_COOKIE']],
    ['signin.php',['SF_ALLOW_REMEMBER_ME','Keep me signed in']],
    ['.env.example',['SF_ALLOW_REMEMBER_ME=0']],
  ],
  'Password reset containment'=>[
    ['includes/auth_hardening.php',['sf_auth_secure_reset_create','SF_SHOW_DEVELOPMENT_RESET_LINK','sf_auth_secure_reset_apply','UPDATE user_auth_tokens']],
    ['forgot-password.php',['If that account exists','development-only reset link']],
    ['reset-password.php',['revokes remember tokens','used only once']],
  ],
  'Registration and owner bootstrap'=>[
    ['includes/auth_hardening.php',['SF_ALLOW_PUBLIC_FIRST_ADMIN','protected installer','Unable to create an account with those details']],
    ['signup.php',['sf_auth_secure_register','Production owner access']],
    ['.env.example',['SF_ALLOW_PUBLIC_FIRST_ADMIN=0']],
  ],
  'Admin authorization'=>[
    ['includes/header.php',['sf_sec_route_guard','sf_require_admin']],
    ['includes/admin_security.php',['sf_sec_route_permission','admin.settings.manage','permission_denied']],
    ['includes/admin_security.php',['admin.billing.manage','admin.members.manage','admin.ops.manage','admin.content.manage']],
  ],
  'Privileged session and role safety'=>[
    ['includes/admin_security.php',['revoked_admin_session_rejected','last_super_admin_removal_blocked','is_system']],
    ['includes/admin_security.php',['sf_sec_owner_admin_id','status=\'revoked\'']],
    ['includes/auth_hardening.php',['admin_security_sessions','status=\'revoked\'']],
  ],
  'Privacy and data rights'=>[
    ['includes/account_privacy.php',['sf_privacy_export_data','Content-Disposition','retention_note','sf_privacy_deactivate_account']],
    ['account-privacy.php',['Download Account Export','DEACTIVATE','active memberships and orders']],
    ['account.php',['Privacy &amp; Data','account-privacy.php']],
  ],
  'Privacy-safe telemetry and browser policy'=>[
    ['includes/auth_hardening.php',['email_hash','ip_hash','agent_hash','login privacy scrub']],
    ['includes/admin_security.php',['audit-ip|','audit-ua|']],
    ['includes/security_headers_hardening.php',["default-src 'self'","object-src 'none'","frame-ancestors 'self'","form-action 'self'",'SF_CSP_REPORT_ONLY']],
  ],
];
$failures=[];$earned=0;$total=0;
echo "Stonefellow Authentication, Authorization, Privacy & Abuse Audit v1\n".str_repeat('=',68)."\n";
foreach($sections as $section=>$checks){$passed=0;foreach($checks as [$file,$markers]){$total++;$body=$read($file);$missing=[];foreach($markers as $marker)if($body===''||!str_contains($body,$marker))$missing[]=$marker;if(!$missing){$passed++;$earned++;}else{$failures[]=$section.': '.$file.' missing ['.implode(', ',$missing).'].';}}$score=(int)round(($passed/count($checks))*10);echo sprintf("%-43s %d/10 (%d/%d)\n",$section,$score,$passed,count($checks));}
$overall=$total?round(($earned/$total)*10,1):0;echo str_repeat('-',68)."\nOverall score: {$overall}/10\n";
if($failures){echo "\nBlocking findings:\n- ".implode("\n- ",$failures)."\n";exit(1);}echo "Result: PASS — all ten sections score 10/10.\n";
