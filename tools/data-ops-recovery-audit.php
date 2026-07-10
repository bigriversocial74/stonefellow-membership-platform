<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$read=static function(string $path) use ($root): string { $file=$root.'/'.$path; return is_file($file)?(string)file_get_contents($file):''; };
$checks=[];
$add=static function(string $section,string $label,bool $ok) use (&$checks): void { $checks[]=['section'=>$section,'label'=>$label,'ok'=>$ok]; };
$contains=static function(string $path,array $needles) use ($read): bool { $body=$read($path);if($body==='')return false;foreach($needles as $needle)if(!str_contains($body,$needle))return false;return true; };

$add('Migration Ledger','Dynamic migration discovery',$contains('includes/data_ops_recovery.php',['glob(','sf_dor_migration_drift','checksum_mismatch','orphaned_record']));
$add('Protected Repair','Repair is disabled by default and advisory locked',$contains('includes/data_ops_recovery.php',['SF_ALLOW_SCHEMA_REPAIR','GET_LOCK','maintenance mode','REPAIR ']));
$add('Backup Evidence','Artifact evidence requires digest size and time',$contains('includes/data_ops_recovery.php',['artifact_sha256','size_bytes','created_at','sf_dor_backup_gate']));
$add('Restore Readiness','Production restore waivers are blocked',$contains('includes/data_ops_recovery.php',['Production restore checks cannot be waived','sf_dor_update_restore_check']));
$add('Release Gate','Release advancement requires backup tasks revision and rollback',$contains('includes/data_ops_recovery.php',['sf_dor_release_gate','40-character','rollback notes','Backup:']));
$add('Relational Integrity','Orphan and engine checks are present',$contains('includes/data_ops_recovery.php',['sf_dor_orphan_checks','InnoDB','FOREIGN_KEY_CHECKS']));
$add('Operations Dashboard','Admin operations dashboard is installed',$contains('admin/operations-recovery.php',['Data integrity, operations & recovery audit v1','sf_dor_operations_checks','Migration Ledger']));
$add('Admin Enforcement','Migration backup and release pages use protected runtime',$contains('admin/migration-checker.php',['sf_dor_schema_repair','Protected Repair'])&&$contains('admin/backups.php',['sf_dor_update_backup_run','Artifact SHA-256'])&&$contains('admin/releases.php',['sf_dor_save_release','Gate Result']));
$add('Deployment Preflight','Operations and certification failures block preflight',$contains('deploy/preflight.php',['sf_dor_operations_checks','Operations & Recovery Score','Staging Launch Certification','Whole Platform Source Certification Score','BLOCKED: Resolve all source, data, backup, release, recovery']));
$add('Regression Gates','Smoke and static audits run in CI',$contains('.github/workflows/code-audit.yml',['data_ops_recovery_smoke.php','data-ops-recovery-audit.php'])&&$contains('.env.example',['SF_ALLOW_SCHEMA_REPAIR=0']));

$passed=count(array_filter($checks,static fn(array $c): bool=>$c['ok']));$total=count($checks);$score=$total?(int)round($passed/$total*100):0;
echo "Stonefellow Data Integrity, Operations & Recovery Audit v1\n";
foreach($checks as $check)echo ($check['ok']?'PASS':'FAIL').': '.$check['section'].' — '.$check['label']."\n";
echo "Score: {$passed}/{$total} (".number_format($score/10,1)."/10)\n";
exit($passed===$total?0:1);
