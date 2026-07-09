<?php
$pageTitle = 'AI Producer';
$pageDescription = 'Stonefellow AI Producer command center foundation for supervised platform operations, team notifications, and launch task coordination.';
$pageClass = 'membership-page admin-catalog-page ai-producer-page';
require __DIR__ . '/../includes/qa.php';

function sf_ai_prod_status_badge(string $status): string {
  $map = ['ready'=>'active','pass'=>'active','review'=>'draft','manual'=>'draft','locked'=>'draft','future'=>'draft','fix'=>'canceled','fail'=>'canceled'];
  return sf_admin_status_badge($map[$status] ?? $status);
}
function sf_ai_prod_signal(string $status, string $title, string $detail, ?string $href = null, ?string $cta = null): array {
  return ['status'=>$status,'title'=>$title,'detail'=>$detail,'href'=>$href,'cta'=>$cta];
}

$sections = sf_qa_all_checks();
$flatChecks = sf_qa_flatten($sections);
$overallScore = sf_qa_score($flatChecks);
$failedChecks = count(array_filter($flatChecks, static fn($check) => in_array((string)($check['status'] ?? ''), ['fail','missing'], true)));
$reviewChecks = count(array_filter($flatChecks, static fn($check) => in_array((string)($check['status'] ?? ''), ['warn','manual','preview'], true)));
$openIncidents = sf_admin_table_exists('incident_records') ? sf_admin_fetch_all("SELECT incident_key, title, severity, status, created_at FROM incident_records WHERE status NOT IN ('resolved','closed') ORDER BY created_at DESC, id DESC LIMIT 5") : [];
$recentBackups = sf_admin_table_exists('backup_runs') ? sf_admin_fetch_all('SELECT run_key, run_status, database_status, files_status, created_at FROM backup_runs ORDER BY created_at DESC, id DESC LIMIT 5') : [];
$recentReleases = sf_admin_table_exists('deployment_releases') ? sf_admin_fetch_all('SELECT release_key, release_label, release_status, created_at FROM deployment_releases ORDER BY created_at DESC, id DESC LIMIT 5') : [];

$signals = [
  sf_ai_prod_signal($overallScore >= 90 ? 'ready' : 'review', 'Launch Readiness', $overallScore . '% readiness score with ' . $failedChecks . ' failed check(s) and ' . $reviewChecks . ' review item(s).', 'admin/launch-readiness.php', 'Open Readiness'),
  sf_ai_prod_signal($failedChecks === 0 ? 'ready' : 'fix', 'QA Failures', $failedChecks === 0 ? 'No failed QA checks currently detected.' : $failedChecks . ' failed QA check(s) should be triaged before launch.', 'admin/qa.php', 'Run QA'),
  sf_ai_prod_signal(sf_admin_table_exists('backup_runs') ? 'manual' : 'review', 'Backups', sf_admin_table_exists('backup_runs') ? count($recentBackups) . ' recent backup record(s) visible.' : 'Backup tables are not detected yet.', 'admin/backups.php', 'Backups'),
  sf_ai_prod_signal(count($openIncidents) === 0 ? 'ready' : 'review', 'Incidents', count($openIncidents) === 0 ? 'No open incidents detected.' : count($openIncidents) . ' open incident(s) need attention.', 'admin/incidents.php', 'Incidents'),
  sf_ai_prod_signal(sf_admin_table_exists('member_message_campaigns') ? 'manual' : 'future', 'Team Notifications', sf_admin_table_exists('member_message_campaigns') ? 'Messaging tables are available for future AI-produced updates.' : 'Notification execution is future work; this page does not send messages.', 'admin/member-messaging.php', 'Messaging'),
  sf_ai_prod_signal('locked', 'Autonomous Control', 'AI control is intentionally locked to observe, recommend, and draft until explicit approval workflows are added.', null, null),
];

$controlLadder = [
  ['Now', 'Observe', 'Read QA, readiness, backup, incident, release, monitoring, and content status without changing the platform.'],
  ['Next', 'Recommend', 'Generate prioritized tasks and team updates from platform signals.'],
  ['Next', 'Draft', 'Prepare release notes, checklists, content tasks, and notification copy for approval.'],
  ['Later', 'Approve', 'Require a human admin approval gate before any state-changing action runs.'],
  ['Later', 'Execute', 'Run approved tasks through audited, permissioned tools only after safety gates exist.'],
];

