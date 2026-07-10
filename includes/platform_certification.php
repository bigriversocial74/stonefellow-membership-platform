<?php

declare(strict_types=1);

require_once __DIR__ . '/production_launch.php';
require_once __DIR__ . '/live_commerce.php';

if (defined('SF_PLATFORM_CERTIFICATION_LOADED')) return;
define('SF_PLATFORM_CERTIFICATION_LOADED', true);

function sf_pc_root(): string { return realpath(__DIR__.'/..') ?: dirname(__DIR__); }
function sf_pc_file(string $path): string { $file=sf_pc_root().'/'.ltrim($path,'/');return is_file($file)?(string)file_get_contents($file):''; }
function sf_pc_has(string $path,array $markers=[]): bool { $body=sf_pc_file($path);if($body==='')return false;foreach($markers as $marker)if(stripos($body,(string)$marker)===false)return false;return true; }
function sf_pc_check(string $section,string $label,bool $ok,string $detail,string $url=''): array { return ['section'=>$section,'label'=>$label,'status'=>$ok?'pass':'fail','detail'=>$detail,'url'=>$url]; }

function sf_pc_static_sections(): array {
    return [
      'Core Platform & Security'=>[
        ['tests/security_smoke.php',['Security smoke']],
        ['tools/code-audit.php',['Runtime & configuration','Overall:']],
        ['.github/workflows/code-audit.yml',['PHP syntax validation','Security smoke tests','Full static code audit']],
      ],
      'AI & Agentic Governance'=>[
        ['tests/agentic_governance_smoke.php',['PASS']],
        ['tools/agentic-audit.php',['Agentic']],
        ['tests/ai_staging_certification_smoke.php',['PASS']],
        ['tools/ai-staging-certification-audit.php',['certification']],
      ],
      'Revenue, Membership & Media'=>[
        ['tests/revenue_membership_media_access_smoke.php',['PASS']],
        ['tools/revenue-membership-media-access-audit.php',['Revenue']],
        ['docs/REVENUE_MEMBERSHIP_MEDIA_ACCESS_AUDIT_V1.md',['10/10']],
        ['tests/live_commerce_stripe_connect_smoke.php',['Live commerce']],
        ['tools/live-commerce-stripe-connect-audit.php',['Stripe Connect','all ten sections score 10/10']],
        ['docs/LIVE_COMMERCE_STRIPE_CONNECT_V1.md',['Final static score: 10/10']],
      ],
      'Data, Operations & Recovery'=>[
        ['tests/data_ops_recovery_smoke.php',['PASS']],
        ['tools/data-ops-recovery-audit.php',['Data Integrity']],
        ['docs/DATA_INTEGRITY_OPERATIONS_RECOVERY_AUDIT_V1.md',['10/10']],
      ],
      'Front-End & Accessibility'=>[
        ['tests/frontend_quality_smoke.php',['PASS']],
        ['tools/frontend-quality-audit.php',['Front-End']],
        ['docs/FRONTEND_ACCESSIBILITY_PERFORMANCE_AUDIT_V1.md',['10/10']],
      ],
      'Authentication, Privacy & Abuse'=>[
        ['tests/auth_privacy_abuse_smoke.php',['PASS']],
        ['tools/auth-privacy-abuse-audit.php',['Authentication']],
        ['docs/AUTH_PRIVACY_ABUSE_AUDIT_V1.md',['10/10']],
      ],
      'Publishing, Search & Moderation'=>[
        ['tests/content_integrity_smoke.php',['PASS']],
        ['tools/content-integrity-audit.php',['Content Publishing']],
        ['docs/CONTENT_PUBLISHING_SEARCH_MODERATION_AUDIT_V1.md',['10/10']],
      ],
      'Delivery, Scheduler & Webhooks'=>[
        ['tests/delivery_integrity_smoke.php',['PASS']],
        ['tools/delivery-integrity-audit.php',['Notifications']],
        ['docs/EMAIL_NOTIFICATIONS_SCHEDULER_DELIVERY_AUDIT_V1.md',['10/10']],
      ],
      'Staging Certification & Integration'=>[
        ['tests/staging_launch_certification_smoke.php',['PASS']],
        ['tools/staging-launch-certification-audit.php',['Launch Certification']],
        ['tests/staging_integration_matrix_smoke.php',['PASS']],
        ['tools/staging-integration-matrix-audit.php',['Integration Matrix']],
      ],
      'Release Candidate & Production Launch'=>[
        ['tests/production_launch_smoke.php',['PASS']],
        ['tools/production-launch-audit.php',['Production Launch']],
        ['deploy/preflight.php',['Production Launch Promotion Score','SF_RELEASE_COMMIT_SHA']],
        ['docs/PRODUCTION_LAUNCH_PROMOTION_V1.md',['10/10']],
      ],
    ];
}

function sf_pc_static_checks(): array {
    $checks=[];foreach(sf_pc_static_sections() as $section=>$files){foreach($files as [$path,$markers]){$ok=sf_pc_has($path,$markers);$checks[]=sf_pc_check($section,$path,$ok,$ok?'Required file and certification markers are present.':'File or required certification marker is missing.',$path);}}return $checks;
}
function sf_pc_score(array $checks): int { if(!$checks)return 0;$passed=count(array_filter($checks,static fn($c)=>($c['status']??'')==='pass'));return (int)round($passed/count($checks)*100); }
function sf_pc_section_summary(array $checks): array { $out=[];foreach($checks as $check){$section=$check['section'];$out[$section]['section']=$section;$out[$section]['checks'][]=$check;}foreach($out as &$section){$section['total']=count($section['checks']);$section['passed']=count(array_filter($section['checks'],static fn($c)=>$c['status']==='pass'));$section['failed']=$section['total']-$section['passed'];$section['score']=$section['total']?(int)round($section['passed']/$section['total']*100):0;}unset($section);return array_values($out); }

