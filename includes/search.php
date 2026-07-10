<?php
require_once __DIR__ . '/library.php';
require_once __DIR__ . '/content_integrity.php';

function sf_search_types(): array { return ['song','album','video','episode','product']; }
function sf_search_query(string $query): string { $query=sf_content_clean_text($query,100);return $query; }
function sf_search_static_index(): array {
  global $catalogSongs,$videoCatalog,$episodes,$musicAlbum,$products;$rows=[];
  foreach($catalogSongs as $s)$rows[]=['content_type'=>'song','content_id'=>(int)($s['id']??0),'title'=>$s['title']??'','slug'=>$s['slug']??'','description'=>($s['episode']??'').' '.($s['artist']??''),'image_path'=>$s['cover']??'images/music/soundtrack-cover.png','content_url'=>sf_library_content_url('song',(string)($s['slug']??'')),'access_level'=>$s['access']??'subscriber','status'=>'published','weight'=>80];
  foreach($videoCatalog as $v)$rows[]=['content_type'=>'video','content_id'=>(int)($v['id']??0),'title'=>$v['title']??'','slug'=>$v['slug']??'','description'=>$v['description']??'','image_path'=>$v['poster']??'images/episodes/episode-01.png','content_url'=>sf_library_content_url('video',(string)($v['slug']??'')),'access_level'=>$v['access_level']??'subscriber','status'=>'published','weight'=>90];
  foreach($episodes as $i=>$e)$rows[]=['content_type'=>'episode','content_id'=>$i+1,'title'=>$e['title']??'','slug'=>$e['slug']??'','description'=>$e['description']??'','image_path'=>$e['image']??'images/episodes/episode-01.png','content_url'=>sf_library_content_url('episode',(string)($e['slug']??'')),'access_level'=>'subscriber','status'=>'published','weight'=>75];
  if(!empty($musicAlbum))$rows[]=['content_type'=>'album','content_id'=>1,'title'=>$musicAlbum['title']??'','slug'=>$musicAlbum['slug']??'','description'=>$musicAlbum['description']??'','image_path'=>$musicAlbum['cover']??'images/music/soundtrack-cover.png','content_url'=>sf_library_content_url('album',(string)($musicAlbum['slug']??'')),'access_level'=>'subscriber','status'=>'published','weight'=>70];
  foreach($products as $p)$rows[]=['content_type'=>'product','content_id'=>(int)($p['id']??0),'title'=>$p['name']??'','slug'=>$p['slug']??'','description'=>$p['description']??'','image_path'=>$p['image']??'images/merch/merch-hero.png','content_url'=>sf_library_content_url('product',(string)($p['slug']??'')),'access_level'=>'public','status'=>'published','weight'=>55];
  return $rows;
}
function sf_search_index_ready(): bool { $pdo=sf_db();return $pdo instanceof PDO&&sf_content_table_exists($pdo,'content_search_index'); }
function sf_search_db_rows(string $query='',string $type=''): array {
  $pdo=sf_db();if(!$pdo instanceof PDO||!sf_search_index_ready())return [];$query=sf_search_query($query);$type=in_array($type,sf_search_types(),true)?$type:'';$where="WHERE status='published'";$params=[];
  if($query!==''){$where.=" AND (title LIKE ? ESCAPE '\\\\' OR description LIKE ? ESCAPE '\\\\' OR keywords LIKE ? ESCAPE '\\\\')";$like='%'.sf_content_like_escape($query).'%';$params=[$like,$like,$like];}
  if($type!==''){$where.=' AND content_type=?';$params[]=$type;}
  try{$s=$pdo->prepare("SELECT * FROM content_search_index {$where} ORDER BY is_featured DESC,weight DESC,updated_at DESC,id DESC LIMIT 100");$s->execute($params);return $s->fetchAll()?:[];}catch(Throwable $e){error_log('Stonefellow search query failed: '.$e->getMessage());return [];}
}
function sf_search_visible(array $row,string $level): bool { return ($row['status']??'published')==='published'&&sf_access_allows((string)($row['access_level']??'public'),$level)&&in_array((string)($row['content_type']??''),sf_search_types(),true); }
function sf_search_results(string $query='',string $type=''): array {
  $query=sf_search_query($query);$type=in_array(trim($type),sf_search_types(),true)?trim($type):'';if($query!==''&&!sf_content_rate_limit('search|'.sf_content_client_hash('q',$query),60,300))return [];
  $rows=sf_search_index_ready()?sf_search_db_rows($query,$type):sf_search_static_index();
  if(!sf_search_index_ready()){if($type!=='')$rows=array_values(array_filter($rows,fn($r)=>($r['content_type']??'')===$type));if($query!==''){$needle=strtolower($query);$rows=array_values(array_filter($rows,fn($r)=>str_contains(strtolower(($r['title']??'').' '.($r['description']??'').' '.($r['slug']??'')),$needle)));}}
  $level=sf_current_access_level();$rows=array_values(array_filter($rows,fn($r)=>sf_search_visible($r,$level)));foreach($rows as &$row){$row['title']=sf_content_clean_text((string)($row['title']??''),190);$row['description']=sf_content_clean_text((string)($row['description']??''),1000);$url=(string)($row['content_url']??'');$row['content_url']=str_starts_with($url,'http')||str_starts_with($url,'/')?$url:'#';}unset($row);usort($rows,fn($a,$b)=>(int)($b['weight']??0)<=>(int)($a['weight']??0));return array_slice($rows,0,100);
}
function sf_search_facets(array $rows): array { $facets=[];foreach($rows as $row){$type=(string)($row['content_type']??'other');if(in_array($type,sf_search_types(),true))$facets[$type]=($facets[$type]??0)+1;}ksort($facets);return $facets; }
?>
