<?php

declare(strict_types=1);
require_once __DIR__ . '/db.php';

function sf_content_env_int(string $name,int $default,int $min,int $max): int { $v=getenv($name);$n=($v!==false&&is_numeric($v))?(int)$v:$default;return max($min,min($max,$n)); }
function sf_content_allowed_types(): array { return ['episode','video','song','album','post','product']; }
function sf_content_access_levels(): array { return ['public','free_account','subscriber','premium','founding_fan']; }
function sf_content_publish_statuses(): array { return ['draft','scheduled','published','archived']; }
function sf_content_identifier(string $value): string { return preg_match('/^[a-z0-9_]+$/i',$value)?$value:''; }
function sf_content_table_exists(PDO $pdo,string $table): bool { $table=sf_content_identifier($table);if($table==='')return false;try{$s=$pdo->prepare('SHOW TABLES LIKE ?');$s->execute([$table]);return (bool)$s->fetchColumn();}catch(Throwable $e){return false;} }
function sf_content_columns(PDO $pdo,string $table): array { $table=sf_content_identifier($table);if($table===''||!sf_content_table_exists($pdo,$table))return [];try{return array_map(static fn($r)=>(string)($r['Field']??''),$pdo->query('SHOW COLUMNS FROM `'.$table.'`')->fetchAll()?:[]);}catch(Throwable $e){return [];} }
function sf_content_column_type(PDO $pdo,string $table,string $column): string { $table=sf_content_identifier($table);$column=sf_content_identifier($column);if($table===''||$column==='')return '';try{$s=$pdo->prepare('SHOW COLUMNS FROM `'.$table.'` LIKE ?');$s->execute([$column]);$r=$s->fetch();return (string)($r['Type']??'');}catch(Throwable $e){return '';} }
function sf_content_enum_allows(PDO $pdo,string $table,string $column,string $value): bool { $type=sf_content_column_type($pdo,$table,$column);if(!str_starts_with(strtolower($type),'enum('))return true;preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/",$type,$m);return in_array($value,$m[1]??[],true); }
function sf_content_normalize_datetime($value): ?string { $value=trim((string)$value);if($value==='')return null;try{$dt=new DateTimeImmutable($value);return $dt->format('Y-m-d H:i:s');}catch(Throwable $e){return null;} }
function sf_content_clean_text(string $value,int $max=2000): string { $value=preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u','',$value)??'';$value=trim(preg_replace('/\s+/u',' ',$value)??'');return function_exists('mb_substr')?mb_substr($value,0,$max):substr($value,0,$max); }
function sf_content_safe_slug(string $value): string { $value=strtolower(trim($value));$value=preg_replace('/[^a-z0-9]+/','-',$value)??'';return substr(trim($value,'-'),0,190); }
function sf_content_like_escape(string $query): string { return strtr($query,['\\'=>'\\\\','%'=>'\\%','_'=>'\\_']); }
function sf_content_rate_limit(string $bucket,int $limit,int $window): bool { $result=sf_security_session_rate_limit('content|'.$bucket,$limit,$window);return !empty($result['allowed']); }
function sf_content_client_hash(string $kind,string $value): string { $secret=(string)(getenv('SF_HASH_SALT')?:getenv('SF_APP_KEY')?:'');return $secret!==''?hash_hmac('sha256',$kind.'|'.$value,$secret):hash('sha256',$kind.'|'.$value); }
function sf_content_advisory_lock(PDO $pdo,string $name,int $timeout=5): bool { try{$s=$pdo->prepare('SELECT GET_LOCK(?,?)');$s->execute(['stonefellow:'.$name,max(0,min(30,$timeout))]);return (int)$s->fetchColumn()===1;}catch(Throwable $e){return false;} }
function sf_content_advisory_unlock(PDO $pdo,string $name): void { try{$s=$pdo->prepare('SELECT RELEASE_LOCK(?)');$s->execute(['stonefellow:'.$name]);}catch(Throwable $e){} }
function sf_content_user_active(int $userId): bool { $pdo=sf_db();if(!$pdo instanceof PDO||$userId<=0)return false;try{$s=$pdo->prepare("SELECT COUNT(*) FROM users WHERE id=? AND status='active'");$s->execute([$userId]);return (int)$s->fetchColumn()===1;}catch(Throwable $e){return false;} }
function sf_content_exists(string $type,int $id,string $slug=''): bool {
  $map=['episode'=>'episodes','video'=>'videos','song'=>'songs','album'=>'albums','post'=>'creator_posts','product'=>'products'];$table=$map[$type]??'';$pdo=sf_db();if(!$pdo instanceof PDO||$table===''||!sf_content_table_exists($pdo,$table))return false;
  try{if($id>0){$s=$pdo->prepare('SELECT COUNT(*) FROM `'.$table.'` WHERE id=?');$s->execute([$id]);return (int)$s->fetchColumn()===1;}if($slug!==''){$s=$pdo->prepare('SELECT COUNT(*) FROM `'.$table.'` WHERE slug=?');$s->execute([$slug]);return (int)$s->fetchColumn()===1;}}catch(Throwable $e){}return false;
}
function sf_content_comment_body(string $body): array {
  $body=trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u','',$body)??'');$length=function_exists('mb_strlen')?mb_strlen($body):strlen($body);
  if($length<2||$length>2000)return ['ok'=>false,'message'=>'Comment must be 2–2000 characters.','body'=>''];
  if(preg_match_all('~https?://~i',$body,$m)>2)return ['ok'=>false,'message'=>'Comments may contain at most two links.','body'=>''];
  if(preg_match('/(.)\1{14,}/u',$body))return ['ok'=>false,'message'=>'Comment contains excessive repeated characters.','body'=>''];
  return ['ok'=>true,'message'=>'','body'=>$body];
}
