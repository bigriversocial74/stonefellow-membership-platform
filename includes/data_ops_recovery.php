<?php
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/installer-core.php';
require_once __DIR__ . '/backup_release.php';
require_once __DIR__ . '/monitoring_alerts.php';

function sf_dor_root(): string { return realpath(__DIR__ . '/..') ?: dirname(__DIR__); }
function sf_dor_env(): string { return strtolower(trim((string)(getenv('SF_ENV') ?: 'production'))); }
function sf_dor_is_production(): bool { return sf_dor_env() === 'production'; }
function sf_dor_env_bool(string $key, bool $default = false): bool {
    $value = getenv($key);
    if ($value === false || $value === '') return $default;
    return in_array(strtolower(trim((string)$value)), ['1','true','yes','on'], true);
}
function sf_dor_check(string $section, string $key, string $status, string $label, string $detail = '', int $weight = 1): array {
    return ['section'=>$section,'key'=>$key,'status'=>$status,'label'=>$label,'detail'=>$detail,'weight'=>max(1,$weight)];
}
function sf_dor_score(array $checks): int {
    $points = 0.0; $total = 0.0;
    foreach ($checks as $check) {
        $weight = max(1, (int)($check['weight'] ?? 1));
        $total += $weight;
        $status = (string)($check['status'] ?? 'fail');
        if ($status === 'pass') $points += $weight;
        elseif (in_array($status, ['warn','manual','preview'], true)) $points += $weight * .6;
    }
    return $total > 0 ? (int)round(($points / $total) * 100) : 0;
}
function sf_dor_table_exists(string $table): bool { return sf_br_table_exists($table); }
function sf_dor_columns(string $table): array {
    $pdo = sf_db(); if (!$pdo || !sf_dor_table_exists($table)) return [];
    try { $rows=$pdo->query('SHOW COLUMNS FROM `'.str_replace('`','',$table).'`')->fetchAll(); return array_map(static fn($r)=>(string)$r['Field'],$rows?:[]); }
    catch(Throwable $e){ return []; }
}

function sf_dor_migration_files(): array {
    $root = sf_dor_root();
    $files = [['key'=>'base','path'=>'database/stonefellow_streaming_platform.sql']];
    foreach (glob($root . '/database/migrations/[0-9][0-9][0-9]_*.sql') ?: [] as $path) {
        $name = basename($path);
        $files[] = ['key'=>substr($name,0,3),'path'=>'database/migrations/'.$name];
    }
    usort($files, static function(array $a,array $b): int {
        if ($a['key']==='base') return -1; if ($b['key']==='base') return 1; return strcmp($a['key'],$b['key']);
    });
    foreach ($files as &$file) {
        $absolute = $root . '/' . $file['path'];
        $file['exists'] = is_file($absolute);
        $file['checksum'] = $file['exists'] ? hash_file('sha256',$absolute) : '';
    }
    unset($file);
    return $files;
}
function sf_dor_latest_migration_key(): string {
    $latest='000'; foreach(sf_dor_migration_files() as $file) if($file['key']!=='base' && strcmp($file['key'],$latest)>0)$latest=$file['key']; return $latest;
}
function sf_dor_applied_migrations(): array {
    $out=[]; $pdo=sf_db(); if(!$pdo || !sf_dor_table_exists('schema_migrations')) return $out;
    try { foreach($pdo->query('SELECT migration_key,file_path,checksum_sha256,applied_at FROM schema_migrations')->fetchAll()?:[] as $row)$out[(string)$row['migration_key']]=$row; }
    catch(Throwable $e){ error_log('Stonefellow migration inventory failed: '.$e->getMessage()); }
    return $out;
}
function sf_dor_migration_drift(): array {
    $files=sf_dor_migration_files(); $applied=sf_dor_applied_migrations(); $rows=[]; $seen=[];
    foreach($files as $file){
        $key=(string)$file['key'];$seen[$key]=true;$existing=$applied[$key]??null;$status='pending';$detail='Not recorded as applied.';
        if(!$file['exists']){$status='missing';$detail='SQL file is missing.';}
        elseif($existing){
            if(hash_equals((string)($existing['checksum_sha256']??''),(string)$file['checksum'])){$status='current';$detail='Applied checksum matches repository file.';}
            else{$status='checksum_mismatch';$detail='Applied checksum differs from repository file; never re-run this migration automatically.';}
        }
        $rows[]=$file+['status'=>$status,'detail'=>$detail,'applied'=>$existing];
    }
    foreach($applied as $key=>$row){ if(isset($seen[$key]))continue; $rows[]=['key'=>$key,'path'=>(string)($row['file_path']??''),'exists'=>false,'checksum'=>'','status'=>'orphaned_record','detail'=>'Migration is recorded in the database but its repository file is missing.','applied'=>$row]; }
    return $rows;
}
function sf_dor_migration_blockers(): array {
    return array_values(array_filter(sf_dor_migration_drift(),static fn($r)=>in_array($r['status'],['missing','checksum_mismatch','orphaned_record'],true)));
}

