<?php
declare(strict_types=1);
putenv('SF_SKIP_INSTALL_REDIRECT=1');
putenv('SF_ENV=testing');
require_once dirname(__DIR__).'/includes/config.php';

$failures=[];
$assert=static function(bool $condition,string $message) use (&$failures): void { if(!$condition)$failures[]=$message; };
$assert(sf_security_safe_redirect('member.php?tab=billing','fallback.php')==='member.php?tab=billing','Relative redirects should be allowed.');
$assert(sf_security_safe_redirect('https://evil.example','fallback.php')==='fallback.php','Absolute redirects should be rejected.');
$assert(sf_security_safe_redirect('//evil.example','fallback.php')==='fallback.php','Protocol-relative redirects should be rejected.');
$assert(sf_url('admin/index.php')!=='','URL helper should return a path.');
$assert(str_contains(sf_asset('images/example.png'),'assets/images/example.png'),'Asset helper should normalize the assets prefix.');
$limit1=sf_security_session_rate_limit('smoke',1,60);$limit2=sf_security_session_rate_limit('smoke',1,60);
$assert($limit1['allowed']===true && $limit2['allowed']===false,'Session rate limiter should enforce its limit.');
if($failures){foreach($failures as $failure)fwrite(STDERR,"FAIL: {$failure}\n");exit(1);}echo "Security smoke tests passed.\n";
