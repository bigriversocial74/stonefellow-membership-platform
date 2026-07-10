<?php
declare(strict_types=1);
putenv('SF_SKIP_INSTALL_REDIRECT=1');
putenv('SF_ENV=testing');
putenv('SF_ALLOW_SCHEMA_REPAIR=0');
require_once dirname(__DIR__).'/includes/data_ops_recovery.php';

$failures=[];
$assert=static function(bool $condition,string $message) use (&$failures): void { if(!$condition)$failures[]=$message; };

$files=sf_dor_migration_files();
$keys=array_map(static fn(array $row): string=>(string)$row['key'],$files);
$assert(in_array('base',$keys,true),'Base schema should be discovered.');
$assert((bool)array_filter($keys,static fn(string $key): bool=>(bool)preg_match('/^\d{3}$/',$key)),'Numbered migrations should be discovered.');
$assert((bool)preg_match('/^\d{3}$/',sf_dor_latest_migration_key()),'Latest migration key should be numeric.');
$assert(sf_dor_score([sf_dor_check('Test','one','pass','Pass'),sf_dor_check('Test','two','fail','Fail')])===50,'Audit scoring should weight pass and fail deterministically.');
$assert(sf_dor_artifact_evidence(['manifest_json'=>json_encode(['artifact'=>['sha256'=>'bad','size_bytes'=>0,'created_at'=>'']])])['ok']===false,'Malformed backup evidence should be rejected.');
$goodHash=str_repeat('a',64);
$assert(sf_dor_artifact_evidence(['manifest_json'=>json_encode(['artifact'=>['sha256'=>$goodHash,'size_bytes'=>100,'created_at'=>'2026-07-10T00:00:00Z']])])['ok']===true,'Complete backup evidence should be accepted.');
putenv('SF_ENV=production');
$waive=sf_dor_update_restore_check(0,'waived','test');
$assert(empty($waive['ok']),'Production restore checks must not be waivable.');
$releaseWaive=sf_dor_update_release_task(0,'waived','test');
$assert(empty($releaseWaive['ok']),'Production release tasks must not be waivable.');
putenv('SF_ENV=testing');
$assert(sf_dor_env()==='testing','Environment helper should reflect the current environment.');

if($failures){foreach($failures as $failure)fwrite(STDERR,"FAIL: {$failure}\n");exit(1);}echo "Data integrity operations recovery smoke tests passed.\n";
