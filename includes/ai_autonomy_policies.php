<?php
function sf_ai_policy_ready(): bool { return function_exists('sf_admin_table_exists') && sf_admin_table_exists('ai_autonomy_policies'); }
function sf_ai_policy_levels(): array { return ['observe_only'=>'Observe Only','propose_only'=>'Propose Only','draft_only'=>'Draft Only','approval_required'=>'Approval Required','auto_execute_low_risk'=>'Auto Execute Low Risk','blocked'=>'Blocked']; }
function sf_ai_policy_default_for_route(string $routeKey, array $route = []): array {
  $side = (string)($route['side_effect'] ?? 'registry_only');
  $risk = in_array($side, ['registry_only','status_update'], true) ? 'medium' : 'high';
  $level = 'approval_required';
  $blocked = 0;
  if ($routeKey === 'review') { $risk = 'low'; $level = 'propose_only'; }
  if (str_contains($routeKey, 'publish') || str_contains($routeKey, 'billing') || str_contains($routeKey, 'payment') || str_contains($routeKey, 'subscription')) { $risk = 'critical'; $level = 'blocked'; $blocked = 1; }
  return ['policy_area'=>sf_ai_policy_area_for_route($routeKey), 'route_key'=>$routeKey, 'autonomy_level'=>$level, 'requires_approval'=>1, 'is_blocked'=>$blocked, 'risk_level'=>$risk, 'notes'=>'Default policy generated from allowlisted route metadata.'];
}
function sf_ai_policy_area_for_route(string $routeKey): string {
  if (str_contains($routeKey, 'scene') || str_contains($routeKey, 'story') || str_contains($routeKey, 'character') || str_contains($routeKey, 'background')) return 'story';
  if (str_contains($routeKey, 'media') || str_contains($routeKey, 'generation')) return 'media';
  if (str_contains($routeKey, 'briefing')) return 'ops';
  return 'platform';
}
function sf_ai_policy_get(string $routeKey, array $route = []): array {
  if (!sf_ai_policy_ready()) return sf_ai_policy_default_for_route($routeKey, $route);
  $row = sf_admin_fetch_one('SELECT * FROM ai_autonomy_policies WHERE route_key = ? LIMIT 1', [$routeKey]);
  return $row ?: sf_ai_policy_default_for_route($routeKey, $route);
}
function sf_ai_policy_can_propose(string $routeKey, array $route = []): bool {
  $policy = sf_ai_policy_get($routeKey, $route);
  return empty($policy['is_blocked']) && ($policy['autonomy_level'] ?? '') !== 'blocked';
}
function sf_ai_policy_can_execute(string $routeKey, array $route = [], string $riskLevel = 'medium'): array {
  $policy = sf_ai_policy_get($routeKey, $route);
  if (!empty($policy['is_blocked']) || ($policy['autonomy_level'] ?? '') === 'blocked') return ['ok'=>false,'message'=>'Autonomy policy blocks this route.'];
  if (($policy['risk_level'] ?? 'medium') === 'critical' || $riskLevel === 'critical') return ['ok'=>false,'message'=>'Critical-risk route execution is blocked by autonomy policy.'];
  if (!empty($policy['requires_approval'])) return ['ok'=>true,'message'=>'Route is allowed after approval.'];
  if (($policy['autonomy_level'] ?? '') === 'auto_execute_low_risk' && $riskLevel === 'low') return ['ok'=>true,'message'=>'Route is allowed for low-risk auto execution.'];
  return ['ok'=>true,'message'=>'Route is policy-allowed.'];
}
function sf_ai_policy_upsert(array $data): bool {
  if (!sf_ai_policy_ready()) return false;
  $routeKey = trim((string)($data['route_key'] ?? ''));
  if ($routeKey === '') return false;
  $userId = function_exists('sf_current_user_id') ? sf_current_user_id() : null;
  $existing = sf_admin_fetch_one('SELECT * FROM ai_autonomy_policies WHERE route_key = ? LIMIT 1', [$routeKey]);
  if ($existing) {
    return sf_admin_execute('UPDATE ai_autonomy_policies SET policy_area = ?, autonomy_level = ?, requires_approval = ?, is_blocked = ?, risk_level = ?, notes = ?, updated_by_user_id = ? WHERE route_key = ?', [$data['policy_area'], $data['autonomy_level'], (int)$data['requires_approval'], (int)$data['is_blocked'], $data['risk_level'], $data['notes'], $userId, $routeKey]);
  }
  return sf_admin_execute('INSERT INTO ai_autonomy_policies (policy_area, route_key, autonomy_level, requires_approval, is_blocked, risk_level, notes, created_by_user_id, updated_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', [$data['policy_area'], $routeKey, $data['autonomy_level'], (int)$data['requires_approval'], (int)$data['is_blocked'], $data['risk_level'], $data['notes'], $userId, $userId]);
}
?>