function sf_pc_required_tables(): array {
    return ['ai_staging_certification_runs','staging_launch_certification_runs','staging_launch_certification_checks','staging_launch_certification_evidence','staging_integration_executions','staging_integration_assertions','staging_integration_events','production_launch_promotions','production_launch_approvals','production_launch_checks','production_launch_events','commerce_merchants','merchant_payment_accounts','merch_checkouts','inventory_reservations'];
}
function sf_pc_operational_checks(): array {
    $checks=[];$production=sf_dor_env()==='production';$tables=[];foreach(sf_pc_required_tables() as $table)if(!sf_admin_table_exists($table))$tables[]=$table;$checks[]=sf_pc_check('Deployment','Certification and commerce SQL installed',!$tables,$tables?'Missing tables: '.implode(', ',$tables):'All certification, promotion, and commerce tables are installed.');
    $commerce=sf_commerce_provider_summary();$checks[]=sf_pc_check('Commerce','Stripe Connect merchant account',!empty($commerce['checkout_ready']),!empty($commerce['checkout_ready'])?'Stripe connected account can accept charges and payouts.':'Stripe platform credentials, onboarding, charges, or payouts are incomplete.','admin/payment-gateways.php');
    $ai=null;if(sf_admin_table_exists('ai_staging_certification_runs'))$ai=sf_admin_fetch_one("SELECT * FROM ai_staging_certification_runs WHERE run_status='passed' AND overall_score=100 ORDER BY completed_at DESC,id DESC LIMIT 1");$checks[]=sf_pc_check('AI','AI staging certification',$ai!==null,$ai?'100% AI staging certificate is available.':'No 100% AI staging certificate exists.','admin/ai-staging-certification.php');
    $slc=sf_slc_latest_passed();$checks[]=sf_pc_check('Staging','Launch certification',$slc!==null,$slc?'Passed launch certificate for '.$slc['target_commit_sha'].'.':'No 100% staging launch certificate exists.','admin/staging-launch-certification.php');
    $coverage=$slc?sf_prod_scenario_coverage((int)$slc['id']):['ok'=>false,'missing'=>array_keys(sf_sim_catalog()),'passed'=>[],'required'=>array_keys(sf_sim_catalog())];$checks[]=sf_pc_check('Staging','Integration scenario matrix',!empty($coverage['ok']),!empty($coverage['ok'])?count($coverage['passed']).' required scenarios passed.':'Missing passed scenarios: '.implode(', ',array_slice($coverage['missing'],0,6)).'.','admin/staging-integration-matrix.php');
    $promotion=sf_prod_latest_verified();if(!$promotion&&sf_prod_ready())$promotion=sf_admin_fetch_one("SELECT * FROM production_launch_promotions WHERE promotion_status IN ('approved','deploying','deployed') ORDER BY created_at DESC,id DESC LIMIT 1");$promotionOk=$promotion!==null;$checks[]=sf_pc_check('Production','Production launch promotion',$promotionOk,$promotion?'Promotion '.$promotion['promotion_key'].' status '.$promotion['promotion_status'].'.':'No approved or verified production promotion exists.','admin/production-launch.php');
    $backup=sf_dor_latest_verified_backup(24);$checks[]=sf_pc_check('Recovery','Fresh verified backup',$backup!==null,$backup?'Backup '.$backup['run_key'].' is verified and current.':'No fully verified backup from the last 24 hours.','admin/backups.php');
    $release=sf_rel_releases(1)[0]??null;$releaseGate=$release?sf_dor_release_gate((int)$release['id']):['ok'=>false,'reasons'=>['No release record.']];$checks[]=sf_pc_check('Release','Deployment release gate',!empty($releaseGate['ok']),!empty($releaseGate['ok'])?'Release gate passes.':implode(' ',array_slice($releaseGate['reasons'],0,4)),'admin/releases.php');
    $releaseSha=strtolower(trim((string)(getenv('SF_RELEASE_COMMIT_SHA')?:'')));$shaOk=preg_match('/^[a-f0-9]{40}$/',$releaseSha)===1;$checks[]=sf_pc_check('Production','Exact deployed commit configured',!$production||$shaOk,$shaOk?'SF_RELEASE_COMMIT_SHA is configured.':($production?'Production requires the exact 40-character deployed commit.':'Configure the release SHA before production promotion.'));
    return $checks;
}
function sf_pc_operational_status(array $checks): string { $score=sf_pc_score($checks);if($score===100)return'operationally_certified';if($score>=70)return'evidence_in_progress';return'not_certified'; }
function sf_pc_summary(): array { $static=sf_pc_static_checks();$operational=sf_pc_operational_checks();return ['static_score'=>sf_pc_score($static),'static_sections'=>sf_pc_section_summary($static),'static_checks'=>$static,'operational_score'=>sf_pc_score($operational),'operational_status'=>sf_pc_operational_status($operational),'operational_checks'=>$operational,'latest_certificate'=>sf_slc_latest_passed(),'latest_promotion'=>sf_prod_latest_verified()]; }
?>
