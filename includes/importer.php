<?php
require_once __DIR__ . '/admin_catalog.php';

function sf_importer_tables_ready(): bool {
  return sf_admin_db_ready() && sf_admin_table_exists('content_import_batches') && sf_admin_table_exists('content_import_rows');
}

function sf_importer_configs(): array {
  return [
    'media_asset'=>['label'=>'Media Assets','table'=>'media_assets','unique'=>['file_path'],'required'=>['title','file_path','file_type'],'defaults'=>['file_type'=>'image']],
    'product_category'=>['label'=>'Product Categories','table'=>'product_categories','unique'=>['slug'],'required'=>['name','slug'],'defaults'=>['status'=>'active','sort_order'=>0]],
    'album'=>['label'=>'Albums','table'=>'albums','unique'=>['slug'],'required'=>['title','slug'],'defaults'=>['artist'=>'Stonefellow','status'=>'published']],
    'episode'=>['label'=>'Episodes','table'=>'episodes','unique'=>['slug'],'required'=>['title','slug','episode_number'],'defaults'=>['season_number'=>1,'status'=>'published']],
    'song'=>['label'=>'Songs','table'=>'songs','unique'=>['slug'],'required'=>['title','slug'],'defaults'=>['artist'=>'Stonefellow','access_level'=>'subscriber','status'=>'published'],'relations'=>['album_slug'=>['albums','slug','album_id']]],
    'song_file'=>['label'=>'Song Files','table'=>'song_files','unique'=>['song_id','file_type','file_path'],'required'=>['song_id','file_type','file_path'],'defaults'=>['file_type'=>'preview','mime_type'=>'audio/wav','is_primary'=>1],'relations'=>['song_slug'=>['songs','slug','song_id']]],
    'video'=>['label'=>'Videos','table'=>'videos','unique'=>['slug'],'required'=>['title','slug'],'defaults'=>['video_type'=>'episode','access_level'=>'subscriber','status'=>'published'],'relations'=>['episode_slug'=>['episodes','slug','episode_id']]],
    'video_file'=>['label'=>'Video Files','table'=>'video_files','unique'=>['video_id','file_type','file_path'],'required'=>['video_id','file_type','file_path'],'defaults'=>['file_type'=>'stream','mime_type'=>'video/mp4','is_primary'=>1],'relations'=>['video_slug'=>['videos','slug','video_id']]],
    'subscription_plan'=>['label'=>'Subscription Plans','table'=>'subscription_plans','unique'=>['slug'],'required'=>['name','slug','price_cents','billing_interval'],'defaults'=>['billing_interval'=>'month','status'=>'active'],'money'=>['price'=>'price_cents']],
    'product'=>['label'=>'Merch Products','table'=>'products','unique'=>['slug'],'required'=>['name','slug','price_cents'],'defaults'=>['product_type'=>'physical','access_level'=>'public','status'=>'active','inventory_quantity'=>0],'money'=>['price'=>'price_cents','compare_at_price'=>'compare_at_price_cents'],'relations'=>['category_slug'=>['product_categories','slug','category_id'],'category_name'=>['product_categories','name','category_id']]],
    'product_variant'=>['label'=>'Product Variants','table'=>'product_variants','unique'=>['product_id','variant_name'],'required'=>['product_id','variant_name'],'defaults'=>['status'=>'active','inventory_quantity'=>0],'money'=>['price'=>'price_cents'],'relations'=>['product_slug'=>['products','slug','product_id']]],
  ];
}

function sf_importer_types(): array { return array_map(fn($c)=>$c['label'], sf_importer_configs()); }
function sf_importer_config(string $type): ?array { $c=sf_importer_configs(); return $c[$type]??null; }
function sf_importer_key(string $key): string { return strtolower(trim(preg_replace('/[^a-zA-Z0-9_]+/','_',$key),'_')); }
function sf_importer_price($value): int { $v=preg_replace('/[^0-9.\-]/','',(string)$value); return (int)round(((float)$v)*100); }
function sf_importer_bool($value): int { return in_array(strtolower(trim((string)$value)),['1','yes','true','on','active','published','featured'],true)?1:0; }

