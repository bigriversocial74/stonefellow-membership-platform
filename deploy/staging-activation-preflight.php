<?php

declare(strict_types=1);

putenv('SF_SKIP_INSTALL_REDIRECT=1');
require_once __DIR__.'/../includes/staging_activation.php';
$summary=sf_sa_activation_summary();
$checks=[];
$checks[]=['label'=>'Migration 025 installed','ok'=>(bool)($summary['schema_ready']??false)];
$checks[]=['label'=>'100% staging activation','ok'=>!empty($summary['latest_run'])&&($summary['latest_run']['run_status']??'')==='passed'&&(float)($summary['latest_run']['overall_score']??0)===100.0];
$checks[]=['label'=>'Frozen release candidate','ok'=>!empty($summary['latest_candidate'])&&($summary['latest_candidate']['candidate_status']??'')==='frozen'];
$checks[]=['label'=>'Exact release commit match','ok'=>(bool)($summary['candidate_matches_release']??false)];
$ok=!array_filter($checks,static fn($c)=>empty($c['ok']));
$payload=['ok'=>$ok,'stage'=>'staging_activation_release_candidate','checks'=>$checks,'release_commit'=>$summary['release_commit']??'','activation_run_key'=>$summary['latest_run']['run_key']??null,'candidate_key'=>$summary['latest_candidate']['candidate_key']??null,'candidate_artifact_sha256'=>$summary['latest_candidate']['artifact_sha256']??null];
if(PHP_SAPI==='cli'){foreach($checks as$c)echo($c['ok']?'PASS':'FAIL').' '.$c['label'].PHP_EOL;echo json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;exit($ok?0:1);}header('Content-Type: application/json; charset=utf-8');http_response_code($ok?200:503);echo json_encode($payload,JSON_UNESCAPED_SLASHES);
