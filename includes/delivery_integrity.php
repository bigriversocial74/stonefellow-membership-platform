<?php

declare(strict_types=1);
require_once __DIR__ . '/db.php';

function sf_delivery_env_int(string $name,int $default,int $min,int $max): int { $v=getenv($name);$n=($v!==false&&is_numeric($v))?(int)$v:$default;return max($min,min($max,$n)); }
function sf_delivery_clean_header(string $value,int $max=255): string { $value=preg_replace('/[\r\n\0]+/',' ',trim($value))??'';$value=preg_replace('/\s+/u',' ',$value)??'';return function_exists('mb_substr')?mb_substr($value,0,$max):substr($value,0,$max); }
function sf_delivery_clean_text(string $value,int $max=20000): string { $value=preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u','',$value)??'';return function_exists('mb_substr')?mb_substr($value,0,$max):substr($value,0,$max); }
function sf_delivery_safe_email(string $email): string { $email=strtolower(trim($email));return filter_var($email,FILTER_VALIDATE_EMAIL)&&!preg_match('/[\r\n]/',$email)?$email:''; }
function sf_delivery_safe_url(string $url): string { $url=trim($url);if($url==='')return '';if(str_starts_with($url,'/')&&!str_starts_with($url,'//'))return $url;if(!preg_match('~^https://~i',$url))return '';return $url; }
function sf_delivery_advisory_lock(PDO $pdo,string $name,int $timeout=3): bool { try{$s=$pdo->prepare('SELECT GET_LOCK(?,?)');$s->execute(['stonefellow:delivery:'.$name,max(0,min(30,$timeout))]);return (int)$s->fetchColumn()===1;}catch(Throwable $e){return false;} }
function sf_delivery_advisory_unlock(PDO $pdo,string $name): void { try{$s=$pdo->prepare('SELECT RELEASE_LOCK(?)');$s->execute(['stonefellow:delivery:'.$name]);}catch(Throwable $e){} }
function sf_delivery_json($value,array $default=[]): array { if(is_array($value))return $value;if(!is_string($value)||trim($value)==='')return $default;try{$decoded=json_decode($value,true,32,JSON_THROW_ON_ERROR);return is_array($decoded)?$decoded:$default;}catch(Throwable $e){return $default;} }
function sf_delivery_backoff_seconds(int $attempt): int { $base=sf_delivery_env_int('SF_NOTIFICATION_RETRY_BASE_SECONDS',60,10,3600);return min(86400,$base*(2**max(0,min(10,$attempt-1)))); }
function sf_delivery_retry_due(array $row): bool { $meta=sf_delivery_json($row['metadata_json']??null);$next=(string)($meta['next_attempt_at']??'');return $next===''||strtotime($next)<=time(); }
function sf_delivery_merge_metadata($current,array $changes): string { return json_encode(array_merge(sf_delivery_json($current),$changes),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR); }
function sf_delivery_idempotency_key(string $templateKey,array $recipient,array $vars,array $options=[]): string { $explicit=trim((string)($options['idempotency_key']??''));if($explicit!=='')return substr(hash('sha256',$explicit),0,64);$basis=[$templateKey,strtolower((string)($recipient['email']??'')),$vars,$options['notification_type']??'',date('Y-m-d')];return hash('sha256',json_encode($basis,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)); }
function sf_delivery_preference_enabled(int $userId,string $key,string $channel='email'): bool { if($userId<=0||!function_exists('sf_notify_table_exists')||!sf_notify_table_exists('notification_preferences'))return true;try{$s=sf_db()->prepare('SELECT is_enabled FROM notification_preferences WHERE user_id=? AND preference_key IN (?,"all_marketing") AND channel=? ORDER BY preference_key=? DESC LIMIT 1');$s->execute([$userId,$key,$channel,$key]);$v=$s->fetchColumn();return $v===false?true:(bool)$v;}catch(Throwable $e){return true;} }
function sf_delivery_transactional_type(string $type): bool { return in_array($type,['auth','billing','commerce','security','transactional','admin'],true); }
function sf_delivery_sanitize_email_html(string $html): string {
  $html=sf_delivery_clean_text($html,200000);
  $html=preg_replace('~<\s*(script|iframe|object|embed|form|input|button|textarea|select|style|link|meta)[^>]*>.*?<\s*/\s*\1\s*>~is','',$html)??$html;
  $html=preg_replace('~<\s*(script|iframe|object|embed|form|input|button|textarea|select|style|link|meta)\b[^>]*?/?>~is','',$html)??$html;
  $html=preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i','',$html)??$html;
  $html=preg_replace('/\s(href|src)\s*=\s*(["\'])\s*(javascript:|data:text\/html)[^"\']*\2/i',' $1="#"',$html)??$html;
  return $html;
}
function sf_delivery_template_variables(array $template): array { return sf_delivery_json($template['variables_json']??null); }
function sf_delivery_validate_template(array $data): array {
  $key=strtolower(trim((string)($data['template_key']??'')));$key=preg_replace('/[^a-z0-9_-]+/','_',$key)??'';
  $subject=sf_delivery_clean_header((string)($data['subject']??''),255);$html=sf_delivery_sanitize_email_html((string)($data['html_body']??''));$text=sf_delivery_clean_text((string)($data['text_body']??''),100000);
  $variables=sf_delivery_json($data['variables_json']??null,[]);$variables=array_values(array_unique(array_filter(array_map(static fn($v)=>preg_match('/^[a-zA-Z0-9_.-]{1,80}$/',(string)$v)?(string)$v:'',$variables))));
  $errors=[];if($key==='')$errors[]='Template key is required.';if($subject==='')$errors[]='Subject is required.';if(trim(strip_tags($html))==='')$errors[]='HTML body is required.';if(strlen($html)>200000)$errors[]='HTML body is too large.';
  preg_match_all('/{{\s*([a-zA-Z0-9_.-]+)\s*}}/',implode('\n',[$subject,$html,$text]),$matches);$used=array_values(array_unique($matches[1]??[]));$undeclared=array_values(array_diff($used,$variables));if($undeclared)$errors[]='Undeclared variables: '.implode(', ',$undeclared);
  return ['ok'=>!$errors,'errors'=>$errors,'template_key'=>$key,'subject'=>$subject,'html_body'=>$html,'text_body'=>$text,'variables'=>$variables];
}
function sf_delivery_webhook_secret(string $provider): string { $specific=getenv('SF_'.strtoupper(preg_replace('/[^a-z0-9]+/i','_',$provider)).'_WEBHOOK_SECRET');return trim((string)($specific?:getenv('SF_NOTIFICATION_WEBHOOK_SECRET')?:'')); }
function sf_delivery_webhook_signature_valid(string $provider,string $raw,string $provided): bool { $secret=sf_delivery_webhook_secret($provider);if(strlen($secret)<32||$provided==='')return false;$expected=hash_hmac('sha256',$raw,$secret);$provided=preg_replace('/^sha256=/i','',trim($provided))??'';return strlen($provided)===64&&hash_equals($expected,$provided); }
