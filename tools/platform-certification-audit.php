<?php

declare(strict_types=1);
putenv('SF_SKIP_INSTALL_REDIRECT=1');
require_once dirname(__DIR__).'/includes/platform_certification.php';
$checks=sf_pc_static_checks();$sections=sf_pc_section_summary($checks);$fail=[];
echo "Stonefellow Whole-Platform Source Certification v1\n".str_repeat('=',72)."\n";
foreach($sections as $section){$score=(int)round($section['score']/10);echo sprintf("%-42s %d/10 (%d/%d)\n",$section['section'],$score,$section['passed'],$section['total']);if($section['score']!==100)$fail[]=$section['section'].' is '.$section['score'].'%.';}
$root=dirname(__DIR__);$framework=[
 'includes/platform_certification.php'=>['sf_pc_static_sections','sf_pc_operational_checks','sf_pc_summary'],
 'admin/platform-certification.php'=>['Source / CI Score','Operational Certification','Certification Boundary'],
 'deploy/preflight.php'=>['Whole Platform Source Certification Score','platformSourceScore'],
 'tests/platform_certification_smoke.php'=>['sf_pc_score($checks)===100'],
 '.github/workflows/code-audit.yml'=>['platform_certification_smoke.php','platform-certification-audit.php'],
];
foreach($framework as $file=>$markers){$body=is_file($root.'/'.$file)?(string)file_get_contents($root.'/'.$file):'';foreach($markers as $marker)if($body===''||stripos($body,$marker)===false)$fail[]=$file.' missing '.$marker.'.';}
$overall=sf_pc_score($checks);echo str_repeat('-',72)."\nOverall source score: ".number_format($overall/10,1)."/10\n";
if($fail){echo "\nBlocking findings:\n- ".implode("\n- ",$fail)."\n";exit(1);}echo "Result: PASS — all cumulative source sections score 10/10.\n";
