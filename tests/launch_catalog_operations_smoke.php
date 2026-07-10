<?php

declare(strict_types=1);
$root=dirname(__DIR__);$fail=[];
$files=['includes/catalog_operations.php','admin/catalog-operations.php','admin/catalog-transfer.php','api/catalog-export.php','api/catalog-operations-tick.php','database/migrations/024_launch_content_catalog_operations.sql','docs/LAUNCH_CONTENT_CATALOG_OPERATIONS_V1.md','tools/launch-catalog-operations-audit.php'];
foreach($files as$f)if(!is_file($root.'/'.$f))$fail[]='Missing '.$f;
$runtime='';foreach(['includes/catalog_operations.php','includes/catalog_operations_core.php','includes/catalog_operations_readiness.php','includes/catalog_operations_actions.php','includes/catalog_operations_transfer.php'] as $runtimeFile)$runtime.=(string)@file_get_contents($root.'/'.$runtimeFile);
$migration=(string)@file_get_contents($root.'/database/migrations/024_launch_content_catalog_operations.sql');
$runner=(string)@file_get_contents($root.'/api/catalog-operations-tick.php');
$workflow=(string)@file_get_contents($root.'/.github/workflows/code-audit.yml');
foreach(['series','season','episode','video','album','song','character','product','plan'] as$type)if(strpos($runtime,"'{$type}'")===false)$fail[]='Missing catalog type '.$type;
foreach(['sf_lco_scan','sf_lco_store_snapshot','sf_lco_save_seo','sf_lco_publish_ready','sf_lco_archive_samples','sf_lco_rollback_batch','sf_lco_transfer_commit','sf_lco_export_csv','sf_lco_run_due'] as$marker)if(strpos($runtime,$marker)===false)$fail[]='Missing runtime marker '.$marker;
foreach(['catalog_readiness_snapshots','catalog_readiness_items','catalog_publication_batches','catalog_publication_actions','catalog_sample_flags','catalog_seo_metadata','catalog_operation_events'] as$table)if(strpos($migration,$table)===false)$fail[]='Missing migration table '.$table;
foreach(['hash_equals','SF_CATALOG_RUNNER_SECRET','X_SF_IDEMPOTENCY_KEY','POST required'] as$marker)if(strpos($runner,$marker)===false)$fail[]='Missing runner control '.$marker;
if(strpos($workflow,'Launch content catalog operations smoke tests')===false)$fail[]='Workflow smoke gate missing.';
if($fail){fwrite(STDERR,"Launch catalog smoke FAILED\n- ".implode("\n- ",$fail)."\n");exit(1);}echo"Launch content catalog operations smoke tests PASS\n";
