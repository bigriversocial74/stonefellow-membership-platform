<?php
$pageTitle = 'AI Autonomy Policies';
$pageDescription = 'Manage route-level autonomy rules for supervised AI platform control.';
$pageClass = 'membership-page admin-catalog-page ai-autonomy-policies-page';
require_once __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/ai_platform_execution.php';
require_once __DIR__ . '/../includes/ai_autonomy_policies.php';

function sf_aiap_risks(): array { return ['low'=>'Low','medium'=>'Medium','high'=>'High','critical'=>'Critical']; }
function sf_aiap_bool($value): int { return !empty($value) ? 1 : 0; }
function sf_aiap_policy_rows(): array { return sf_ai_policy_ready() ? sf_admin_fetch_all('SELECT * FROM ai_autonomy_policies ORDER BY is_blocked DESC, policy_area ASC, route_key ASC') : []; }
function sf_aiap_policy_map(): array { $map = []; foreach (sf_aiap_policy_rows() as $row) $map[(string)$row['route_key']] = $row; return $map; }
function sf_aiap_counts(array $routes, array $policies): array {
  $counts = ['routes'=>count($routes),'policies'=>count($policies),'blocked'=>0,'approval'=>0,'auto'=>0];
  foreach ($routes as $key => $route) { $policy = $policies[$key] ?? sf_ai_policy_default_for_route($key, $route); if (!empty($policy['is_blocked']) || ($policy['autonomy_level'] ?? '') === 'blocked') $counts['blocked']++; if (!empty($policy['requires_approval'])) $counts['approval']++; if (($policy['autonomy_level'] ?? '') === 'auto_execute_low_risk') $counts['auto']++; }
  return $counts;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'seed_defaults') {
  if (!sf_ai_policy_ready()) {
    sf_admin_flash('error', 'AI autonomy policy SQL is not ready. Import database/ai_autonomy_policies.sql first.');
  } else {
    $count = sf_ai_policy_seed_missing_defaults(sf_ai_exec_routes());
    sf_admin_flash('success', $count > 0 ? 'Missing default autonomy policies created for ' . $count . ' route(s).' : 'No missing defaults found. Existing edited policies were preserved.');
  }
  sf_admin_redirect(sf_url('admin/ai-autonomy-policies.php'));
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'save_policy') {
  $routes = sf_ai_exec_routes();
  $routeKey = trim((string)($_POST['route_key'] ?? ''));
  $level = trim((string)($_POST['autonomy_level'] ?? 'approval_required'));
  $risk = trim((string)($_POST['risk_level'] ?? 'medium'));
  if (!isset($routes[$routeKey])) {
    sf_admin_flash('error', 'Route is not allowlisted.');
  } elseif (!array_key_exists($level, sf_ai_policy_levels())) {
    sf_admin_flash('error', 'Autonomy level is invalid.');
  } elseif (!array_key_exists($risk, sf_aiap_risks())) {
    sf_admin_flash('error', 'Risk level is invalid.');
  } elseif (!sf_ai_policy_ready()) {
    sf_admin_flash('error', 'AI autonomy policy SQL is not ready.');
  } else {
    $isBlocked = $level === 'blocked' ? 1 : sf_aiap_bool($_POST['is_blocked'] ?? 0);
    $requiresApproval = $isBlocked ? 1 : sf_aiap_bool($_POST['requires_approval'] ?? 0);
    if ($risk === 'critical') { $isBlocked = 1; $requiresApproval = 1; $level = 'blocked'; }
    $data = ['policy_area'=>sf_ai_policy_area_for_route($routeKey),'route_key'=>$routeKey,'autonomy_level'=>$level,'requires_approval'=>$requiresApproval,'is_blocked'=>$isBlocked,'risk_level'=>$risk,'notes'=>trim((string)($_POST['notes'] ?? ''))];
    $before = sf_admin_fetch_one('SELECT * FROM ai_autonomy_policies WHERE route_key = ? LIMIT 1', [$routeKey]);
    $ok = sf_ai_policy_upsert($data);
    $after = sf_admin_fetch_one('SELECT * FROM ai_autonomy_policies WHERE route_key = ? LIMIT 1', [$routeKey]);
    if ($ok) sf_admin_audit('ai_autonomy_policy_saved', 'ai_autonomy_policy', (int)($after['id'] ?? 0), $before, $after);
    sf_admin_flash($ok ? 'success' : 'error', $ok ? 'Autonomy policy saved.' : 'Autonomy policy could not be saved.');
  }
  sf_admin_redirect(sf_url('admin/ai-autonomy-policies.php'));
}