function sf_dor_advisory_lock(PDO $pdo, string $name, int $timeout = 0): bool {
    try { $stmt=$pdo->prepare('SELECT GET_LOCK(?,?)');$stmt->execute([substr($name,0,64),max(0,min(30,$timeout))]);return (int)$stmt->fetchColumn()===1; }
    catch(Throwable $e){ return false; }
}
function sf_dor_advisory_unlock(PDO $pdo, string $name): void {
    try{$stmt=$pdo->prepare('SELECT RELEASE_LOCK(?)');$stmt->execute([substr($name,0,64)]);}catch(Throwable $e){}
}
function sf_dor_run_pending_migrations(PDO $pdo): array {
    sf_install_schema_table($pdo); $results=[]; $root=sf_dor_root(); $applied=sf_dor_applied_migrations();
    foreach(sf_dor_migration_files() as $file){
        $key=(string)$file['key'];$path=(string)$file['path'];
        if(empty($file['exists'])){$results[]=['key'=>$key,'label'=>$path,'status'=>'missing','detail'=>'SQL file missing.'];break;}
        $old=$applied[$key]??null;
        if($old && hash_equals((string)($old['checksum_sha256']??''),(string)$file['checksum'])){$results[]=['key'=>$key,'label'=>$path,'status'=>'skipped','detail'=>'Already applied with matching checksum.'];continue;}
        if($old){$results[]=['key'=>$key,'label'=>$path,'status'=>'failed','detail'=>'Checksum mismatch. Create a new migration instead of modifying an applied migration.'];break;}
        try{
            $sql=(string)file_get_contents($root.'/'.$path);
            foreach(sf_install_split_sql($sql) as $statement)sf_install_execute_statement($pdo,$statement);
            sf_install_mark_migration($pdo,$key,$path,(string)$file['checksum']);
            $results[]=['key'=>$key,'label'=>$path,'status'=>'applied','detail'=>'Applied successfully.'];
        }catch(Throwable $e){$results[]=['key'=>$key,'label'=>$path,'status'=>'failed','detail'=>$e->getMessage()];break;}
    }
    $_SESSION['sf_install_sql_results']=$results;
    return $results;
}
function sf_dor_schema_repair(string $confirmation): array {
    $pdo=sf_db(); if(!$pdo)return ['ok'=>false,'message'=>'Database connection is unavailable.','results'=>[]];
    global $database; $dbName=(string)($database['name']??'database');
    if(!sf_dor_env_bool('SF_ALLOW_SCHEMA_REPAIR',false))return ['ok'=>false,'message'=>'Schema repair is disabled. Set SF_ALLOW_SCHEMA_REPAIR=1 only during an approved maintenance window.','results'=>[]];
    if(!hash_equals('REPAIR '.$dbName,trim($confirmation)))return ['ok'=>false,'message'=>'Confirmation phrase does not match REPAIR '.$dbName.'.','results'=>[]];
    if(sf_dor_is_production() && sf_get_setting('maintenance_mode','0')!=='1')return ['ok'=>false,'message'=>'Production schema repair requires maintenance mode.','results'=>[]];
    if(sf_dor_is_production()){
        $backup=sf_dor_latest_verified_backup(24);
        if(!$backup)return ['ok'=>false,'message'=>'A fully verified backup from the last 24 hours is required.','results'=>[]];
    }
    $lock='stonefellow_schema_repair';
    if(!sf_dor_advisory_lock($pdo,$lock,0))return ['ok'=>false,'message'=>'Another schema repair or migration process is already running.','results'=>[]];
    try{
        @set_time_limit(300);$results=sf_dor_run_pending_migrations($pdo);
        $failed=array_filter($results,static fn($r)=>in_array(($r['status']??''),['failed','missing'],true));
        if(function_exists('sf_sec_audit'))sf_sec_audit('schema_repair_run',$failed?'error':'notice','schema_migrations',0,['results'=>$results]);
        return ['ok'=>!$failed,'message'=>$failed?'Schema repair stopped safely on a blocking migration.':'Pending migrations applied successfully.','results'=>$results];
    }finally{sf_dor_advisory_unlock($pdo,$lock);}
}

