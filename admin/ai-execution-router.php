<?php
$pageTitle = 'AI Execution Router';
$pageDescription = 'Run approved AI platform actions through audited allowlisted routes.';
$pageClass = 'membership-page admin-catalog-page ai-execution-router-page';
require_once __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/ai_platform_execution.php';

function sf_aier_ready_actions(): array {
  if (!sf_ai_exec_registry_ready()) return [];
  return sf_admin_fetch_all("SELECT * FROM ai_platform_actions WHERE approval_status = 'ready_for_execution' AND execution_status = 'ready' ORDER BY updated_at DESC, id DESC LIMIT 80");
}
function sf_aier_recent_logs(): array {
  if (!sf_ai_exec_log_ready()) return [];
  return sf_admin_fetch_all('SELECT e.*, a.title, a.action_area, a.action_type FROM ai_platform_action_executions e LEFT JOIN ai_platform_actions a ON a.id = e.platform_action_id ORDER BY e.created_at DESC, e.id DESC LIMIT 100');
}
function sf_aier_counts(): array {
  $counts = ['ready'=>0,'completed'=>0,'failed'=>0,'blocked'=>0,'started'=>0];
  if (sf_ai_exec_registry_ready()) $counts['ready'] = (int)(sf_admin_fetch_one("SELECT COUNT(*) total FROM ai_platform_actions WHERE approval_status = 'ready_for_execution' AND execution_status = 'ready'")['total'] ?? 0);
  if (sf_ai_exec_log_ready()) foreach (sf_admin_fetch_all('SELECT execution_status, COUNT(*) total FROM ai_platform_action_executions GROUP BY execution_status') as $row) { $key = (string)($row['execution_status'] ?? ''); if (isset($counts[$key])) $counts[$key] = (int)($row['total'] ?? 0); }
  return $counts;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'run_route') {
  $id = sf_admin_int($_POST['platform_action_id'] ?? null, 0) ?? 0;
  $result = $id > 0 ? sf_ai_exec_route_action($id) : ['ok'=>false,'message'=>'Missing platform action id.'];
  sf_admin_flash(!empty($result['ok']) ? 'success' : 'error', (string)($result['message'] ?? 'Execution route finished.'));
  sf_admin_redirect(sf_url('admin/ai-execution-router.php'));
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'block_route') {
  $id = sf_admin_int($_POST['platform_action_id'] ?? null, 0) ?? 0;
  if (!sf_ai_exec_registry_ready() || $id <= 0) {
    sf_admin_flash('error', 'Invalid platform action block request.');
  } else {
    $before = sf_admin_fetch_one('SELECT * FROM ai_platform_actions WHERE id = ? LIMIT 1', [$id]);
    $ok = sf_admin_execute("UPDATE ai_platform_actions SET approval_status = 'blocked', execution_status = 'cancelled' WHERE id = ?", [$id]);
    $after = sf_admin_fetch_one('SELECT * FROM ai_platform_actions WHERE id = ? LIMIT 1', [$id]);
    if ($ok) { sf_ai_exec_log($id, (string)($before['action_type'] ?? 'unknown'), 'blocked', 'Admin blocked this action before execution.'); sf_admin_audit('ai_platform_action_blocked_before_execution', 'ai_platform_action', $id, $before, $after); }
    sf_admin_flash($ok ? 'success' : 'error', $ok ? 'AI platform action blocked.' : 'AI platform action could not be blocked.');
  }
  sf_admin_redirect(sf_url('admin/ai-execution-router.php'));
}

