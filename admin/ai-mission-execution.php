<?php
$pageTitle = 'AI Mission Execution Console';
$pageDescription = 'Run approved AI operation missions one routed item at a time.';
$pageClass = 'membership-page admin-catalog-page ai-mission-execution-page';
require_once __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/ai_platform_execution.php';
require_once __DIR__ . '/../includes/ai_autonomy_policies.php';

function sf_aimx_ready(): bool { return sf_admin_table_exists('ai_operation_missions') && sf_admin_table_exists('ai_operation_mission_items'); }
function sf_aimx_actions_ready(): bool { return sf_admin_table_exists('ai_platform_actions'); }
function sf_aimx_logs_ready(): bool { return sf_admin_table_exists('ai_platform_action_executions'); }
function sf_aimx_text($value, string $fallback = 'Not set'): string { $text = trim((string)($value ?? '')); return $text === '' ? $fallback : $text; }
function sf_aimx_user_id(): ?int { return function_exists('sf_current_user_id') ? sf_current_user_id() : null; }
function sf_aimx_missions(): array {
  if (!sf_aimx_ready()) return [];
  return sf_admin_fetch_all("SELECT m.*, COUNT(i.id) item_count, SUM(i.item_status = 'completed') completed_count, SUM(i.item_status IN ('blocked','failed')) blocked_count FROM ai_operation_missions m LEFT JOIN ai_operation_mission_items i ON i.mission_id = m.id WHERE m.mission_status IN ('approved','in_progress','blocked','completed') GROUP BY m.id ORDER BY FIELD(m.mission_status,'in_progress','approved','blocked','completed'), m.updated_at DESC, m.id DESC LIMIT 80");
}
function sf_aimx_items(int $missionId): array {
  if (!sf_aimx_ready() || $missionId <= 0) return [];
  return sf_admin_fetch_all('SELECT i.*, a.title, a.action_type, a.action_area, a.risk_level, a.approval_status, a.execution_status FROM ai_operation_mission_items i LEFT JOIN ai_platform_actions a ON a.id = i.platform_action_id WHERE i.mission_id = ? ORDER BY i.item_order ASC, i.id ASC', [$missionId]);
}
function sf_aimx_counts(): array {
  return [
    'approved'=>sf_aimx_ready() ? (int)(sf_admin_fetch_one("SELECT COUNT(*) total FROM ai_operation_missions WHERE mission_status = 'approved'")['total'] ?? 0) : 0,
    'in_progress'=>sf_aimx_ready() ? (int)(sf_admin_fetch_one("SELECT COUNT(*) total FROM ai_operation_missions WHERE mission_status = 'in_progress'")['total'] ?? 0) : 0,
    'completed'=>sf_aimx_ready() ? (int)(sf_admin_fetch_one("SELECT COUNT(*) total FROM ai_operation_missions WHERE mission_status = 'completed'")['total'] ?? 0) : 0,
    'blocked'=>sf_aimx_ready() ? (int)(sf_admin_fetch_one("SELECT COUNT(*) total FROM ai_operation_missions WHERE mission_status = 'blocked'")['total'] ?? 0) : 0,
  ];
}
function sf_aimx_next_item(int $missionId): ?array {
  if (!sf_aimx_ready() || !sf_aimx_actions_ready() || $missionId <= 0) return null;
  return sf_admin_fetch_one("SELECT i.*, a.approval_status, a.execution_status, a.action_type, a.risk_level, a.title FROM ai_operation_mission_items i INNER JOIN ai_platform_actions a ON a.id = i.platform_action_id WHERE i.mission_id = ? AND i.item_status IN ('pending','ready') AND a.approval_status = 'ready_for_execution' AND a.execution_status = 'ready' ORDER BY i.item_order ASC, i.id ASC LIMIT 1", [$missionId]);
}
function sf_aimx_remaining_count(int $missionId): int {
  if (!sf_aimx_ready() || $missionId <= 0) return 0;
  return (int)(sf_admin_fetch_one("SELECT COUNT(*) total FROM ai_operation_mission_items WHERE mission_id = ? AND item_status NOT IN ('completed','skipped')", [$missionId])['total'] ?? 0);
}
function sf_aimx_mark_mission_terminal_if_done(int $missionId): void {
  if (sf_aimx_remaining_count($missionId) === 0) sf_admin_execute("UPDATE ai_operation_missions SET mission_status = 'completed', completed_at = NOW() WHERE id = ?", [$missionId]);
}
function sf_aimx_item_message(array $result): string { return sf_aimx_text($result['message'] ?? '', 'Execution route finished.'); }

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'run_next_item') {
  $missionId = sf_admin_int($_POST['mission_id'] ?? null, 0) ?? 0;
  if (!sf_aimx_ready()) {
    sf_admin_flash('error', 'Mission SQL is not ready. Import database/ai_operation_missions.sql first.');
  } elseif (!sf_aimx_actions_ready() || !sf_aimx_logs_ready()) {
    sf_admin_flash('error', 'AI action registry or execution log SQL is not ready.');
  } elseif ($missionId <= 0) {
    sf_admin_flash('error', 'Missing mission id.');
  } else {
    $mission = sf_admin_fetch_one('SELECT * FROM ai_operation_missions WHERE id = ? LIMIT 1', [$missionId]);
    if (!$mission || !in_array((string)($mission['mission_status'] ?? ''), ['approved','in_progress'], true)) {
      sf_admin_flash('error', 'Mission must be approved or in progress before execution.');
    } else {
      $item = sf_aimx_next_item($missionId);
      if (!$item) {
        sf_admin_flash('error', 'No executable mission item is ready. Approve an action card as ready_for_execution first.');
      } else {
        $beforeItem = $item;
        sf_admin_execute("UPDATE ai_operation_missions SET mission_status = 'in_progress' WHERE id = ?", [$missionId]);
        sf_admin_execute("UPDATE ai_operation_mission_items SET item_status = 'running', last_result_message = 'Execution started.' WHERE id = ?", [(int)$item['id']]);
        $result = sf_ai_exec_route_action((int)$item['platform_action_id']);
        $message = sf_aimx_item_message($result);
        $itemStatus = !empty($result['ok']) ? 'completed' : (((string)($result['status'] ?? '') === 'blocked') ? 'blocked' : 'failed');
        sf_admin_execute('UPDATE ai_operation_mission_items SET item_status = ?, last_result_message = ? WHERE id = ?', [$itemStatus, $message, (int)$item['id']]);
        $afterItem = sf_admin_fetch_one('SELECT * FROM ai_operation_mission_items WHERE id = ? LIMIT 1', [(int)$item['id']]);
        sf_admin_audit('ai_mission_item_routed_execution', 'ai_operation_mission_item', (int)$item['id'], $beforeItem, $afterItem);
        if ($itemStatus === 'completed') {
          sf_aimx_mark_mission_terminal_if_done($missionId);
          sf_admin_flash('success', $message);
        } else {
          if (!empty($item['stop_on_failure'])) sf_admin_execute("UPDATE ai_operation_missions SET mission_status = 'blocked' WHERE id = ?", [$missionId]);
          sf_admin_flash('error', $message);
        }
      }
    }
  }
  sf_admin_redirect(sf_url('admin/ai-mission-execution.php'));
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'skip_item') {
  $itemId = sf_admin_int($_POST['item_id'] ?? null, 0) ?? 0;
  if (!sf_aimx_ready() || $itemId <= 0) sf_admin_flash('error', 'Invalid item skip request.');
  else {
    $before = sf_admin_fetch_one('SELECT * FROM ai_operation_mission_items WHERE id = ? LIMIT 1', [$itemId]);
    $mission = $before ? sf_admin_fetch_one('SELECT * FROM ai_operation_missions WHERE id = ? LIMIT 1', [(int)$before['mission_id']]) : null;
    if (!$before || !$mission || !in_array((string)($mission['mission_status'] ?? ''), ['approved','in_progress','blocked'], true)) sf_admin_flash('error', 'Only approved, in-progress, or blocked mission items can be skipped.');
    else {
      $ok = sf_admin_execute("UPDATE ai_operation_mission_items SET item_status = 'skipped', last_result_message = 'Skipped manually by admin.' WHERE id = ? AND item_status NOT IN ('completed','running')", [$itemId]);
      $after = sf_admin_fetch_one('SELECT * FROM ai_operation_mission_items WHERE id = ? LIMIT 1', [$itemId]);
      if ($ok) { sf_admin_audit('ai_mission_item_skipped', 'ai_operation_mission_item', $itemId, $before, $after); sf_aimx_mark_mission_terminal_if_done((int)$before['mission_id']); }
      sf_admin_flash($ok ? 'success' : 'error', $ok ? 'Mission item skipped.' : 'Mission item could not be skipped.');
    }
  }
  sf_admin_redirect(sf_url('admin/ai-mission-execution.php'));
}