function sf_importer_find_id(string $table,string $column,$value): ?int {
  if (!sf_admin_table_exists($table) || trim((string)$value)==='') return null;
  $row=sf_admin_fetch_one('SELECT id FROM `'.str_replace('`','',$table).'` WHERE `'.str_replace('`','',$column).'`=? LIMIT 1',[$value]);
  return $row?(int)$row['id']:null;
}

function sf_importer_normalize(string $type,array $row,int $num=1): array {
  $config=sf_importer_config($type); if(!$config) return ['ok'=>false,'errors'=>['Unsupported type'],'payload'=>[],'source'=>$row,'row_number'=>$num,'unique_key'=>''];
  $clean=[]; foreach($row as $k=>$v){$clean[sf_importer_key((string)$k)]=is_string($v)?trim($v):$v;}
  $payload=array_merge($config['defaults']??[],$clean);
  foreach(($config['money']??[]) as $from=>$to){ if(isset($payload[$from]) && !isset($payload[$to])) $payload[$to]=sf_importer_price($payload[$from]); }
  if(empty($payload['slug']) && !empty($payload['title'])) $payload['slug']=sf_admin_slugify((string)$payload['title']);
  if(empty($payload['slug']) && !empty($payload['name'])) $payload['slug']=sf_admin_slugify((string)$payload['name']);
  foreach(($config['relations']??[]) as $from=>$rel){ if(!empty($payload[$from]) && empty($payload[$rel[2]])){ $id=sf_importer_find_id($rel[0],$rel[1],$payload[$from]); if($id) $payload[$rel[2]]=$id; } }
  foreach(['is_featured','is_primary','is_limited_drop','allows_full_music','allows_video_streaming','allows_episode_tracking','allows_playlists','allows_offline_downloads'] as $b){ if(isset($payload[$b])) $payload[$b]=sf_importer_bool($payload[$b]); }
  foreach($payload as $k=>$v){ if(preg_match('/(_id|_seconds|_minutes|_number|_count|_quantity|_cents|sort_order)$/',$k) && $v!=='' && $v!==null && is_numeric($v)) $payload[$k]=(int)$v; }
  $errors=[]; if(!sf_admin_table_exists($config['table'])) $errors[]='Missing table: '.$config['table'];
  foreach($config['required'] as $field){ if(!isset($payload[$field]) || $payload[$field]==='') $errors[]='Missing required field: '.$field; }
  $payload=sf_admin_table_exists($config['table'])?sf_admin_column_filtered_payload($config['table'],$payload):$payload;
  unset($payload['id'],$payload['created_at'],$payload['updated_at']);
  $parts=[]; foreach($config['unique'] as $field){ $parts[]=$field.'='.($payload[$field]??''); }
  return ['ok'=>!$errors,'errors'=>$errors,'payload'=>$payload,'source'=>$row,'row_number'=>$num,'unique_key'=>implode('|',$parts)];
}

function sf_importer_parse_csv(string $path): array {
  $h=fopen($path,'rb'); if(!$h) throw new RuntimeException('Could not open CSV.');
  $head=fgetcsv($h); if(!$head){fclose($h); return [];} $head=array_map('sf_importer_key',$head); $rows=[];
  while(($line=fgetcsv($h))!==false){ if(!array_filter($line,fn($v)=>trim((string)$v)!=='')) continue; $rows[]=array_combine(array_slice($head,0,count($line)),$line)?:[]; }
  fclose($h); return $rows;
}

