<?php
require_once __DIR__ . '/../includes/engagement.php';
$method=strtoupper((string)($_SERVER['REQUEST_METHOD']??'GET'));
if($method==='GET'){
  $type=trim((string)($_GET['content_type']??'episode'));if(!in_array($type,sf_content_allowed_types(),true))sf_json_response(['ok'=>false,'error'=>'invalid_content_type'],422);
  $id=max(0,(int)($_GET['content_id']??0));$slug=sf_content_safe_slug((string)($_GET['slug']??''));
  sf_json_response(['ok'=>true,'comments'=>sf_comments_for($type,$id,$slug,'approved')]);
}
if($method!=='POST')sf_json_response(['ok'=>false,'error'=>'method_not_allowed'],405);
$user=sf_auth_user();if(!$user)sf_json_response(['ok'=>false,'error'=>'login_required'],401);
$origin=trim((string)($_SERVER['HTTP_ORIGIN']??''));$referer=trim((string)($_SERVER['HTTP_REFERER']??''));$source=$origin!==''?$origin:$referer;
if($source!==''){$host=strtolower((string)(parse_url($source,PHP_URL_HOST)??''));$requestHost=strtolower(preg_replace('/:\d+$/','',sf_security_request_host())??'');if($host===''||!hash_equals($requestHost,$host))sf_json_response(['ok'=>false,'error'=>'cross_origin_request_blocked'],403);}
$data=sf_request_json(32768);if(!$data&&$_POST)$data=$_POST;$csrf=(string)($data['csrf_token']??$_SERVER['HTTP_X_CSRF_TOKEN']??'');if(!sf_verify_csrf($csrf))sf_json_response(['ok'=>false,'error'=>'csrf_failed'],403);
$action=(string)($data['action']??'comment');
if($action==='react'){$result=sf_comment_react((int)$user['id'],(string)($data['target_type']??'comment'),(int)($data['target_id']??0),(string)($data['reaction_type']??'like'));sf_json_response($result,!empty($result['ok'])?200:422);}
if($action!=='comment')sf_json_response(['ok'=>false,'error'=>'unsupported_action'],422);
$result=sf_comment_create((int)$user['id'],(string)($data['content_type']??'episode'),(int)($data['content_id']??0),(string)($data['slug']??''),(string)($data['body']??''),isset($data['parent_comment_id'])?(int)$data['parent_comment_id']:null);
sf_json_response($result,!empty($result['ok'])?201:422);
