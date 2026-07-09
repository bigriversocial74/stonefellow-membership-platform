<?php
function sf_ai_mission_ready(): bool { return function_exists('sf_admin_table_exists') && sf_admin_table_exists('ai_operation_missions') && sf_admin_table_exists('ai_operation_mission_items'); }
function sf_ai_mission_actions_ready(): bool { return function_exists('sf_admin_table_exists') && sf_admin_table_exists('ai_platform_actions'); }
function sf_ai_mission_logs_ready(): bool { return function_exists('sf_admin_table_exists') && sf_admin_table_exists('ai_platform_action_executions'); }
function sf_ai_mission_text($value, string $fallback = 'Not set'): string { $text = trim((string)($value ?? '')); return $text === '' ? $fallback : $text; }
function sf_ai_mission_missions(): array {
  if (!sf_ai_mission_ready()) return [];
  return sf_admin_fetch_all("SELECT m.*, COUNT(i.id) item_count, SUM(i.item_status = 'completed') completed_count, SUM(i.item_status IN ('blocked','failed')) blocked_count FROM ai_operation_missions m LEFT JOIN ai_operation_mission_items i ON i.mission_id = m.id WHERE m.mission_status IN ('approved','in_progress','blocked','completed') GROUP BY m.id ORDER BY FIELD(m.mission_status,'in_progress','approved','blocked','completed'), m.updated_at DESC, m.id DESC LIMIT 80");
}
function sf_ai_mission_items(int $missionId): array { return sf_ai_mission_ready() && $missionId > 0 ? sf_admin_fetch_all('SELECT i.*, a.title, a.action_type, a.action_area, a.risk_level, a.approval_status, a.execution_status FROM ai_operation_mission_items i LEFT JOIN ai_platform_actions a ON a.id = i.platform_action_id WHERE i.mission_id = ? ORDER BY i.item_order ASC, i.id ASC', [$missionId]) : []; }
function sf_ai_mission_counts(): array { return ['approved'=>sf_ai_mission_ready() ? (int)(sf_admin_fetch_one("SELECT COUNT(*) total FROM ai_operation_missions WHERE mission_status = 'approved'")['total'] ?? 0) : 0,'in_progress'=>sf_ai_mission_ready() ? (int)(sf_admin_fetch_one("SELECT COUNT(*) total FROM ai_operation_missions WHERE mission_status = 'in_progress'")['total'] ?? 0) : 0,'completed'=>sf_ai_mission_ready() ? (int)(sf_admin_fetch_one("SELECT COUNT(*) total FROM ai_operation_missions WHERE mission_status = 'completed'")['total'] ?? 0) : 0,'blocked'=>sf_ai_mission_ready() ? (int)(sf_admin_fetch_one("SELECT COUNT(*) total FROM ai_operation_missions WHERE mission_status = 'blocked'")['total'] ?? 0) : 0]; }
function sf_ai_mission_next_item(int $missionId): ?array {
  if (!sf_ai_mission_ready() || !sf_ai_mission_actions_ready() || $missionId <= 0) return null;
  return sf_admin_fetch_one("SELECT i.*, a.approval_status, a.execution_status, a.action_type, a.risk_level, a.title FROM ai_operation_mission_items i INNER JOIN ai_platform_actions a ON a.id = i.platform_action_id WHERE i.mission_id = ? AND i.item_status IN ('pending','ready') AND a.approval_status = 'ready_for_execution' AND a.execution_status = 'ready' ORDER BY i.item_order ASC, i.id ASC LIMIT 1", [$missionId]);
}
function sf_ai_mission_remaining_count(int $missionId): int { return sf_ai_mission_ready() && $missionId > 0 ? (int)(sf_admin_fetch_one("SELECT COUNT(*) total FROM ai_operation_mission_items WHERE mission_id = ? AND item_status NOT IN ('completed','skipped')", [$missionId])['total'] ?? 0) : 0; }
function sf_ai_mission_blocked_item_count(int $missionId): int { return sf_ai_mission_ready() && $missionId > 0 ? (int)(sf_admin_fetch_one("SELECT COUNT(*) total FROM ai_operation_mission_items WHERE mission_id = ? AND item_status IN ('blocked','failed','running')", [$missionId])['total'] ?? 0) : 0; }
function sf_ai_mission_mark_terminal_if_done(int $missionId): void { if (sf_ai_mission_remaining_count($missionId) === 0) sf_admin_execute("UPDATE ai_operation_missions SET mission_status = 'completed', completed_at = NOW() WHERE id = ?", [$missionId]); }
function sf_ai_mission_resume_if_unblocked(int $missionId): void { if (sf_ai_mission_remaining_count($missionId) > 0 && sf_ai_mission_blocked_item_count($missionId) === 0) sf_admin_execute("UPDATE ai_operation_missions SET mission_status = 'approved' WHERE id = ? AND mission_status = 'blocked'", [$missionId]); }
function sf_ai_mission_item_message(array $result): string { return sf_ai_mission_text($result['message'] ?? '', 'Execution route finished.'); }
function sf_ai_mission_run_next(int $missionId): array {
  if (!sf_ai_mission_ready()) return ['ok'=>false,'message'=>'Mission SQL is not ready. Import database/ai_operation_missions.sql first.'];
  if (!sf_ai_mission_actions_ready() || !sf_ai_mission_logs_ready()) return ['ok'=>false,'message'=>'AI action registry or execution log SQL is not ready.'];
  if ($missionId <= 0) return ['ok'=>false,'message'=>'Missing mission id.'];
  $mission = sf_admin_fetch_one('SELECT * FROM ai_operation_missions WHERE id = ? LIMIT 1', [$missionId]);
  if (!$mission || !in_array((string)($mission['mission_status'] ?? ''), ['approved','in_progress'], true)) return ['ok'=>false,'message'=>'Mission must be approved or in progress before execution.'];
  $item = sf_ai_mission_next_item($missionId);
  if (!$item) return ['ok'=>false,'message'=>'No executable mission item is ready. Approve an action card as ready_for_execution first.'];
  $beforeItem = $item;
  sf_admin_execute("UPDATE ai_operation_missions SET mission_status = 'in_progress' WHERE id = ?", [$missionId]);
  sf_admin_execute("UPDATE ai_operation_mission_items SET item_status = 'running', last_result_message = 'Execution started.' WHERE id = ?", [(int)$item['id']]);
  $result = sf_ai_exec_route_action((int)$item['platform_action_id']);
  $message = sf_ai_mission_item_message($result);
  $itemStatus = !empty($result['ok']) ? 'completed' : (((string)($result['status'] ?? '') === 'blocked') ? 'blocked' : 'failed');
  sf_admin_execute('UPDATE ai_operation_mission_items SET item_status = ?, last_result_message = ? WHERE id = ?', [$itemStatus, $message, (int)$item['id']]);
  $afterItem = sf_admin_fetch_one('SELECT * FROM ai_operation_mission_items WHERE id = ? LIMIT 1', [(int)$item['id']]);
  sf_admin_audit('ai_mission_item_routed_execution', 'ai_operation_mission_item', (int)$item['id'], $beforeItem, $afterItem);
  if ($itemStatus === 'completed') { sf_ai_mission_mark_terminal_if_done($missionId); return ['ok'=>true,'message'=>$message,'item_status'=>$itemStatus]; }
  if (!empty($item['stop_on_failure'])) sf_admin_execute("UPDATE ai_operation_missions SET mission_status = 'blocked' WHERE id = ?", [$missionId]);
  return ['ok'=>false,'message'=>$message,'item_status'=>$itemStatus];
}
function sf_ai_mission_skip_item(int $itemId): array {
  if (!sf_ai_mission_ready() || $itemId <= 0) return ['ok'=>false,'message'=>'Invalid item skip request.'];
  $before = sf_admin_fetch_one('SELECT * FROM ai_operation_mission_items WHERE id = ? LIMIT 1', [$itemId]);
  $mission = $before ? sf_admin_fetch_one('SELECT * FROM ai_operation_missions WHERE id = ? LIMIT 1', [(int)$before['mission_id']]) : null;
  if (!$before || !$mission || !in_array((string)($mission['mission_status'] ?? ''), ['approved','in_progress','blocked'], true)) return ['ok'=>false,'message'=>'Only approved, in-progress, or blocked mission items can be skipped.'];
  $ok = sf_admin_execute("UPDATE ai_operation_mission_items SET item_status = 'skipped', last_result_message = 'Skipped manually by admin.' WHERE id = ? AND item_status NOT IN ('completed','running')", [$itemId]);
  $after = sf_admin_fetch_one('SELECT * FROM ai_operation_mission_items WHERE id = ? LIMIT 1', [$itemId]);
  if ($ok) { sf_admin_audit('ai_mission_item_skipped', 'ai_operation_mission_item', $itemId, $before, $after); sf_ai_mission_mark_terminal_if_done((int)$before['mission_id']); sf_ai_mission_resume_if_unblocked((int)$before['mission_id']); }
  return ['ok'=>$ok,'message'=>$ok ? 'Mission item skipped. Mission was resumed if no blocked items remain.' : 'Mission item could not be skipped.'];
}
function sf_ai_mission_recover_running(int $missionId): array {
  if (!sf_ai_mission_ready() || $missionId <= 0) return ['ok'=>false,'message'=>'Invalid mission recovery request.'];
  $mission = sf_admin_fetch_one('SELECT * FROM ai_operation_missions WHERE id = ? LIMIT 1', [$missionId]);
  if (!$mission || !in_array((string)($mission['mission_status'] ?? ''), ['approved','in_progress','blocked'], true)) return ['ok'=>false,'message'=>'Mission cannot be recovered in its current state.'];
  $ok = sf_admin_execute("UPDATE ai_operation_mission_items SET item_status = 'ready', last_result_message = 'Recovered from stale running state by admin.' WHERE mission_id = ? AND item_status = 'running'", [$missionId]);
  if ($ok) { sf_ai_mission_resume_if_unblocked($missionId); sf_admin_audit('ai_mission_running_items_recovered', 'ai_operation_mission', $missionId, null, ['mission_id'=>$missionId]); }
  return ['ok'=>$ok,'message'=>$ok ? 'Running mission items were recovered to ready state.' : 'No running mission items were recovered.'];
}
?>
