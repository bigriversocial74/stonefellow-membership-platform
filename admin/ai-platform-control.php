<?php
$pageTitle = 'AI Platform Control Center';
$pageDescription = 'Central AI action registry for supervised platform management.';
$pageClass = 'membership-page admin-catalog-page ai-platform-control-page';
require_once __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/ai_platform_execution.php';

function sf_aipc_ready(): bool { return sf_admin_table_exists('ai_platform_actions'); }
function sf_aipc_text($value, string $fallback = 'Not set'): string { $text = trim((string)($value ?? '')); return $text === '' ? $fallback : $text; }
function sf_aipc_snip($value, int $length = 160): string { $text = trim((string)($value ?? '')); return strlen($text) > $length ? rtrim(substr($text, 0, $length - 1)) . '…' : $text; }
function sf_aipc_statuses(): array { return ['draft'=>'Draft','proposed'=>'Proposed','approved'=>'Approved','rejected'=>'Rejected','blocked'=>'Blocked','ready_for_execution'=>'Ready for Execution']; }
function sf_aipc_route_options(): array { return function_exists('sf_ai_exec_routes') ? sf_ai_exec_routes() : ['review'=>['label'=>'Complete Review Action','description'=>'Review-only action','side_effect'=>'registry_only']]; }
function sf_aipc_domains(): array {
  return [
    ['area'=>'script','title'=>'Story + Script','risk'=>'medium','description'=>'Seasons, episodes, scenes, characters, story assets, and continuity workflow.'],
    ['area'=>'media','title'=>'Media Production','risk'=>'high','description'=>'Prompt prep, generation queues, media review, thumbnails, and production handoff.'],
    ['area'=>'publishing','title'=>'Publishing','risk'=>'critical','description'=>'Release readiness, public visibility, content schedules, and publishing gates.'],
    ['area'=>'membership','title'=>'Membership','risk'=>'high','description'=>'Member segments, access checks, subscriptions, notifications, and support triage.'],
    ['area'=>'commerce','title'=>'Commerce','risk'=>'critical','description'=>'Merch products, orders, checkout readiness, and revenue workflow reviews.'],
    ['area'=>'ops','title'=>'Operations','risk'=>'medium','description'=>'QA, launch readiness, backups, incidents, monitoring, and site health.'],
  ];
}
function sf_aipc_counts(): array {
  $counts = ['all'=>0,'proposed'=>0,'approved'=>0,'blocked'=>0,'ready_for_execution'=>0,'executed'=>0];
  if (!sf_aipc_ready()) return $counts;
  foreach (sf_admin_fetch_all('SELECT approval_status, execution_status, COUNT(*) total FROM ai_platform_actions GROUP BY approval_status, execution_status') as $row) {
    $total = (int)($row['total'] ?? 0); $counts['all'] += $total;
    $a = (string)($row['approval_status'] ?? ''); $e = (string)($row['execution_status'] ?? '');
    if (isset($counts[$a])) $counts[$a] += $total;
    if ($e === 'executed') $counts['executed'] += $total;
  }
  return $counts;
}
function sf_aipc_actions(): array { return sf_aipc_ready() ? sf_admin_fetch_all('SELECT * FROM ai_platform_actions ORDER BY updated_at DESC, id DESC LIMIT 120') : []; }

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'create_platform_proposal') {
  $routes = sf_aipc_route_options();
  $area = trim((string)($_POST['action_area'] ?? 'ops'));
  $type = trim((string)($_POST['action_type'] ?? 'review'));
  if (!isset($routes[$type])) {
    sf_admin_flash('error', 'Action type is not allowlisted for the AI Execution Router.');
    sf_admin_redirect(sf_url('admin/ai-platform-control.php'));
  }
  if (function_exists('sf_ai_policy_can_propose') && !sf_ai_policy_can_propose($type, $routes[$type])) {
    sf_admin_flash('error', 'Autonomy policy does not allow proposals for this route.');
    sf_admin_redirect(sf_url('admin/ai-platform-control.php'));
  }
  $title = sf_aipc_text($_POST['title'] ?? '', $routes[$type]['label'] ?? 'AI Platform Recommendation');
  $description = sf_aipc_text($_POST['description'] ?? '', $routes[$type]['description'] ?? 'Review this proposed platform management action before execution.');
  $risk = trim((string)($_POST['risk_level'] ?? 'low'));
  if (!in_array($risk, ['low','medium','high','critical'], true)) $risk = 'low';
  $payload = ['source'=>'ai_platform_control','requested_at'=>date('c'),'suggested_next_step'=>'Review, approve, then run through the AI Execution Router when ready.'];
  $payloadRaw = trim((string)($_POST['payload_json'] ?? ''));
  if ($payloadRaw !== '') {
    $decoded = json_decode($payloadRaw, true);
    if (!is_array($decoded)) {
      sf_admin_flash('error', 'Payload JSON is invalid. Use an object like {"media_prompt_id":123}.');
      sf_admin_redirect(sf_url('admin/ai-platform-control.php'));
    }
    $payload = $decoded + $payload;
  }
  if (!sf_aipc_ready()) {
    sf_admin_flash('error', 'AI platform action registry is not ready. Import database/ai_platform_actions.sql first.');
  } else {
    $ok = sf_admin_execute('INSERT INTO ai_platform_actions (action_area, action_type, title, description, payload_json, risk_level, approval_status, execution_status, created_by_ai) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)', [$area, $type, $title, $description, json_encode($payload, JSON_UNESCAPED_SLASHES), $risk, 'proposed', 'not_ready']);
    if ($ok) sf_admin_audit('ai_platform_action_proposed', 'ai_platform_action', null, null, ['area'=>$area,'type'=>$type,'risk'=>$risk,'payload'=>$payload]);
    sf_admin_flash($ok ? 'success' : 'error', $ok ? 'AI platform action proposal created.' : 'AI platform action proposal could not be created.');
  }
  sf_admin_redirect(sf_url('admin/ai-platform-control.php'));
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'update_platform_action') {
  $id = sf_admin_int($_POST['platform_action_id'] ?? null, 0) ?? 0;
  $approval = trim((string)($_POST['approval_status'] ?? ''));
  if (!sf_aipc_ready() || $id <= 0 || !array_key_exists($approval, sf_aipc_statuses())) {
    sf_admin_flash('error', 'Invalid AI platform action status update.');
  } else {
    $before = sf_admin_fetch_one('SELECT * FROM ai_platform_actions WHERE id = ? LIMIT 1', [$id]);
    $currentExecution = (string)($before['execution_status'] ?? 'not_ready');
    $terminal = in_array($currentExecution, ['executed','failed','cancelled'], true);
    $execution = $terminal ? $currentExecution : ($approval === 'ready_for_execution' ? 'ready' : 'not_ready');
    if (in_array($approval, ['rejected','blocked'], true)) $execution = 'cancelled';
    $userId = function_exists('sf_current_user_id') ? sf_current_user_id() : null;
    $ok = sf_admin_execute('UPDATE ai_platform_actions SET approval_status = ?, execution_status = ?, approved_by_user_id = CASE WHEN ? IN (\'approved\',\'ready_for_execution\') THEN ? ELSE approved_by_user_id END, approved_at = CASE WHEN ? IN (\'approved\',\'ready_for_execution\') THEN NOW() ELSE approved_at END WHERE id = ?', [$approval, $execution, $approval, $userId, $approval, $id]);
    $after = sf_admin_fetch_one('SELECT * FROM ai_platform_actions WHERE id = ? LIMIT 1', [$id]);
    if ($ok) sf_admin_audit('ai_platform_action_status_update', 'ai_platform_action', $id, $before, $after);
    sf_admin_flash($ok ? 'success' : 'error', $ok ? 'AI platform action updated.' : 'AI platform action could not be updated.');
  }
  sf_admin_redirect(sf_url('admin/ai-platform-control.php'));
}