$counts = sf_aier_counts();
$readyActions = sf_aier_ready_actions();
$logs = sf_aier_recent_logs();
$routes = sf_ai_exec_routes();
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('AI Operations', 'AI Execution Router', 'Run approved AI actions through audited allowlisted routes.', 'ai-execution-router');
?>
<style>
.ai-execution-router-page .sf-router-hero,.ai-execution-router-page .sf-router-stats,.ai-execution-router-page .sf-router-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}.ai-execution-router-page .sf-router-panel{padding:20px;border:1px solid rgba(232,198,127,.16);border-radius:22px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.025));box-shadow:0 20px 56px rgba(0,0,0,.22)}.ai-execution-router-page .sf-router-panel h2{margin:8px 0;color:#fff;font-size:clamp(30px,4vw,56px);letter-spacing:-.05em;line-height:.98}.ai-execution-router-page .sf-router-copy,.ai-execution-router-page .sf-router-panel p{color:rgba(255,255,255,.68);line-height:1.55}.ai-execution-router-page .sf-router-card{padding:16px;border:1px solid rgba(255,255,255,.08);border-radius:18px;background:rgba(0,0,0,.15)}.ai-execution-router-page .sf-router-card h3{color:#fff;margin:10px 0 8px}.ai-execution-router-page .sf-router-pill{display:inline-block;margin-top:8px;border:1px solid rgba(232,198,127,.18);border-radius:999px;padding:4px 8px;color:#f5d98d;font-size:11px;font-weight:900}.ai-execution-router-page code{display:block;white-space:normal;overflow-wrap:anywhere;margin-top:8px;color:#d7e6ff;background:rgba(0,0,0,.22);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:8px;font-size:12px}@media(max-width:1080px){.ai-execution-router-page .sf-router-hero,.ai-execution-router-page .sf-router-stats,.ai-execution-router-page .sf-router-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:720px){.ai-execution-router-page .sf-router-hero,.ai-execution-router-page .sf-router-stats,.ai-execution-router-page .sf-router-grid{grid-template-columns:1fr}}
</style>
<section class="sf-router-hero"><div class="sf-router-panel" style="grid-column:span 3"><span class="sf-panel-eyebrow">Phase 15</span><h2>Story execution routes</h2><p>Approved AI platform actions can now perform safe story/content operations through explicit, audited route keys.</p></div><div class="sf-router-panel"><span class="sf-panel-eyebrow">Guardrail</span><h2>No publish</h2><p>No route publishes, deletes records, calls providers, sends messages, or changes billing/subscriptions.</p></div></section>
<?php if (!sf_ai_exec_registry_ready()): ?><section class="sf-story-v1-warning"><strong>SQL required:</strong> Import <code>database/ai_platform_actions.sql</code> first.</section><?php endif; ?>
<?php if (!sf_ai_exec_log_ready()): ?><section class="sf-story-v1-warning"><strong>SQL required:</strong> Import <code>database/ai_platform_action_executions.sql</code> before running routes.</section><?php endif; ?>
<section class="sf-router-stats"><div class="sf-router-panel"><span class="sf-panel-eyebrow">Ready</span><h2><?= (int)$counts['ready'] ?></h2></div><div class="sf-router-panel"><span class="sf-panel-eyebrow">Started</span><h2><?= (int)$counts['started'] ?></h2></div><div class="sf-router-panel"><span class="sf-panel-eyebrow">Completed</span><h2><?= (int)$counts['completed'] ?></h2></div><div class="sf-router-panel"><span class="sf-panel-eyebrow">Blocked</span><h2><?= (int)$counts['blocked'] ?></h2></div></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Allowlisted Routes</span><h2><?= count($routes) ?> route(s)</h2></div><div><a href="<?= sf_url('admin/ai-platform-control.php') ?>">AI Control Center</a></div></div><div class="sf-router-grid"><?php foreach ($routes as $key => $route): ?><article class="sf-router-card"><span class="sf-router-pill"><?= sf_admin_h($key) ?> · <?= sf_admin_h($route['side_effect']) ?></span><h3><?= sf_admin_h($route['label']) ?></h3><p class="sf-router-copy"><?= sf_admin_h($route['description']) ?></p><?php if (!empty($route['payload_hint'])): ?><code><?= sf_admin_h($route['payload_hint']) ?></code><?php endif; ?></article><?php endforeach; ?></div></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Ready Actions</span><h2><?= count($readyActions) ?> action(s)</h2></div></div><?php if (!$readyActions): ?><p class="sf-router-copy">No AI platform actions are ready for execution.</p><?php else: ?><div class="sf-router-grid"><?php foreach ($readyActions as $action): ?><article class="sf-router-card"><?= sf_admin_status_badge((string)($action['execution_status'] ?? 'ready')) ?><h3><?= sf_admin_h($action['title'] ?? 'AI Action') ?></h3><p class="sf-router-copy"><strong><?= sf_admin_h($action['action_type'] ?? 'route') ?></strong> · <?= sf_admin_h($action['action_area'] ?? 'area') ?> · Risk <?= sf_admin_h($action['risk_level'] ?? 'low') ?></p><p class="sf-router-copy"><?= sf_admin_h($action['description'] ?? '') ?></p><form method="post" class="sf-admin-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="run_route"><input type="hidden" name="platform_action_id" value="<?= (int)($action['id'] ?? 0) ?>"><div class="sf-admin-form-actions"><button type="submit"<?= (sf_ai_exec_registry_ready() && sf_ai_exec_log_ready()) ? '' : ' disabled' ?>>Run Routed Action</button></div></form><form method="post" class="sf-admin-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="block_route"><input type="hidden" name="platform_action_id" value="<?= (int)($action['id'] ?? 0) ?>"><div class="sf-admin-form-actions"><button type="submit">Block Action</button></div></form></article><?php endforeach; ?></div><?php endif; ?></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Execution Log</span><h2><?= count($logs) ?> recent event(s)</h2></div></div><?php if (!$logs): ?><p class="sf-router-copy">No execution events have been recorded.</p><?php else: ?><div class="sf-router-grid"><?php foreach ($logs as $log): ?><article class="sf-router-card"><?= sf_admin_status_badge((string)($log['execution_status'] ?? 'started')) ?><h3><?= sf_admin_h($log['title'] ?? ('Action #' . (int)($log['platform_action_id'] ?? 0))) ?></h3><p class="sf-router-copy"><strong><?= sf_admin_h($log['route_key'] ?? 'route') ?></strong> · <?= sf_admin_h($log['action_area'] ?? 'area') ?> · <?= sf_admin_h($log['created_at'] ?? '') ?></p><p class="sf-router-copy"><?= sf_admin_h($log['result_message'] ?? '') ?></p></article><?php endforeach; ?></div><?php endif; ?></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Safety</span><h2>Not arbitrary control</h2></div></div><p class="sf-router-copy">This router does not run freeform AI commands. It only executes known route keys that are coded, audited, approved, and visible in this page.</p></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