function sf_dor_manifest(array $run): array {
    $decoded=!empty($run['manifest_json'])?json_decode((string)$run['manifest_json'],true):[]; return is_array($decoded)?$decoded:[];
}
function sf_dor_artifact_evidence(array $run): array {
    $manifest=sf_dor_manifest($run);$artifact=is_array($manifest['artifact']??null)?$manifest['artifact']:[];
    $sha=strtolower(trim((string)($artifact['sha256']??'')));$size=(int)($artifact['size_bytes']??0);$created=(string)($artifact['created_at']??'');
    $ok=(bool)preg_match('/^[a-f0-9]{64}$/',$sha)&&$size>0&&$created!=='';
    return ['ok'=>$ok,'sha256'=>$sha,'size_bytes'=>$size,'created_at'=>$created,'scope'=>$artifact['scope']??[]];
}
function sf_dor_backup_gate(int $runId): array {
    $run=sf_br_run($runId);$reasons=[];if(!$run)return ['ok'=>false,'reasons'=>['Backup record not found.'],'run'=>null];
    if(($run['run_status']??'')!=='verified')$reasons[]='Backup run status is not verified.';
    foreach(['database_status','uploads_status','config_status'] as $field)if(($run[$field]??'')!=='verified')$reasons[]=str_replace('_',' ',$field).' must be verified.';
    if(trim((string)($run['storage_location']??''))==='')$reasons[]='Storage location/reference is required.';
    $artifact=sf_dor_artifact_evidence($run);if(!$artifact['ok'])$reasons[]='Artifact SHA-256, byte size, and creation time are required.';
    $checks=sf_br_restore_checks($runId);if(!$checks)$reasons[]='Restore readiness checks are missing.';
    foreach($checks as $check)if(($check['status']??'')!=='passed')$reasons[]='Restore check not passed: '.($check['check_label']??$check['check_key']??'unknown').'.';
    return ['ok'=>!$reasons,'reasons'=>$reasons,'run'=>$run,'artifact'=>$artifact,'checks'=>$checks];
}
function sf_dor_latest_verified_backup(int $maxAgeHours = 24): ?array {
    if(!sf_dor_table_exists('backup_runs'))return null;
    $rows=sf_br_fetch_all("SELECT * FROM backup_runs WHERE run_status='verified' AND verified_at IS NOT NULL AND verified_at>=DATE_SUB(NOW(), INTERVAL ".max(1,min(720,$maxAgeHours))." HOUR) ORDER BY verified_at DESC,id DESC LIMIT 20");
    foreach($rows as $run)if(sf_dor_backup_gate((int)$run['id'])['ok'])return $run;return null;
}
function sf_dor_create_backup_run(array $data): int {
    $data['run_status']='planned';$data['database_status']='not_started';$data['uploads_status']='not_started';$data['config_status']='not_started';return sf_br_create_run($data);
}
function sf_dor_update_backup_run(int $id,array $data): array {
    $run=sf_br_run($id);if(!$run)return ['ok'=>false,'message'=>'Backup record not found.'];
    $allowed=['planned','running','completed','failed','verified','archived'];$status=in_array(($data['run_status']??'planned'),$allowed,true)?$data['run_status']:'planned';
    $componentAllowed=['not_started','exported','skipped','failed','verified'];
    $database=in_array(($data['database_status']??'not_started'),$componentAllowed,true)?$data['database_status']:'not_started';
    $uploads=in_array(($data['uploads_status']??'not_started'),$componentAllowed,true)?$data['uploads_status']:'not_started';
    $config=in_array(($data['config_status']??'not_started'),$componentAllowed,true)?$data['config_status']:'not_started';
    $location=substr(trim((string)($data['storage_location']??'')),0,255);$notes=substr(trim((string)($data['notes']??'')),0,10000);
    $manifest=sf_dor_manifest($run);$manifest['generated_at']=$manifest['generated_at']??date('c');
    $manifest['artifact']=['sha256'=>strtolower(trim((string)($data['artifact_sha256']??($manifest['artifact']['sha256']??'')))),'size_bytes'=>max(0,(int)($data['artifact_size_bytes']??($manifest['artifact']['size_bytes']??0))),'created_at'=>trim((string)($data['artifact_created_at']??($manifest['artifact']['created_at']??''))),'scope'=>['database','uploads','config']];
    $candidate=array_merge($run,['run_status'=>$status,'database_status'=>$database,'uploads_status'=>$uploads,'config_status'=>$config,'storage_location'=>$location,'manifest_json'=>json_encode($manifest,JSON_UNESCAPED_SLASHES)]);
    if(in_array($status,['completed','verified'],true) && in_array('not_started',[$database,$uploads,$config],true))return ['ok'=>false,'message'=>'Completed backups cannot contain not-started components.'];
    if($status==='verified'){
        if($database!=='verified'||$uploads!=='verified'||$config!=='verified')return ['ok'=>false,'message'=>'Database, uploads, and config must each be verified.'];
        if($location==='')return ['ok'=>false,'message'=>'A storage location/reference is required.'];
        if(!sf_dor_artifact_evidence($candidate)['ok'])return ['ok'=>false,'message'=>'Enter a valid 64-character artifact SHA-256, byte size, and creation time.'];
        foreach(sf_br_restore_checks($id) as $check)if(($check['status']??'')!=='passed')return ['ok'=>false,'message'=>'Every restore readiness check must be passed before verification.'];
    }
    $ok=sf_br_execute('UPDATE backup_runs SET run_status=?,database_status=?,uploads_status=?,config_status=?,manifest_json=?,storage_location=?,notes=?,started_at=CASE WHEN ?="running" THEN COALESCE(started_at,NOW()) ELSE started_at END,finished_at=CASE WHEN ? IN ("completed","verified","failed") THEN COALESCE(finished_at,NOW()) ELSE finished_at END,verified_at=CASE WHEN ?="verified" THEN NOW() ELSE NULL END,updated_at=NOW() WHERE id=?',[$status,$database,$uploads,$config,json_encode($manifest,JSON_UNESCAPED_SLASHES),$location?:null,$notes?:null,$status,$status,$status,$id]);
    if($ok&&function_exists('sf_sec_audit'))sf_sec_audit('backup_run_secured','notice','backup_run',$id,['status'=>$status,'artifact_sha256'=>$manifest['artifact']['sha256']]);
    return ['ok'=>$ok,'message'=>$ok?'Backup record updated.':'Backup record update failed.'];
}
function sf_dor_update_restore_check(int $id,string $status,string $detail=''): array {
    if(sf_dor_is_production() && $status==='waived')return ['ok'=>false,'message'=>'Production restore checks cannot be waived.'];
    $ok=sf_br_update_check($id,$status,substr(trim($detail),0,4000));return ['ok'=>$ok,'message'=>$ok?'Restore check updated.':'Restore check update failed.'];
}