$domains = sf_aipc_domains();
$routeOptions = sf_aipc_route_options();
$counts = sf_aipc_counts();
$actions = sf_aipc_actions();
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('AI Operations', 'AI Platform Control Center', 'Central registry for supervised AI platform management actions.', 'ai-platform-control');
?>
<style>
.ai-platform-control-page .sf-ai-hero,.ai-platform-control-page .sf-ai-stats,.ai-platform-control-page .sf-ai-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}.ai-platform-control-page .sf-ai-panel{padding:20px;border:1px solid rgba(232,198,127,.16);border-radius:22px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.025));box-shadow:0 20px 56px rgba(0,0,0,.22)}.ai-platform-control-page .sf-ai-panel h2{margin:8px 0;color:#fff;font-size:clamp(30px,4vw,56px);letter-spacing:-.05em;line-height:.98}.ai-platform-control-page .sf-ai-copy,.ai-platform-control-page .sf-ai-panel p{color:rgba(255,255,255,.68);line-height:1.55}.ai-platform-control-page .sf-ai-card{padding:16px;border:1px solid rgba(255,255,255,.08);border-radius:18px;background:rgba(0,0,0,.15)}.ai-platform-control-page .sf-ai-card h3{color:#fff;margin:10px 0 8px}.ai-platform-control-page .sf-ai-pill{display:inline-block;margin-top:8px;border:1px solid rgba(232,198,127,.18);border-radius:999px;padding:4px 8px;color:#f5d98d;font-size:11px;font-weight:900}.ai-platform-control-page textarea{width:100%;min-height:84px;resize:vertical}@media(max-width:1080px){.ai-platform-control-page .sf-ai-hero,.ai-platform-control-page .sf-ai-stats,.ai-platform-control-page .sf-ai-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:720px){.ai-platform-control-page .sf-ai-hero,.ai-platform-control-page .sf-ai-stats,.ai-platform-control-page .sf-ai-grid{grid-template-columns:1fr}}
</style>
<section class="sf-ai-hero"><div class="sf-ai-panel" style="grid-column:span 3"><span class="sf-panel-eyebrow">Phase 17</span><h2>Supervised AI control</h2><p>One registry for AI-proposed management actions across story, media, publishing, membership, commerce, and operations.</p></div><div class="sf-ai-panel"><span class="sf-panel-eyebrow">Policy-backed</span><h2>Permission aware</h2><p>Action cards can only be proposed when the autonomy policy allows that route.</p></div></section>
<?php if (!sf_aipc_ready()): ?><section class="sf-story-v1-warning"><strong>SQL required:</strong> Import <code>database/ai_platform_actions.sql</code> before creating AI platform actions.</section><?php endif; ?>
<section class="sf-ai-stats"><div class="sf-ai-panel"><span class="sf-panel-eyebrow">Total</span><h2><?= (int)$counts['all'] ?></h2></div><div class="sf-ai-panel"><span class="sf-panel-eyebrow">Proposed</span><h2><?= (int)$counts['proposed'] ?></h2></div><div class="sf-ai-panel"><span class="sf-panel-eyebrow">Approved</span><h2><?= (int)$counts['approved'] ?></h2></div><div class="sf-ai-panel"><span class="sf-panel-eyebrow">Ready</span><h2><?= (int)$counts['ready_for_execution'] ?></h2></div></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Managed Areas</span><h2>AI control domains</h2></div><div><a href="<?= sf_url('admin/ai-autonomy-policies.php') ?>">Autonomy Policies</a> · <a href="<?= sf_url('admin/ai-execution-router.php') ?>">Execution Router</a></div></div><div class="sf-ai-grid"><?php foreach ($domains as $domain): ?><article class="sf-ai-card"><span class="sf-ai-pill">Risk: <?= sf_admin_h($domain['risk']) ?></span><h3><?= sf_admin_h($domain['title']) ?></h3><p class="sf-ai-copy"><?= sf_admin_h($domain['description']) ?></p><form method="post" class="sf-admin-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="create_platform_proposal"><input type="hidden" name="action_area" value="<?= sf_admin_h($domain['area']) ?>"><input type="hidden" name="action_type" value="review"><input type="hidden" name="risk_level" value="<?= sf_admin_h($domain['risk']) ?>"><input type="hidden" name="title" value="Review <?= sf_admin_h($domain['title']) ?> readiness"><input type="hidden" name="description" value="AI should review <?= sf_admin_h(strtolower($domain['title'])) ?> readiness and propose safe next actions for admin approval."><div class="sf-admin-form-actions"><button type="submit"<?= (sf_aipc_ready() && (!function_exists('sf_ai_policy_can_propose') || sf_ai_policy_can_propose('review', $routeOptions['review'] ?? []))) ? '' : ' disabled' ?>>Create Proposal</button></div></form></article><?php endforeach; ?></div></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Custom Proposal</span><h2>Create AI action card</h2></div></div><form method="post" class="sf-admin-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="create_platform_proposal"><div class="sf-admin-form-grid"><label>Area<select name="action_area"><?php foreach ($domains as $domain): ?><option value="<?= sf_admin_h($domain['area']) ?>"><?= sf_admin_h($domain['title']) ?></option><?php endforeach; ?></select></label><label>Action Type<select name="action_type"><?php foreach ($routeOptions as $key => $route): ?><option value="<?= sf_admin_h($key) ?>"><?= sf_admin_h($key . ' — ' . ($route['label'] ?? $key)) ?></option><?php endforeach; ?></select></label><label>Risk<select name="risk_level"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="critical">Critical</option></select></label><label>Title<input name="title" placeholder="Review launch readiness"></label></div><label>Description<textarea name="description" placeholder="Describe the proposed AI management action."></textarea></label><label>Payload JSON<textarea name="payload_json" placeholder='Optional. Examples: {"storyboard_id":12,"status":"ready"} or {"story_episode_id":2,"title":"New Scene"}'></textarea></label><div class="sf-admin-form-actions"><button type="submit"<?= sf_aipc_ready() ? '' : ' disabled' ?>>Create AI Proposal</button></div></form></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Action Registry</span><h2><?= count($actions) ?> action(s)</h2></div></div><?php if (!$actions): ?><p class="sf-ai-copy">No AI platform actions yet.</p><?php else: ?><div class="sf-ai-grid"><?php foreach ($actions as $item): ?><article class="sf-ai-card"><?= sf_admin_status_badge((string)($item['approval_status'] ?? 'proposed')) ?><h3><?= sf_admin_h($item['title'] ?? 'AI Action') ?></h3><p class="sf-ai-copy"><strong><?= sf_admin_h($item['action_area'] ?? 'area') ?></strong> · <?= sf_admin_h($item['action_type'] ?? 'action') ?> · Risk <?= sf_admin_h($item['risk_level'] ?? 'low') ?><br>Execution: <?= sf_admin_h($item['execution_status'] ?? 'not_ready') ?></p><p class="sf-ai-copy"><?= sf_admin_h($item['description'] ?? '') ?></p><?php if (!empty($item['payload_json'])): ?><p class="sf-ai-copy"><strong>Payload:</strong> <?= sf_admin_h(sf_aipc_snip($item['payload_json'], 190)) ?></p><?php endif; ?><form method="post" class="sf-admin-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="update_platform_action"><input type="hidden" name="platform_action_id" value="<?= (int)($item['id'] ?? 0) ?>"><label>Approval Status<select name="approval_status"><?php foreach (sf_aipc_statuses() as $key => $label): ?><option value="<?= sf_admin_h($key) ?>"<?= (string)($item['approval_status'] ?? '') === $key ? ' selected' : '' ?>><?= sf_admin_h($label) ?></option><?php endforeach; ?></select></label><div class="sf-admin-form-actions"><button type="submit">Update Action</button></div></form></article><?php endforeach; ?></div><?php endif; ?></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Safety</span><h2>Controlled execution only</h2></div></div><p class="sf-ai-copy">This registry prepares and approves AI platform actions. Execution is controlled by coded, allowlisted routes and the AI Autonomy Policies layer.</p></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