function sf_importer_parse_payload(?array $file,string $pasted): array {
  if($file && (int)($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_OK){ $ext=strtolower(pathinfo((string)$file['name'],PATHINFO_EXTENSION)); if($ext==='csv') return sf_importer_parse_csv((string)$file['tmp_name']); $pasted=(string)file_get_contents((string)$file['tmp_name']); }
  $pasted=trim($pasted); if($pasted==='') return [];
  $data=json_decode($pasted,true); if(!is_array($data)) throw new RuntimeException('JSON could not be decoded.');
  return array_values(isset($data['rows'])&&is_array($data['rows'])?$data['rows']:$data);
}

function sf_importer_preview(string $type,array $rows): array { $out=[];$errors=0; foreach($rows as $i=>$row){$r=sf_importer_normalize($type,is_array($row)?$row:[],$i+1); if(!$r['ok'])$errors++; $out[]=$r;} return ['ok'=>$errors===0,'rows'=>$out,'total'=>count($out),'errors'=>$errors]; }

function sf_importer_existing(array $config,array $payload): ?array {
  $where=[];$params=[]; foreach($config['unique'] as $f){ if(!isset($payload[$f])||$payload[$f]==='') return null; $where[]='`'.str_replace('`','',$f).'`=?'; $params[]=$payload[$f]; }
  return sf_admin_fetch_one('SELECT * FROM `'.str_replace('`','',$config['table']).'` WHERE '.implode(' AND ',$where).' LIMIT 1',$params);
}

function sf_importer_insert(string $table,array $payload): int {
  $cols=array_keys($payload); $safe=array_map(fn($c)=>'`'.str_replace('`','',$c).'`',$cols);
  if(!sf_admin_execute('INSERT INTO `'.str_replace('`','',$table).'` ('.implode(',',$safe).') VALUES ('.implode(',',array_fill(0,count($cols),'?')).')',array_values($payload))) return 0;
  return (int)(sf_admin_db()?->lastInsertId()?:0);
}

function sf_importer_update(string $table,int $id,array $payload): bool {
  unset($payload['id'],$payload['created_at']); if(sf_admin_column_exists($table,'updated_at')) $payload['updated_at']=date('Y-m-d H:i:s');
  $sets=array_map(fn($c)=>'`'.str_replace('`','',$c).'`=?',array_keys($payload));
  return $payload?sf_admin_execute('UPDATE `'.str_replace('`','',$table).'` SET '.implode(',',$sets).' WHERE id=?',array_merge(array_values($payload),[$id])):true;
}

function sf_importer_log(int $batch,int $row,string $table,?int $id,string $action,string $status,string $key,array $src,?array $before,?array $after,string $error=''): void {
  sf_admin_execute('INSERT INTO content_import_rows (batch_id,row_number,entity_table,entity_id,import_action,import_status,unique_key_value,source_json,before_json,after_json,error_message) VALUES (?,?,?,?,?,?,?,?,?,?,?)',[$batch,$row,$table,$id,$action,$status,$key,json_encode($src),$before?json_encode($before):null,$after?json_encode($after):null,$error?:null]);
}

function sf_importer_run(string $type,array $rows,string $source='manual'): array {
  $config=sf_importer_config($type); if(!$config) return ['ok'=>false,'message'=>'Unsupported import type.'];
  if(!sf_importer_tables_ready()) return ['ok'=>false,'message'=>'Run migration 011 before importing.'];
  $preview=sf_importer_preview($type,$rows); if(!$preview['ok']) return ['ok'=>false,'message'=>'Preview has validation errors.','preview'=>$preview];
  sf_admin_execute('INSERT INTO content_import_batches (import_key,import_type,source_name,status,total_rows,created_by_user_id) VALUES (?,?,?,?,?,?)',['import_'.date('Ymd_His').'_'.substr(bin2hex(random_bytes(3)),0,6),$type,$source,'processing',count($rows),sf_current_user_id()]);
  $batch=(int)(sf_admin_db()?->lastInsertId()?:0); if(!$batch) return ['ok'=>false,'message'=>'Could not create import batch.'];
  $counts=['inserted'=>0,'updated'=>0,'skipped'=>0,'errors'=>0];
  foreach($preview['rows'] as $item){ $payload=$item['payload']; $existing=sf_importer_existing($config,$payload); $table=$config['table'];
    if($existing){ $id=(int)$existing['id']; $diff=array_diff_assoc($payload,array_intersect_key($existing,$payload)); if($diff){ sf_importer_update($table,$id,$payload); $after=sf_admin_fetch_one('SELECT * FROM `'.str_replace('`','',$table).'` WHERE id=?',[$id])?:$payload; sf_importer_log($batch,$item['row_number'],$table,$id,'update','success',$item['unique_key'],$item['source'],$existing,$after); $counts['updated']++; } else { sf_importer_log($batch,$item['row_number'],$table,$id,'skip','skipped',$item['unique_key'],$item['source'],$existing,$existing); $counts['skipped']++; } }
    else { $id=sf_importer_insert($table,$payload); $after=$id?(sf_admin_fetch_one('SELECT * FROM `'.str_replace('`','',$table).'` WHERE id=?',[$id])?:$payload):$payload; sf_importer_log($batch,$item['row_number'],$table,$id?:null,'insert',$id?'success':'failed',$item['unique_key'],$item['source'],null,$after,$id?'':'Insert failed'); $id?$counts['inserted']++:$counts['errors']++; }
  }
  sf_admin_execute('UPDATE content_import_batches SET status=?, inserted_count=?, updated_count=?, skipped_count=?, error_count=?, completed_at=NOW(), summary_json=? WHERE id=?',[$counts['errors']?'failed':'completed',$counts['inserted'],$counts['updated'],$counts['skipped'],$counts['errors'],json_encode($counts),$batch]);
  return ['ok'=>$counts['errors']===0,'message'=>'Import completed.','batch_id'=>$batch,'counts'=>$counts];
}

function sf_importer_batches(int $limit=30): array { return sf_importer_tables_ready()?sf_admin_fetch_all('SELECT * FROM content_import_batches ORDER BY created_at DESC,id DESC LIMIT '.max(1,min(100,$limit))):[]; }
function sf_importer_batch_rows(int $id): array { return sf_importer_tables_ready()?sf_admin_fetch_all('SELECT * FROM content_import_rows WHERE batch_id=? ORDER BY row_number,id',[$id]):[]; }
function sf_importer_safe_table(string $table): bool { return in_array($table,array_map(fn($c)=>$c['table'],sf_importer_configs()),true); }
function sf_importer_rollback_batch(int $id): array { $batch=sf_admin_fetch_one('SELECT * FROM content_import_batches WHERE id=?',[$id]); if(!$batch)return ['ok'=>false,'message'=>'Batch not found.']; if(($batch['status']??'')==='rolled_back')return ['ok'=>false,'message'=>'Batch already rolled back.']; $rows=sf_admin_fetch_all('SELECT * FROM content_import_rows WHERE batch_id=? ORDER BY id DESC',[$id]); $n=['deleted'=>0,'restored'=>0,'skipped'=>0]; foreach($rows as $r){$t=(string)$r['entity_table'];$rid=(int)$r['entity_id']; if(!$rid||!sf_importer_safe_table($t)){$n['skipped']++;continue;} if($r['import_action']==='insert'){sf_admin_execute('DELETE FROM `'.str_replace('`','',$t).'` WHERE id=?',[$rid]);$n['deleted']++;} elseif($r['import_action']==='update'&&!empty($r['before_json'])){$before=json_decode($r['before_json'],true); if(is_array($before)){sf_importer_update($t,$rid,sf_admin_column_filtered_payload($t,$before));$n['restored']++;}} else $n['skipped']++; } sf_admin_execute('UPDATE content_import_batches SET status=\'rolled_back\',rolled_back_at=NOW(),summary_json=? WHERE id=?',[json_encode(['rollback'=>$n]),$id]); return ['ok'=>true,'message'=>'Import batch rolled back.','counts'=>$n]; }

function sf_importer_starter_seed(): array { $file=__DIR__.'/../database/seeds/starter_catalog.json'; if(!is_file($file))return []; $json=json_decode((string)file_get_contents($file),true); return is_array($json)?$json:[]; }
function sf_importer_sample_rows(string $type): array { $seed=sf_importer_starter_seed(); return array_slice($seed['rows'][$type]??[],0,1); }
function sf_importer_seed_groups(): array { return ['starter_catalog'=>['label'=>'Starter Catalog','description'=>'Core album, episodes, songs, videos, plans, merch, and variants.']]; }
function sf_importer_run_seed_group(string $group): array { $seed=sf_importer_starter_seed(); if($group!=='starter_catalog'||!$seed)return ['ok'=>false,'message'=>'Seed payload not found.','results'=>[]]; $results=[];$ok=true; foreach(($seed['import_order']??array_keys($seed['rows']??[])) as $type){ if(empty($seed['rows'][$type]))continue; $res=sf_importer_run($type,$seed['rows'][$type],'seed:'.$group.':'.$type); $results[$type]=$res; if(empty($res['ok'])){$ok=false;break;} } return ['ok'=>$ok,'message'=>$ok?'Seed group completed.':'Seed group stopped with an error.','results'=>$results]; }
?>
