<?php

declare(strict_types=1);
putenv('SF_SKIP_INSTALL_REDIRECT=1');
putenv('SF_ENV=testing');
putenv('SF_APP_KEY='.str_repeat('a',64));
putenv('SF_HASH_SALT='.str_repeat('b',40));
require_once __DIR__.'/../includes/platform_certification.php';
$fail=[];$assert=static function(bool $ok,string $message)use(&$fail):void{if(!$ok)$fail[]=$message;};
$sections=sf_pc_static_sections();$checks=sf_pc_static_checks();
$assert(count($sections)===11,'Whole-platform source certification must contain eleven cumulative sections, including licensed installation.');
$assert(count($checks)>=30,'Whole-platform certification should verify all prior phase artifacts.');
$assert(sf_pc_score($checks)===100,'All committed source certification artifacts must score 100%.');
$summary=sf_pc_section_summary($checks);foreach($summary as $section)$assert((int)$section['score']===100,$section['section'].' must score 100%.');
$operational=sf_pc_operational_checks();$assert(count($operational)>=8,'Operational certification should cover license, SQL, AI, staging, scenarios, production, backup, release, and commit.');
$assert(in_array(sf_pc_operational_status($operational),['operationally_certified','evidence_in_progress','not_certified'],true),'Operational status must be bounded.');
$root=dirname(__DIR__);$markers=[
 'includes/platform_certification.php'=>['sf_pc_static_sections','sf_pc_operational_checks','operationally_certified','sf_prod_latest_verified','Product license receipt and entitlement'],
 'admin/platform-certification.php'=>['Source / CI Score','Operational Score','Certification Boundary','Run Preflight'],
 'admin/operations-recovery.php'=>['admin/platform-certification.php','Certification'],
 'deploy/preflight.php'=>['Whole Platform Source Certification Score','Source certification is 10/10'],
 '.github/workflows/code-audit.yml'=>['platform_certification_smoke.php','platform-certification-audit.php'],
];
foreach($markers as $file=>$needles){$body=(string)file_get_contents($root.'/'.$file);foreach($needles as $needle)$assert(stripos($body,$needle)!==false,$file.' missing '.$needle);}
if($fail){fwrite(STDERR,"Platform certification smoke failures:\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "Platform certification smoke: PASS\n";