function sf_dor_release_gate(int $releaseId): array {
    $release=sf_rel_release($releaseId);$reasons=[];if(!$release)return ['ok'=>false,'reasons'=>['Release not found.'],'release'=>null];
    $env=(string)($release['deployment_environment']??'production');$production=$env==='production';
    if(!preg_match('/^[a-f0-9]{40}$/i',(string)($release['git_sha']??'')))$reasons[]='A full 40-character Git commit SHA is required.';
    if(trim((string)($release['git_branch']??''))==='')$reasons[]='Git branch is required.';
    if($production&&trim((string)($release['rollback_notes']??''))==='')$reasons[]='Production rollback notes are required.';
    $tasks=sf_rel_tasks($releaseId);if(!$tasks)$reasons[]='Release checklist tasks are missing.';
    foreach($tasks as $task)if(($task['status']??'')!=='passed')$reasons[]='Release task not passed: '.($task['task_label']??$task['task_key']??'unknown').'.';
    $backupId=(int)($release['backup_run_id']??0);if($backupId<=0)$reasons[]='A linked verified backup is required.';else{
        $backupGate=sf_dor_backup_gate($backupId);if(!$backupGate['ok'])foreach($backupGate['reasons'] as $reason)$reasons[]='Backup: '.$reason;
        if($production&&!empty($backupGate['run']['verified_at'])&&strtotime((string)$backupGate['run']['verified_at'])<time()-86400)$reasons[]='Production backup is older than 24 hours.';
    }
    foreach(sf_dor_migration_drift() as $row)if(($row['status']??'')!=='current')$reasons[]='Migration '.$row['key'].' is '.$row['status'].'.';
    return ['ok'=>!$reasons,'reasons'=>array_values(array_unique($reasons)),'release'=>$release,'tasks'=>$tasks];
}
function sf_dor_save_release(array $data,int $id=0): array {
    $requested=in_array(($data['release_status']??'draft'),['draft','ready','deploying','deployed','rolled_back','failed','archived'],true)?$data['release_status']:'draft';
    $base=$data;$base['release_status']=in_array($requested,['ready','deploying','deployed'],true)?'draft':$requested;
    $id=sf_rel_create_or_update($base,$id);if(!$id)return ['ok'=>false,'id'=>0,'message'=>'Release was not saved.'];
    if(in_array($requested,['ready','deploying','deployed'],true)){
        $gate=sf_dor_release_gate($id);
        if(!$gate['ok']){sf_rel_event($id,'release_gate_blocked','warning','Release gate blocked',implode(' ',array_slice($gate['reasons'],0,8)));return ['ok'=>false,'id'=>$id,'message'=>'Release saved as draft. Gate blocked: '.implode(' ',array_slice($gate['reasons'],0,4))];}
        $sql='UPDATE deployment_releases SET release_status=?,updated_at=NOW()';$params=[$requested];
        if($requested==='deployed')$sql.=',deployed_at=NOW()';$sql.=' WHERE id=?';$params[]=$id;sf_br_execute($sql,$params);sf_rel_event($id,'release_gate_passed','success','Release gate passed','Status advanced to '.$requested.'.');
    }
    return ['ok'=>true,'id'=>$id,'message'=>'Release saved.'];
}
function sf_dor_update_release_task(int $taskId,string $status,string $detail=''): array {
    if(sf_dor_is_production()&&$status==='waived')return ['ok'=>false,'message'=>'Production release tasks cannot be waived.'];
    $ok=sf_rel_update_task($taskId,$status,substr(trim($detail),0,4000));return ['ok'=>$ok,'message'=>$ok?'Release task updated.':'Release task update failed.'];
}