require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('AI Producer', 'AI Producer command center', 'Foundation for supervised AI operations, team visibility, and future platform control.', 'ai-producer');
?>
<style>
.ai-producer-page .sf-ai-hero{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(280px,.8fr);gap:16px;margin-bottom:18px}.ai-producer-page .sf-ai-panel{padding:20px;border:1px solid rgba(232,198,127,.16);border-radius:22px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.025));box-shadow:0 20px 56px rgba(0,0,0,.22)}.ai-producer-page .sf-ai-hero h2{margin:8px 0;color:#fff;font-size:clamp(36px,5vw,64px);letter-spacing:-.055em;line-height:.95}.ai-producer-page .sf-ai-panel p{color:rgba(255,255,255,.68);line-height:1.55}.ai-producer-page .sf-ai-signal-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:18px}.ai-producer-page .sf-ai-signal{min-height:180px;padding:16px;border:1px solid rgba(232,198,127,.14);border-radius:18px;background:rgba(255,255,255,.04)}.ai-producer-page .sf-ai-signal h3{color:#fff;margin:12px 0 8px;font-size:18px}.ai-producer-page .sf-ai-signal span,.ai-producer-page .sf-ai-ladder span{display:block;color:rgba(232,198,127,.84);font-size:11px;font-weight:950;letter-spacing:.1em;text-transform:uppercase}.ai-producer-page .sf-ai-signal a{display:inline-flex;margin-top:13px;color:#f5d98d;font-weight:950;text-decoration:none}.ai-producer-page .sf-ai-ladder-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}.ai-producer-page .sf-ai-ladder{padding:16px;border:1px solid rgba(232,198,127,.14);border-radius:18px;background:rgba(255,255,255,.035)}.ai-producer-page .sf-ai-ladder h3{color:#fff;margin:10px 0 8px}.ai-producer-page .sf-ai-two-col{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.ai-producer-page .sf-ai-actions{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}.ai-producer-page .sf-ai-actions a{padding:14px;border:1px solid rgba(232,198,127,.16);border-radius:16px;background:rgba(255,255,255,.035);color:#f5d98d;font-weight:950;text-align:center;text-decoration:none}@media(max-width:1180px){.ai-producer-page .sf-ai-signal-grid,.ai-producer-page .sf-ai-ladder-grid,.ai-producer-page .sf-ai-two-col,.ai-producer-page .sf-ai-actions{grid-template-columns:repeat(2,minmax(0,1fr))}.ai-producer-page .sf-ai-hero{grid-template-columns:1fr}}@media(max-width:720px){.ai-producer-page .sf-ai-signal-grid,.ai-producer-page .sf-ai-ladder-grid,.ai-producer-page .sf-ai-two-col,.ai-producer-page .sf-ai-actions{grid-template-columns:1fr}}
</style>

<section class="sf-ai-hero">
  <div class="sf-ai-panel"><span class="sf-panel-eyebrow">Producer Mode</span><h2>Supervised</h2><p>The AI Producer is being staged as an operations layer that can observe the platform, recommend priorities, draft updates, and eventually run approved tasks with audit trails.</p></div>
  <div class="sf-ai-panel"><span class="sf-panel-eyebrow">Current Guardrail</span><h2>No Auto-Run</h2><p>This foundation does not change data, send notifications, publish content, issue refunds, or run jobs. Control stays human-approved until the permission model is built.</p></div>
</section>

<section class="sf-ai-signal-grid">
  <?php foreach ($signals as $signal): ?>
    <article class="sf-ai-signal">
      <?= sf_ai_prod_status_badge($signal['status']) ?>
      <span><?= sf_admin_h($signal['status']) ?></span>
      <h3><?= sf_admin_h($signal['title']) ?></h3>
      <p><?= sf_admin_h($signal['detail']) ?></p>
      <?php if (!empty($signal['href'])): ?><a href="<?= sf_url($signal['href']) ?>"><?= sf_admin_h($signal['cta'] ?? 'Open') ?></a><?php endif; ?>
    </article>
  <?php endforeach; ?>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Control Ladder</span><h2>Path to safe AI control</h2></div></div>
  <div class="sf-ai-ladder-grid">
    <?php foreach ($controlLadder as $step): ?>
      <article class="sf-ai-ladder"><span><?= sf_admin_h($step[0]) ?></span><h3><?= sf_admin_h($step[1]) ?></h3><p><?= sf_admin_h($step[2]) ?></p></article>
    <?php endforeach; ?>
  </div>
</section>

<section class="sf-ai-two-col">
  <div class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Open Incidents</span><h2>Needs attention</h2></div></div><?php if ($openIncidents): ?><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Incident</th><th>Status</th><th>Created</th></tr></thead><tbody><?php foreach ($openIncidents as $row): ?><tr><td><strong><?= sf_admin_h($row['title'] ?? $row['incident_key'] ?? '') ?></strong><small><?= sf_admin_h($row['severity'] ?? '') ?></small></td><td><?= sf_admin_status_badge((string)($row['status'] ?? 'draft')) ?></td><td><?= sf_admin_h($row['created_at'] ?? '') ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p>No open incidents detected.</p><?php endif; ?></div>
  <div class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Backups</span><h2>Recent runs</h2></div></div><?php if ($recentBackups): ?><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Run</th><th>Status</th><th>Created</th></tr></thead><tbody><?php foreach ($recentBackups as $row): ?><tr><td><strong><?= sf_admin_h($row['run_key'] ?? '') ?></strong><small>DB: <?= sf_admin_h($row['database_status'] ?? '') ?> · Files: <?= sf_admin_h($row['files_status'] ?? '') ?></small></td><td><?= sf_admin_status_badge((string)($row['run_status'] ?? 'draft')) ?></td><td><?= sf_admin_h($row['created_at'] ?? '') ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p>No backup runs found yet.</p><?php endif; ?></div>
  <div class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Releases</span><h2>Recent deploys</h2></div></div><?php if ($recentReleases): ?><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Release</th><th>Status</th><th>Created</th></tr></thead><tbody><?php foreach ($recentReleases as $row): ?><tr><td><strong><?= sf_admin_h($row['release_label'] ?? $row['release_key'] ?? '') ?></strong><small><?= sf_admin_h($row['release_key'] ?? '') ?></small></td><td><?= sf_admin_status_badge((string)($row['release_status'] ?? 'draft')) ?></td><td><?= sf_admin_h($row['created_at'] ?? '') ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p>No release records found yet.</p><?php endif; ?></div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Producer Tools</span><h2>Operations shortcuts</h2></div></div>
  <div class="sf-ai-actions">
    <a href="<?= sf_url('admin/launch-readiness.php') ?>">Launch Readiness</a>
    <a href="<?= sf_url('admin/qa.php') ?>">Production QA</a>
    <a href="<?= sf_url('admin/monitoring.php') ?>">Monitoring</a>
    <a href="<?= sf_url('admin/member-messaging.php') ?>">Messaging</a>
    <a href="<?= sf_url('admin/releases.php') ?>">Releases</a>
  </div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
