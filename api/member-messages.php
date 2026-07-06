<?php
require_once __DIR__ . '/../includes/ops_scheduler_messaging.php';
$user = sf_auth_user();
if (!$user) sf_json_response(['ok'=>false,'error'=>'login_required'],401);
$userId=(int)$user['id'];
$method=$_SERVER['REQUEST_METHOD']??'GET';
if($method==='GET') sf_json_response(['ok'=>true,'messages'=>sf_msg_member_messages($userId,(string)($_GET['status']??''),isset($_GET['limit'])?(int)$_GET['limit']:100)]);
$data=sf_request_json(); if(!$data&&$_POST)$data=$_POST;
sf_json_response(['ok'=>sf_msg_update_member_message($userId,(int)($data['message_id']??0),(string)($data['status']??'read'))]);