$missions = sf_aimx_missions();
$counts = sf_aimx_counts();
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('AI Operations', 'AI Mission Execution', 'Run approved AI missions one routed item at a time.', 'ai-mission-execution');
?>
<style>.ai-mission-execution-page .sf-exe-hero,.ai-mission-execution-page .sf-exe-stats,.ai-mission-execution-page .sf-exe-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}.ai-mission-execution-page .sf-exe-panel{padding:20px;border:1px solid rgba(232,198,127,.16);border-radius:22px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.025));box-shadow:0 20px 56px rgba(0,0,0,.22)}.ai-mission-execution-page .sf-exe-panel h2{margin:8px 0;color:#fff;font-size:clamp(30px,4vw,56px);letter-spacing:-.05em;line-height:.98}.ai-mission-execution-page .sf-exe-copy,.ai-mission-execution-page .sf-exe-panel p{color:rgba(255,255,255,.68);line-height:1.55}.ai-mission-execution-page .sf-exe-card{padding:16px;border:1px solid rgba(255,255,255,.08);border-radius:18px;background:rgba(0,0,0,.15)}.ai-mission-execution-page .sf-exe-card h3{color:#fff;margin:10px 0 8px}.ai-mission-execution-page .sf-exe-pill{display:inline-block;margin-top:8px;border:1px solid rgba(232,198,127,.18);border-radius:999px;padding:4px 8px;color:#f5d98d;font-size:11px;font-weight:900}.ai-mission-execution-page .sf-exe-item{border-top:1px solid rgba(255,255,255,.08);padding:10px 0}@media(max-width:1080px){.ai-mission-execution-page .sf-exe-hero,.ai-mission-execution-page .sf-exe-stats,.ai-mission-execution-page .sf-exe-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:720px){.ai-mission-execution-page .sf-exe-hero,.ai-mission-execution-page .sf-exe-stats,.ai-mission-execution-page .sf-exe-grid{grid-template-columns:1fr}}</style>
<section class="sf-exe-hero"><div class="sf-exe-panel" style="grid-column:span 3"><span class="sf-panel-eyebrow">Phase 20</span><h2>Mission execution</h2><p>Approved missions can run exactly one eligible action card at a time through the existing AI Execution Router.</p></div><div class="sf-exe-panel"><span class="sf-panel-eyebrow">Guardrail</span><h2>Step by step</h2><p>No mission runs freeform or in bulk. The console stops when an item fails or is blocked.</p></div></section>
<?php if (!sf_aimx_ready()): ?><section class="sf-story-v1-warning"><strong>SQL required:</strong> Import <code>database/ai_operation_missions.sql</code> before executing missions.</section><?php endif; ?>
<?php if (!sf_aimx_actions_ready()): ?><section class="sf-story-v1-warning"><strong>SQL required:</strong> Import Phase 13 action registry SQL before executing missions.</section><?php endif; ?>
<?php if (!sf_aimx_logs_ready()): ?><section class="sf-story-v1-warning"><strong>SQL required:</strong> Import Phase 14 execution log SQL before executing missions.</section><?php endif; ?>
<section class="sf-exe-stats"><div class="sf-exe-panel"><span class="sf-panel-eyebrow">Approved</span><h2><?= (int)$counts['approved'] ?></h2></div><div class="sf-exe-panel"><span class="sf-panel-eyebrow">In Progress</span><h2><?= (int)$counts['in_progress'] ?></h2></div><div class="sf-exe-panel"><span class="sf-panel-eyebrow">Completed</span><h2><?= (int)$counts['completed'] ?></h2></div><div class="sf-exe-panel"><span class="sf-panel-eyebrow">Blocked</span><h2><?= (int)$counts['blocked'] ?></h2></div></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Execution Board</span><h2><?= count($missions) ?> executable mission(s)</h2></div><div><a href="<?= sf_url('admin/ai-operations-missions.php') ?>">Mission Planner</a> · <a href="<?= sf_url('admin/ai-execution-router.php') ?>">Execution Router</a></div></div><?php if (!$missions): ?><p class="sf-exe-copy">No approved or active missions are ready for the execution console.</p><?php else: ?><div class="sf-exe-grid"><?php foreach ($missions as $mission): $items = sf_aimx_items((int)$mission['id']); $next = sf_aimx_next_item((int)$mission['id']); ?><article class="sf-exe-card"><?= sf_admin_status_badge((string)($mission['mission_status'] ?? 'approved')) ?><h3><?= sf_admin_h($mission['mission_title'] ?? 'Mission') ?></h3><p class="sf-exe-copy"><strong><?= sf_admin_h($mission['mission_area'] ?? 'ops') ?></strong> · Risk <?= sf_admin_h($mission['risk_level'] ?? 'medium') ?><br><?= (int)($mission['item_count'] ?? 0) ?> items · <?= (int)($mission['completed_count'] ?? 0) ?> complete · <?= (int)($mission['blocked_count'] ?? 0) ?> blocked</p><form method="post" class="sf-admin-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="run_next_item"><input type="hidden" name="mission_id" value="<?= (int)$mission['id'] ?>"><div class="sf-admin-form-actions"><button type="submit"<?= ($next && in_array((string)($mission['mission_status'] ?? ''), ['approved','in_progress'], true)) ? '' : ' disabled' ?>>Run Next Eligible Item</button></div></form><?php foreach ($items as $item): ?><div class="sf-exe-item"><span class="sf-exe-pill">#<?= (int)$item['item_order'] ?> · <?= sf_admin_h($item['item_status'] ?? 'pending') ?> · <?= sf_admin_h($item['action_type'] ?? 'route') ?></span><p class="sf-exe-copy"><strong><?= sf_admin_h($item['title'] ?? 'Action') ?></strong><br>Action: <?= sf_admin_h(($item['approval_status'] ?? 'missing') . ' / ' . ($item['execution_status'] ?? 'missing')) ?><br><?= sf_admin_h($item['last_result_message'] ?? '') ?></p><?php if (!in_array((string)($item['item_status'] ?? ''), ['completed','skipped','running'], true)): ?><form method="post" class="sf-admin-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="skip_item"><input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>"><div class="sf-admin-form-actions"><button type="submit">Skip Item</button></div></form><?php endif; ?></div><?php endforeach; ?></article><?php endforeach; ?></div><?php endif; ?></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Safety</span><h2>Router-controlled mission execution</h2></div></div><p class="sf-exe-copy">This console only calls the existing AI Execution Router for one approved and ready action at a time. Router policies still block disallowed, critical, unknown, publishing, payment, billing, subscription, provider, sending, or destructive actions.</p></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
