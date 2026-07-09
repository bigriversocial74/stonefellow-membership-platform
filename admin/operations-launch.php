<?php
$pageTitle = 'Operations Launch';
$pageDescription = 'Stonefellow operations and launch command hub for readiness, QA, migrations, backups, releases, monitoring, incidents, and AI Producer coordination.';
$pageClass = 'membership-page admin-catalog-page operations-launch-page';
require __DIR__ . '/../includes/qa.php';

function sf_ops_hub_card(string $eyebrow, string $title, string $detail, string $href, string $status = 'Open'): array {
  return ['eyebrow'=>$eyebrow,'title'=>$title,'detail'=>$detail,'href'=>$href,'status'=>$status];
}

$sections = sf_qa_all_checks();
$flatChecks = sf_qa_flatten($sections);
$score = sf_qa_score($flatChecks);
$fails = count(array_filter($flatChecks, static fn($check) => in_array((string)($check['status'] ?? ''), ['fail','missing'], true)));
$reviews = count(array_filter($flatChecks, static fn($check) => in_array((string)($check['status'] ?? ''), ['warn','manual','preview'], true)));
$installerLocked = is_file(sf_qa_root() . '/storage/install.lock');
$openIncidents = sf_admin_table_exists('incident_records') ? sf_admin_count_table('incident_records') : 0;
$backupRuns = sf_admin_table_exists('backup_runs') ? sf_admin_count_table('backup_runs') : 0;
$releaseRuns = sf_admin_table_exists('deployment_releases') ? sf_admin_count_table('deployment_releases') : 0;

$cards = [
  sf_ops_hub_card('Launch', 'Launch Readiness', 'Permanent post-install checklist and launch score.', 'admin/launch-readiness.php', $score >= 90 ? 'Ready' : 'Review'),
  sf_ops_hub_card('AI', 'AI Producer', 'Supervised AI operations foundation for recommendations and team updates.', 'admin/ai-producer.php', 'Supervised'),
  sf_ops_hub_card('QA', 'Production QA', 'Environment, migrations, routes, security, and content readiness.', 'admin/qa.php', $fails === 0 ? 'Ready' : 'Fix'),
  sf_ops_hub_card('Schema', 'Migration Checker', 'Base SQL and versioned migration verification and repair.', 'admin/migration-checker.php', 'Run'),
  sf_ops_hub_card('Routes', 'Routes Checker', 'Public, admin, API, and utility route matrix.', 'admin/routes-checker.php', 'Check'),
  sf_ops_hub_card('Security', 'Security Check', 'Admin gates, CSRF, uploads, media signatures, and webhook security.', 'admin/security-check.php', 'Check'),
  sf_ops_hub_card('Content', 'Content Audit', 'Missing media references and content launch hygiene.', 'admin/content-audit.php', 'Audit'),
  sf_ops_hub_card('Backup', 'Backups', 'Backup profiles, backup runs, and restore readiness.', 'admin/backups.php', $backupRuns > 0 ? 'Ready' : 'Manual'),
  sf_ops_hub_card('Release', 'Releases', 'Deployment release records, checklist, rollback notes, and events.', 'admin/releases.php', $releaseRuns > 0 ? 'Ready' : 'Draft'),
  sf_ops_hub_card('Monitor', 'Monitoring', 'Health snapshots, service checks, errors, failed jobs, and alert signals.', 'admin/monitoring.php', 'Watch'),
  sf_ops_hub_card('Incident', 'Incidents', 'Incident records, severity, status, and response history.', 'admin/incidents.php', $openIncidents > 0 ? 'Review' : 'Clear'),
  sf_ops_hub_card('Team', 'Member Messaging', 'Future team/member notification workflow for AI Producer updates.', 'admin/member-messaging.php', 'Manual'),
];

require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Operations / Launch', 'Operations launch command', 'Grouped launch, QA, monitoring, release, backup, incident, and AI Producer controls.', 'operations-launch');
?>
<style>
.operations-launch-page .sf-ops-hero{display:grid;grid-template-columns:minmax(0,1.25fr) repeat(3,minmax(160px,.25fr));gap:14px;margin-bottom:18px}.operations-launch-page .sf-ops-panel{padding:20px;border:1px solid rgba(232,198,127,.16);border-radius:22px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.025));box-shadow:0 20px 56px rgba(0,0,0,.22)}.operations-launch-page .sf-ops-hero h2{margin:8px 0;color:#fff;font-size:clamp(32px,5vw,60px);letter-spacing:-.055em;line-height:.95}.operations-launch-page .sf-ops-panel p{color:rgba(255,255,255,.68);line-height:1.55}.operations-launch-page .sf-ops-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.operations-launch-page .sf-ops-card{min-height:188px;padding:16px;border:1px solid rgba(232,198,127,.14);border-radius:18px;background:rgba(255,255,255,.04);text-decoration:none}.operations-launch-page .sf-ops-card span{display:block;color:rgba(232,198,127,.84);font-size:11px;font-weight:950;letter-spacing:.1em;text-transform:uppercase}.operations-launch-page .sf-ops-card h3{color:#fff;margin:12px 0 8px;font-size:18px}.operations-launch-page .sf-ops-card p{color:rgba(255,255,255,.67);font-size:13px;line-height:1.5}.operations-launch-page .sf-ops-card strong{display:inline-flex;margin-top:10px;color:#f5d98d;font-size:12px;text-transform:uppercase;letter-spacing:.08em}@media(max-width:1180px){.operations-launch-page .sf-ops-hero,.operations-launch-page .sf-ops-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:720px){.operations-launch-page .sf-ops-hero,.operations-launch-page .sf-ops-grid{grid-template-columns:1fr}}
</style>
<section class="sf-ops-hero">
  <div class="sf-ops-panel"><span class="sf-panel-eyebrow">Command Hub</span><h2><?= (int)$score ?>%</h2><p>Central operations view for launch, readiness, QA, releases, monitoring, incidents, and the AI Producer foundation.</p></div>
  <div class="sf-ops-panel"><span class="sf-panel-eyebrow">Installer</span><h2><?= $installerLocked ? 'Locked' : 'Open' ?></h2><p><?= $installerLocked ? 'Install lock is present.' : 'Install lock needs review.' ?></p></div>
  <div class="sf-ops-panel"><span class="sf-panel-eyebrow">Fails</span><h2><?= (int)$fails ?></h2><p>QA checks requiring fixes.</p></div>
  <div class="sf-ops-panel"><span class="sf-panel-eyebrow">Review</span><h2><?= (int)$reviews ?></h2><p>Warnings, manual checks, and preview-mode checks.</p></div>
</section>
<section class="sf-ops-grid">
  <?php foreach ($cards as $card): ?>
    <a class="sf-ops-card" href="<?= sf_url($card['href']) ?>"><span><?= sf_admin_h($card['eyebrow']) ?></span><h3><?= sf_admin_h($card['title']) ?></h3><p><?= sf_admin_h($card['detail']) ?></p><strong><?= sf_admin_h($card['status']) ?></strong></a>
  <?php endforeach; ?>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
