<?php
declare(strict_types=1);

function sf_vp3_clip_certification_check(bool $passed,string $detail):array{return['status'=>$passed?'pass':'fail','detail'=>substr($detail,0,500)];}
function sf_vp3_clip_certification_table_ready():bool{$pdo=sf_db();if(!$pdo)return false;try{$s=$pdo->prepare('SHOW TABLES LIKE ?');$s->execute(['vp3_clip_certifications']);return(bool)$s->fetchColumn();}catch(Throwable){return false;}}

function sf_vp3_clip_render_probe():array{
    $ffmpeg=sf_mp_binary_path('ffmpeg');
    if($ffmpeg==='')return['ok'=>false,'error'=>'ffmpeg_unavailable'];
    $root=sf_vp3_clip_output_root().'/certification';
    if(!is_dir($root)&&!mkdir($root,0750,true)&&!is_dir($root))return['ok'=>false,'error'=>'certification_directory_failed'];
    $key=sf_vp3_clip_uuid();$video=$root.'/'.$key.'.mp4';$poster=$root.'/'.$key.'.jpg';
    $render=sf_mp_run_process([$ffmpeg,'-y','-v','error','-f','lavfi','-i','testsrc2=size=360x640:rate=30:duration=2','-f','lavfi','-i','anullsrc=channel_layout=stereo:sample_rate=48000','-shortest','-t','2','-c:v','libx264','-preset','veryfast','-pix_fmt','yuv420p','-c:a','aac','-b:a','96k','-movflags','+faststart',$video],180);
    if(empty($render['ok'])){@unlink($video);return['ok'=>false,'error'=>'render_probe_failed','detail'=>substr((string)($render['stderr']??''),0,500)];}
    $frame=sf_mp_run_process([$ffmpeg,'-y','-v','error','-ss','0.5','-i',$video,'-frames:v','1','-q:v','3',$poster],120);
    $ok=!empty($frame['ok'])&&is_file($video)&&filesize($video)>1000&&is_file($poster)&&filesize($poster)>500;
    $detail=$ok?'Synthetic 360x640 H.264/AAC clip and poster rendered successfully.':'Poster or rendered probe validation failed.';
    @unlink($video);@unlink($poster);
    return['ok'=>$ok,'error'=>$ok?null:'render_probe_output_invalid','detail'=>$detail];
}

function sf_vp3_clip_certification_checks(bool $runRender=true):array{
    $checks=[];$pdo=sf_db();
    $checks['database_schema']=sf_vp3_clip_certification_check($pdo instanceof PDO&&sf_vp3_clip_tables_ready()&&sf_vp3_clip_certification_table_ready(),'Migrations 027 and 028 '.(($pdo instanceof PDO&&sf_vp3_clip_tables_ready()&&sf_vp3_clip_certification_table_ready())?'are installed.':'must be imported.'));
    $receipt=sf_license_receipt()?:[];$receiptOk=($receipt['product_id']??'')==='VP3-STONEFELLOW-001'&&!empty($receipt['installation_id'])&&!empty($receipt['activated_domain']);
    $checks['license_receipt']=sf_vp3_clip_certification_check($receiptOk,$receiptOk?'Stonefellow product and installation receipt verified.':'A valid Stonefellow license receipt is required.');
    $settings=[];$settingsError='';try{$settings=sf_vp3_clip_settings();}catch(Throwable $e){$settingsError=$e->getMessage();}
    $settingsOk=!empty($settings['api_base'])&&!empty($settings['bridge_uuid'])&&!empty($settings['bridge_secret']);
    $checks['bridge_settings']=sf_vp3_clip_certification_check($settingsOk,$settingsOk?'Encrypted VP3 bridge settings are configured.':($settingsError?:'Bridge ID, secret, and API base are required.'));
    $checks['curl_tls']=sf_vp3_clip_certification_check(function_exists('curl_init'),'PHP cURL '.(function_exists('curl_init')?'is available for verified TLS requests.':'is unavailable.'));
    $opensslOk=function_exists('openssl_encrypt')&&defined('OPENSSL_VERSION_TEXT');
    $checks['openssl']=sf_vp3_clip_certification_check($opensslOk,$opensslOk?'OpenSSL encryption support is available.':'OpenSSL encryption support is unavailable.');
    $ffmpeg=sf_mp_binary_path('ffmpeg');$ffprobe=sf_mp_binary_path('ffprobe');
    $checks['ffmpeg']=sf_vp3_clip_certification_check($ffmpeg!=='',$ffmpeg!==''?'FFmpeg executable resolved.':'FFmpeg executable was not found.');
    $checks['ffprobe']=sf_vp3_clip_certification_check($ffprobe!=='',$ffprobe!==''?'FFprobe executable resolved.':'FFprobe executable was not found.');
    $root=sf_vp3_clip_output_root();$storageOk=false;$storageDetail='';
    try{if(!is_dir($root)&&!mkdir($root,0750,true)&&!is_dir($root))throw new RuntimeException('Directory could not be created.');$probe=$root.'/.certification-write-'.bin2hex(random_bytes(6));$storageOk=file_put_contents($probe,'vp3-clips-certification',LOCK_EX)!==false&&is_file($probe);@unlink($probe);$storageDetail=$storageOk?'Private clip output storage is writable.':'Private clip output storage is not writable.';}catch(Throwable $e){$storageDetail=$e->getMessage();}
    $checks['clip_storage']=sf_vp3_clip_certification_check($storageOk,$storageDetail);
    $base=rtrim((string)($receipt['base_url']??getenv('SF_PUBLIC_BASE_URL')?:''),'/');$baseOk=filter_var($base,FILTER_VALIDATE_URL)&&strtolower((string)parse_url($base,PHP_URL_SCHEME))==='https';
    $checks['public_https_base']=sf_vp3_clip_certification_check((bool)$baseOk,$baseOk?'Public HTTPS base URL verified: '.$base:'The license receipt must contain a public HTTPS base URL.');
    $context=$settingsOk&&function_exists('curl_init')?sf_vp3_clip_context():['ok'=>false,'error'=>'bridge_prerequisites_failed'];
    $checks['signed_context']=sf_vp3_clip_certification_check(!empty($context['ok']),!empty($context['ok'])?'Central VP3 accepted a signed context request.':'Signed context failed: '.(string)($context['error']??'unknown error'));
    $render=$runRender?sf_vp3_clip_render_probe():['ok'=>false,'error'=>'render_probe_not_run'];
    $checks['render_probe']=sf_vp3_clip_certification_check(!empty($render['ok']),!empty($render['ok'])?(string)($render['detail']??'Synthetic render passed.'):'Synthetic render failed: '.(string)($render['error']??'unknown error'));
    return['checks'=>$checks,'context'=>$context,'receipt'=>$receipt,'settings'=>$settings];
}