$routes = sf_ai_exec_routes();
$policies = sf_aiap_policy_map();
$counts = sf_aiap_counts($routes, $policies);
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('AI Operations', 'AI Autonomy Policies', 'Configure what AI can propose, draft, execute after approval, or never do.', 'ai-autonomy-policies');
?>
<style>
.ai-autonomy-policies-page .sf-pol-hero,.ai-autonomy-policies-page .sf-pol-stats,.ai-autonomy-policies-page .sf-pol-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}.ai-autonomy-policies-page .sf-pol-panel{padding:20px;border:1px solid rgba(232,198,127,.16);border-radius:22px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.025));box-shadow:0 20px 56px rgba(0,0,0,.22)}.ai-autonomy-policies-page .sf-pol-panel h2{margin:8px 0;color:#fff;font-size:clamp(30px,4vw,56px);letter-spacing:-.05em;line-height:.98}.ai-autonomy-policies-page .sf-pol-copy,.ai-autonomy-policies-page .sf-pol-panel p{color:rgba(255,255,255,.68);line-height:1.55}.ai-autonomy-policies-page .sf-pol-card{padding:16px;border:1px solid rgba(255,255,255,.08);border-radius:18px;background:rgba(0,0,0,.15)}.ai-autonomy-policies-page .sf-pol-card h3{color:#fff;margin:10px 0 8px}.ai-autonomy-policies-page .sf-pol-pill{display:inline-block;margin-top:8px;border:1px solid rgba(232,198,127,.18);border-radius:999px;padding:4px 8px;color:#f5d98d;font-size:11px;font-weight:900}.ai-autonomy-policies-page textarea{width:100%;min-height:70px;resize:vertical}@media(max-width:1080px){.ai-autonomy-policies-page .sf-pol-hero,.ai-autonomy-policies-page .sf-pol-stats,.ai-autonomy-policies-page .sf-pol-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:720px){.ai-autonomy-policies-page .sf-pol-hero,.ai-autonomy-policies-page .sf-pol-stats,.ai-autonomy-policies-page .sf-pol-grid{grid-template-columns:1fr}}
</style>
<section class="sf-pol-hero"><div class="sf-pol-panel" style="grid-column:span 3"><span class="sf-panel-eyebrow">Phase 17</span><h2>AI permission layer</h2><p>Turn hardcoded AI route safety into explicit admin-managed policy settings for each allowlisted route.</p></div><div class="sf-pol-panel"><span class="sf-panel-eyebrow">Guardrail</span><h2>Critical blocks</h2><p>Critical-risk routes are forced blocked. Publishing, billing, payments, subscriptions, and destructive actions stay outside this layer.</p></div></section>
<?php if (!sf_ai_policy_ready()): ?><section class="sf-story-v1-warning"><strong>SQL required:</strong> Import <code>database/ai_autonomy_policies.sql</code> before saving autonomy policies.</section><?php endif; ?>
<section class="sf-pol-stats"><div class="sf-pol-panel"><span class="sf-panel-eyebrow">Routes</span><h2><?= (int)$counts['routes'] ?></h2></div><div class="sf-pol-panel"><span class="sf-panel-eyebrow">Saved Policies</span><h2><?= (int)$counts['policies'] ?></h2></div><div class="sf-pol-panel"><span class="sf-panel-eyebrow">Require Approval</span><h2><?= (int)$counts['approval'] ?></h2></div><div class="sf-pol-panel"><span class="sf-panel-eyebrow">Blocked</span><h2><?= (int)$counts['blocked'] ?></h2></div></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Defaults</span><h2>Seed missing route policies</h2></div><div><a href="<?= sf_url('admin/ai-platform-control.php') ?>">AI Control Center</a> · <a href="<?= sf_url('admin/ai-execution-router.php') ?>">Execution Router</a></div></div><p class="sf-pol-copy">Create one missing policy record for each allowlisted route. Existing policies are preserved and not overwritten.</p><form method="post" class="sf-admin-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="seed_defaults"><div class="sf-admin-form-actions"><button type="submit"<?= sf_ai_policy_ready() ? '' : ' disabled' ?>>Seed Missing Defaults</button></div></form></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Route Policies</span><h2><?= count($routes) ?> managed route(s)</h2></div></div><div class="sf-pol-grid"><?php foreach ($routes as $key => $route): $policy = $policies[$key] ?? sf_ai_policy_default_for_route($key, $route); ?><article class="sf-pol-card"><span class="sf-pol-pill"><?= sf_admin_h($key) ?> · <?= sf_admin_h($route['side_effect'] ?? 'route') ?></span><h3><?= sf_admin_h($route['label'] ?? $key) ?></h3><p class="sf-pol-copy"><?= sf_admin_h($route['description'] ?? '') ?></p><form method="post" class="sf-admin-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="save_policy"><input type="hidden" name="route_key" value="<?= sf_admin_h($key) ?>"><label>Autonomy Level<select name="autonomy_level"><?php foreach (sf_ai_policy_levels() as $levelKey => $label): ?><option value="<?= sf_admin_h($levelKey) ?>"<?= (string)($policy['autonomy_level'] ?? '') === $levelKey ? ' selected' : '' ?>><?= sf_admin_h($label) ?></option><?php endforeach; ?></select></label><label>Risk<select name="risk_level"><?php foreach (sf_aiap_risks() as $riskKey => $label): ?><option value="<?= sf_admin_h($riskKey) ?>"<?= (string)($policy['risk_level'] ?? '') === $riskKey ? ' selected' : '' ?>><?= sf_admin_h($label) ?></option><?php endforeach; ?></select></label><label><input type="checkbox" name="requires_approval" value="1"<?= !empty($policy['requires_approval']) ? ' checked' : '' ?>> Requires approval</label><label><input type="checkbox" name="is_blocked" value="1"<?= !empty($policy['is_blocked']) ? ' checked' : '' ?>> Block route</label><label>Notes<textarea name="notes"><?= sf_admin_h($policy['notes'] ?? '') ?></textarea></label><div class="sf-admin-form-actions"><button type="submit"<?= sf_ai_policy_ready() ? '' : ' disabled' ?>>Save Policy</button></div></form></article><?php endforeach; ?></div></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Safety</span><h2>Policy-backed AI control</h2></div></div><p class="sf-pol-copy">These policies do not add new powers by themselves. They constrain existing allowlisted routes and give future AI managers a single source of truth for proposal and execution rules.</p></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