function sf_dor_orphan_checks(PDO $pdo): array {
    $relations=[
        ['user_subscriptions','user_id','users','id'],['user_subscriptions','plan_id','subscription_plans','id'],['content_access_grants','user_id','users','id'],
        ['order_items','order_id','orders','id'],['order_items','product_id','products','id'],['playlist_songs','playlist_id','playlists','id'],['playlist_songs','song_id','songs','id'],
        ['video_files','video_id','videos','id'],['song_files','song_id','songs','id']
    ];$out=[];
    foreach($relations as [$child,$childCol,$parent,$parentCol]){
        if(!sf_dor_table_exists($child)||!sf_dor_table_exists($parent)||!in_array($childCol,sf_dor_columns($child),true)||!in_array($parentCol,sf_dor_columns($parent),true))continue;
        try{$sql='SELECT COUNT(*) FROM `'.$child.'` c LEFT JOIN `'.$parent.'` p ON p.`'.$parentCol.'`=c.`'.$childCol.'` WHERE c.`'.$childCol.'` IS NOT NULL AND p.`'.$parentCol.'` IS NULL';$count=(int)$pdo->query($sql)->fetchColumn();$out[]=['relation'=>$child.'.'.$childCol.' → '.$parent.'.'.$parentCol,'count'=>$count];}catch(Throwable $e){}
    }return $out;
}
function sf_dor_operations_checks(): array {
    $checks=[];$pdo=sf_db();$root=sf_dor_root();
    $checks[]=sf_dor_check('Environment','env','pass','Runtime environment',sf_dor_env(),1);
    $checks[]=sf_dor_check('Environment','installer_lock',is_file($root.'/storage/install.lock')?'pass':'fail','Installer lock',is_file($root.'/storage/install.lock')?'Locked':'Installer is open',3);
    $config=$root.'/config/local.php';$mode=is_file($config)?(fileperms($config)&0777):0;$checks[]=sf_dor_check('Environment','config_permissions',!is_file($config)||($mode&0002)===0?'pass':'fail','Config file permissions',is_file($config)?decoct($mode):'Environment-only configuration',3);
    $free=@disk_free_space($root);$total=@disk_total_space($root);$ratio=($free!==false&&$total>0)?$free/$total:1;$checks[]=sf_dor_check('Environment','disk_space',$ratio>=.15?'pass':($ratio>=.08?'warn':'fail'),'Disk free space',$free===false?'Unavailable':number_format($ratio*100,1).'% free',3);
    $checks[]=sf_dor_check('Environment','schema_repair_default',!sf_dor_is_production()||!sf_dor_env_bool('SF_ALLOW_SCHEMA_REPAIR',false)?'pass':'warn','Schema repair default',sf_dor_env_bool('SF_ALLOW_SCHEMA_REPAIR',false)?'Enabled':'Disabled',2);
    if(!$pdo){$checks[]=sf_dor_check('Database','connection','fail','Database connection','Unavailable',5);return $checks;}
    $checks[]=sf_dor_check('Database','connection','pass','Database connection','Connected',5);
    try{$row=$pdo->query('SELECT @@FOREIGN_KEY_CHECKS fk,@@SESSION.sql_mode sql_mode,@@SESSION.time_zone tz')->fetch();$checks[]=sf_dor_check('Database','foreign_key_checks',(int)($row['fk']??0)===1?'pass':'fail','Foreign-key checks',(string)($row['fk']??'unknown'),4);$strict=str_contains((string)($row['sql_mode']??''),'STRICT_');$checks[]=sf_dor_check('Database','strict_mode',$strict?'pass':'fail','Strict SQL mode',(string)($row['sql_mode']??''),4);}catch(Throwable $e){$checks[]=sf_dor_check('Database','session_settings','fail','Database session settings',$e->getMessage(),3);}
    try{$stmt=$pdo->query("SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE' AND (ENGINE IS NULL OR ENGINE<>'InnoDB')");$rows=$stmt->fetchAll()?:[];$checks[]=sf_dor_check('Database','innodb',$rows?'fail':'pass','Transactional table engines',$rows?count($rows).' non-InnoDB tables':'All tables use InnoDB',4);}catch(Throwable $e){}
    foreach(sf_dor_orphan_checks($pdo) as $orphan)$checks[]=sf_dor_check('Database','orphan_'.hash('crc32b',$orphan['relation']),$orphan['count']===0?'pass':'fail','Orphan check: '.$orphan['relation'],$orphan['count'].' orphan rows',3);
    $drift=sf_dor_migration_drift();foreach($drift as $row)$checks[]=sf_dor_check('Migrations','migration_'.$row['key'],$row['status']==='current'?'pass':($row['status']==='pending'?'warn':'fail'),'Migration '.$row['key'],$row['status'].' — '.$row['detail'],3);
    $hardcoded=array_keys(sf_install_plan());$discovered=array_map(static fn($r)=>(string)$r['key'],sf_dor_migration_files());$missingPlan=array_values(array_diff($discovered,$hardcoded));$checks[]=sf_dor_check('Migrations','installer_coverage',$missingPlan?'fail':'pass','Installer migration coverage',$missingPlan?'Installer plan missing: '.implode(', ',$missingPlan):'Installer plan covers discovered migrations',4);
    $backup=sf_dor_latest_verified_backup(24);$checks[]=sf_dor_check('Recovery','fresh_verified_backup',$backup?'pass':'fail','Fresh verified backup',$backup?'Verified backup '.$backup['run_key'].' is available':'No fully verified backup from the last 24 hours',5);
    $latestRelease=sf_rel_releases(1)[0]??null;if($latestRelease){$gate=sf_dor_release_gate((int)$latestRelease['id']);$checks[]=sf_dor_check('Release','release_gate',$gate['ok']?'pass':'warn','Latest release gate',$gate['ok']?'All release gates pass':implode(' ',array_slice($gate['reasons'],0,4)),4);}else{$checks[]=sf_dor_check('Release','release_gate','manual','Latest release gate','No release record exists.',3);}
    $metrics=sf_mon_metrics();$checks[]=sf_dor_check('Monitoring','failed_jobs',(int)$metrics['failed_jobs_24h']===0?'pass':((int)$metrics['failed_jobs_24h']<3?'warn':'fail'),'Failed jobs in 24 hours',(string)$metrics['failed_jobs_24h'],3);$checks[]=sf_dor_check('Monitoring','failed_notifications',(int)$metrics['failed_notifications_24h']===0?'pass':((int)$metrics['failed_notifications_24h']<5?'warn':'fail'),'Failed notifications in 24 hours',(string)$metrics['failed_notifications_24h'],2);$checks[]=sf_dor_check('Monitoring','open_incidents',(int)$metrics['critical_incidents']===0?'pass':'fail','Critical incidents',(string)$metrics['critical_incidents'],4);
    return $checks;
}
function sf_dor_section_summary(array $checks): array {
    $out=[];foreach($checks as $check){$section=(string)$check['section'];$out[$section]['section']=$section;$out[$section]['checks'][]=$check;}$rows=[];foreach($out as $section=>$data){$items=$data['checks'];$rows[]=['section'=>$section,'score'=>sf_dor_score($items),'count'=>count($items),'fails'=>count(array_filter($items,static fn($c)=>$c['status']==='fail')),'warnings'=>count(array_filter($items,static fn($c)=>in_array($c['status'],['warn','manual','preview'],true)))];}return $rows;
}
?>