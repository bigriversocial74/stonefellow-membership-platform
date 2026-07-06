<?php
require_once __DIR__ . '/../includes/member_lifecycle_support.php';
$user = sf_auth_user();
if (!$user) sf_json_response(['ok'=>false,'error'=>'login_required'],401);
$isAdmin = (($user['role'] ?? '') === 'admin' || sf_current_access_level() === 'admin');
$userId = (int)$user['id'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
  $ticketId = (int)($_GET['ticket_id'] ?? 0);
  if ($ticketId) {
    $ticket = sf_support_ticket($ticketId,$isAdmin ? 0 : $userId);
    sf_json_response(['ok'=>(bool)$ticket,'ticket'=>$ticket,'messages'=>$ticket ? sf_support_messages($ticketId) : []]);
  }
  sf_json_response(['ok'=>true,'summary'=>$isAdmin ? sf_support_summary() : null,'tickets'=>sf_support_tickets($isAdmin ? 0 : $userId,(string)($_GET['status'] ?? ''),isset($_GET['limit']) ? (int)$_GET['limit'] : 100)]);
}
$data = sf_request_json();
if (!$data && $_POST) $data = $_POST;
$action = (string)($data['action'] ?? 'create_ticket');
if ($action === 'create_ticket') {
  $id = sf_support_create_ticket($isAdmin ? (int)($data['user_id'] ?? 0) : $userId,(string)($data['subject'] ?? ''),(string)($data['body'] ?? ''),(string)($data['category'] ?? 'other'),(string)($data['priority'] ?? 'medium'),$isAdmin ? 'admin' : 'member',$data);
  sf_json_response(['ok'=>$id>0,'ticket_id'=>$id],$id?200:422);
}
if ($action === 'reply_ticket') {
  $ticketId = (int)($data['ticket_id'] ?? 0);
  $ticket = sf_support_ticket($ticketId,$isAdmin ? 0 : $userId);
  if (!$ticket) sf_json_response(['ok'=>false,'error'=>'ticket_not_found'],404);
  sf_json_response(['ok'=>sf_support_add_message($ticketId,$userId,(string)($data['message'] ?? ''),$isAdmin ? 'admin' : 'member',!empty($data['is_internal']) && $isAdmin)]);
}
if ($action === 'update_ticket' && $isAdmin) sf_json_response(['ok'=>sf_support_update_ticket((int)($data['ticket_id'] ?? 0),(string)($data['status'] ?? 'open'),(string)($data['priority'] ?? ''),$userId)]);
sf_json_response(['ok'=>false,'error'=>'unsupported_action'],422);
