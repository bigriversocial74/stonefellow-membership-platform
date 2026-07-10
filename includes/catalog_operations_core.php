<?php

declare(strict_types=1);

function sf_lco_required_tables(): array {
    return ['catalog_series','catalog_seo_metadata','catalog_readiness_snapshots','catalog_readiness_items','catalog_publication_batches','catalog_publication_actions','catalog_sample_flags','catalog_export_runs','catalog_operation_events'];
}
function sf_lco_ready(): bool { foreach (sf_lco_required_tables() as $table) if (!sf_admin_table_exists($table)) return false; return true; }
function sf_lco_timezone(): string { $tz=trim((string)(getenv('SF_CATALOG_TIMEZONE')?:'America/Phoenix'));try{new DateTimeZone($tz);return $tz;}catch(Throwable $e){return'America/Phoenix';} }
function sf_lco_normalize_schedule(?string $value, ?string $timezone=null): ?string { $value=trim((string)$value);if($value==='')return null;$tz=$timezone?:sf_lco_timezone();try{$local=new DateTimeImmutable($value,new DateTimeZone($tz));return $local->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');}catch(Throwable $e){return null;} }
function sf_lco_display_schedule(?string $utc, ?string $timezone=null): string { if(!$utc)return'Unscheduled';$tz=$timezone?:sf_lco_timezone();try{return(new DateTimeImmutable($utc,new DateTimeZone('UTC')))->setTimezone(new DateTimeZone($tz))->format('M j, Y g:i A T');}catch(Throwable $e){return(string)$utc;} }
function sf_lco_key(string $prefix='lco'): string { return $prefix.'_'.date('Ymd_His').'_'.substr(bin2hex(random_bytes(8)),0,16); }
function sf_lco_len(string $value): int { return function_exists('mb_strlen')?mb_strlen($value,'UTF-8'):strlen($value); }
function sf_lco_json($value): string { return json_encode($value,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)?:'{}'; }
function sf_lco_identifier(string $value): string { return preg_match('/^[a-zA-Z0-9_]+$/',$value)?$value:''; }
function sf_lco_types(): array {
    return [
      'series'=>['label'=>'Series','table'=>'catalog_series','title'=>['title'],'description'=>['description'],'slug'=>'slug','status'=>'status','publish'=>'published','archive'=>'archived','image'=>['primary_image_asset_id'],'edit'=>'admin/catalog-operations.php?section=series','public'=>'series.php'],
      'season'=>['label'=>'Seasons','table'=>'seasons','title'=>['title'],'description'=>['description'],'slug'=>'slug','status'=>'status','publish'=>'published','archive'=>'archived','image'=>['poster_asset_id'],'edit'=>'admin/seasons.php?edit=','public'=>'series.php'],
      'episode'=>['label'=>'Episodes','table'=>'episodes','title'=>['title'],'description'=>['short_description','description'],'slug'=>'slug','status'=>'status','publish'=>'published','archive'=>'archived','image'=>['hero_asset_id','poster_asset_id'],'edit'=>'admin/episodes.php?edit=','public'=>'episode.php?slug='],
      'video'=>['label'=>'Videos','table'=>'videos','title'=>['title'],'description'=>['short_description','description'],'slug'=>'slug','status'=>'status','publish'=>'published','archive'=>'archived','image'=>['poster_asset_id'],'edit'=>'admin/videos.php?edit=','public'=>'watch.php?slug='],
      'album'=>['label'=>'Albums','table'=>'albums','title'=>['title'],'description'=>['description'],'slug'=>'slug','status'=>'status','publish'=>'published','archive'=>'archived','image'=>['cover_asset_id'],'edit'=>'admin/music-albums.php?edit=','public'=>'album.php?slug='],
      'song'=>['label'=>'Songs','table'=>'songs','title'=>['title'],'description'=>['description'],'slug'=>'slug','status'=>'status','publish'=>'published','archive'=>'archived','image'=>['cover_asset_id'],'edit'=>'admin/music-songs.php?edit=','public'=>'song.php?slug='],
      'character'=>['label'=>'Cast / Characters','table'=>'story_characters','title'=>['character_name','name'],'description'=>['short_bio','motivation'],'slug'=>'slug','status'=>'status','publish'=>'active','archive'=>'archived','image'=>['image_path'],'edit'=>'admin/characters.php?edit=','public'=>'cast.php'],
      'product'=>['label'=>'Merchandise','table'=>'products','title'=>['name'],'description'=>['short_description','description'],'slug'=>'slug','status'=>'status','publish'=>'active','archive'=>'archived','image'=>['primary_image_asset_id'],'edit'=>'admin/products.php?edit=','public'=>'product.php?slug='],
      'plan'=>['label'=>'Membership Plans','table'=>'subscription_plans','title'=>['name'],'description'=>['description'],'slug'=>'slug','status'=>'status','publish'=>'active','archive'=>'inactive','image'=>[],'edit'=>'admin/billing.php','public'=>'subscribe.php'],
    ];
}
function sf_lco_type(string $type): ?array { $all=sf_lco_types();return$all[$type]??null; }
function sf_lco_rows(string $type,int $limit=1000): array { $c=sf_lco_type($type);if(!$c||!sf_admin_table_exists($c['table']))return[];$table=sf_lco_identifier($c['table']);if($table==='')return[];$limit=max(1,min(5000,$limit));return sf_admin_fetch_all('SELECT * FROM `'.$table.'` ORDER BY id ASC LIMIT '.$limit); }
function sf_lco_value(array $row,array $keys,$default=''){foreach($keys as$key)if(isset($row[$key])&&trim((string)$row[$key])!=='')return$row[$key];return$default;}
function sf_lco_title(string $type,array $row): string { $c=sf_lco_type($type);return trim((string)sf_lco_value($row,$c['title']??[],$c['label'].' #'.($row['id']??0))); }
function sf_lco_description(string $type,array $row): string { $c=sf_lco_type($type);return trim((string)sf_lco_value($row,$c['description']??[],'')); }
function sf_lco_slug(string $type,array $row): string { $c=sf_lco_type($type);$key=$c['slug']??'slug';return trim((string)($row[$key]??'')); }
function sf_lco_edit_url(string $type,array $row): string { $c=sf_lco_type($type);$base=(string)($c['edit']??'admin/catalog-operations.php');return str_ends_with($base,'=')?$base.(int)($row['id']??0):$base; }
function sf_lco_public_url(string $type,array $row): string { $c=sf_lco_type($type);$base=(string)($c['public']??'#');return str_ends_with($base,'=')?$base.urlencode(sf_lco_slug($type,$row)):$base; }
function sf_lco_check(string $key,string $label,bool $ok,string $detail,int $weight=1,string $severity='blocker'): array { return compact('key','label','ok','detail','weight','severity'); }
function sf_lco_row_exists(string $table,int $id): bool { if($id<=0||!sf_admin_table_exists($table))return false;$table=sf_lco_identifier($table);return(bool)sf_admin_fetch_one('SELECT id FROM `'.$table.'` WHERE id=? LIMIT 1',[$id]); }
function sf_lco_duplicate_slug(string $table,string $slug): bool { if($slug===''||!sf_admin_table_exists($table))return false;$table=sf_lco_identifier($table);$row=sf_admin_fetch_one('SELECT COUNT(*) AS total FROM `'.$table.'` WHERE slug=?',[$slug]);return(int)($row['total']??0)>1; }
function sf_lco_asset_ready($value): bool { if(is_numeric($value))return sf_lco_row_exists('media_assets',(int)$value);$path=trim((string)$value);return$path!==''&&!str_contains($path,'..')&&!preg_match('/placeholder|sample|demo/i',$path); }
function sf_lco_seo(string $type,int $id): ?array { if(!sf_admin_table_exists('catalog_seo_metadata'))return null;return sf_admin_fetch_one('SELECT * FROM catalog_seo_metadata WHERE entity_type=? AND entity_id=? LIMIT 1',[$type,$id]); }
function sf_lco_media_roles(string $type,int $id): array { if(!sf_admin_table_exists('media_objects')||$id<=0||!in_array($type,['song','video','episode','album','series'],true))return[];$rows=sf_admin_fetch_all("SELECT role,status,COUNT(*) AS total FROM media_objects WHERE entity_type=? AND entity_id=? GROUP BY role,status",[$type,$id]);$out=[];foreach($rows as$r)$out[(string)$r['role']][(string)$r['status']]=(int)$r['total'];return$out; }
function sf_lco_file_count(string $table,string $fk,int $id,string $type=''): int { if(!sf_admin_table_exists($table)||$id<=0)return 0;$table=sf_lco_identifier($table);$fk=sf_lco_identifier($fk);$sql='SELECT COUNT(*) AS total FROM `'.$table.'` WHERE `'.$fk.'`=?';$params=[$id];if($type!==''&&sf_admin_column_exists($table,'file_type')){$sql.=' AND file_type=?';$params[]=$type;}$r=sf_admin_fetch_one($sql,$params);return(int)($r['total']??0); }