function sf_vp3_clip_certification_submit(int $userId):array{
    $pdo=sf_db();if(!$pdo||!sf_vp3_clip_certification_table_ready())return['ok'=>false,'error'=>'import_migration_028'];
    $report=sf_vp3_clip_certification_checks(true);$settings=$report['settings'];
    $payload=['action'=>'submit','certification_version'=>'1.0','checks'=>$report['checks'],'source_report'=>[
        'product_id'=>$report['receipt']['product_id']??null,
        'installation_uuid'=>$report['receipt']['installation_id']??null,
        'activated_domain'=>$report['receipt']['activated_domain']??null,
        'base_url'=>$report['receipt']['base_url']??null,
        'bridge_uuid'=>$settings['bridge_uuid']??null,
        'php_version'=>PHP_VERSION,
        'generated_at'=>date(DATE_ATOM),
    ]];
    $result=sf_vp3_bridge_request('api/v1/clips/bridge/certification.php',$payload);
    $data=(array)($result['data']??[]);$status=!empty($result['ok'])?(string)($data['certification_status']??'failed'):'failed';$mode=!empty($result['ok'])?(string)($data['publishing_mode']??'certification'):'certification';
    $stmt=$pdo->prepare('INSERT INTO vp3_clip_certifications (certification_uuid,bridge_uuid,status,publishing_mode,checks_json,central_response_json,failure_summary,started_at,completed_at,approved_at,expires_at,created_by_user_id) VALUES (?,?,?,?,?,?,?,NOW(),NOW(),?,?,?)');
    $stmt->execute([$data['certification_uuid']??null,(string)($settings['bridge_uuid']??''),in_array($status,['passed','failed','approved','revoked'],true)?$status:'failed',$mode==='live'?'live':'certification',json_encode($report['checks'],JSON_UNESCAPED_SLASHES),json_encode($result,JSON_UNESCAPED_SLASHES),$data['failure_summary']??($result['error']??null),$data['approved_at']??null,$data['expires_at']??null,$userId?:null]);
    return$result;
}

function sf_vp3_clip_certification_refresh():array{
    $pdo=sf_db();if(!$pdo||!sf_vp3_clip_certification_table_ready())return['ok'=>false,'error'=>'import_migration_028'];
    $result=sf_vp3_bridge_request('api/v1/clips/bridge/certification.php',['action'=>'status']);
    if(empty($result['ok']))return$result;$data=(array)$result['data'];$settings=sf_vp3_clip_settings();
    $stmt=$pdo->prepare('SELECT id FROM vp3_clip_certifications WHERE certification_uuid=? LIMIT 1');$stmt->execute([(string)($data['certification_uuid']??'')]);$id=(int)$stmt->fetchColumn();
    if($id>0){$pdo->prepare('UPDATE vp3_clip_certifications SET status=?,publishing_mode=?,central_response_json=?,failure_summary=?,approved_at=?,expires_at=?,completed_at=NOW() WHERE id=?')->execute([(string)$data['certification_status'],($data['publishing_mode']??'certification')==='live'?'live':'certification',json_encode($result,JSON_UNESCAPED_SLASHES),$data['failure_summary']??null,$data['approved_at']??null,$data['expires_at']??null,$id]);}
    elseif(!empty($data['certification_uuid'])){$pdo->prepare('INSERT INTO vp3_clip_certifications (certification_uuid,bridge_uuid,status,publishing_mode,checks_json,central_response_json,failure_summary,started_at,completed_at,approved_at,expires_at) VALUES (?,?,?,?,?,?,?,NOW(),NOW(),?,?)')->execute([$data['certification_uuid'],(string)($settings['bridge_uuid']??''),(string)$data['certification_status'],($data['publishing_mode']??'certification')==='live'?'live':'certification',json_encode($data['checks']??[],JSON_UNESCAPED_SLASHES),json_encode($result,JSON_UNESCAPED_SLASHES),$data['failure_summary']??null,$data['approved_at']??null,$data['expires_at']??null]);}
    return$result;
}

function sf_vp3_clip_certification_latest():?array{$pdo=sf_db();if(!$pdo||!sf_vp3_clip_certification_table_ready())return null;$row=$pdo->query('SELECT * FROM vp3_clip_certifications ORDER BY id DESC LIMIT 1')->fetch();return is_array($row)?$row:null;}
function sf_vp3_clip_certification_history():array{$pdo=sf_db();if(!$pdo||!sf_vp3_clip_certification_table_ready())return[];return$pdo->query('SELECT * FROM vp3_clip_certifications ORDER BY id DESC LIMIT 50')->fetchAll()?:[];}
