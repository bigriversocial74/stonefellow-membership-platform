<?php
$pageTitle = 'AI Staging Certification';
$pageDescription = 'Connect providers, run controlled staging checks, verify rollback and concurrency, and record production-readiness evidence.';
$pageClass = 'membership-page admin-catalog-page ai-certification-page';
require_once __DIR__ . '/../includes/ai_staging_certification.php';
sf_agentic_require_permission('admin.ops.manage');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $runId = (int)($_POST['run_id'] ?? 0);
    if ($action === 'create_run') {
        $runId = sf_ai_cert_create_run((string)($_POST['run_label'] ?? ''));
        sf_admin_flash($runId > 0 ? 'success':'error',$runId > 0 ? 'Certification run created.':'Certification run could not be created. Import the certification SQL first.');
    } elseif ($runId > 0 && $action === 'run_automated') {
        sf_ai_cert_run_automated($runId);
        sf_admin_flash('success','Automated staging checks completed.');
    } elseif ($runId > 0 && $action === 'test_provider') {
        $result = sf_ai_cert_provider_test($runId,(string)($_POST['provider_key'] ?? ''));
        sf_admin_flash(!empty($result['ok']) ? 'success':'error',(string)($result['message'] ?? 'Provider test completed.'));
    } elseif ($runId > 0 && $action === 'restore_roundtrip') {
        $result = sf_ai_cert_restore_roundtrip($runId,(string)($_POST['entity_type'] ?? ''),(int)($_POST['entity_id'] ?? 0));
        sf_admin_flash(!empty($result['ok']) ? 'success':'error',(string)($result['message'] ?? 'Restore test completed.'));
    } elseif ($runId > 0 && $action === 'save_cost') {
        $ok = sf_ai_cert_save_cost_reconciliation($runId,(string)($_POST['provider_key'] ?? ''),max(0,(int)($_POST['provider_invoice_cents'] ?? 0)),(string)($_POST['notes'] ?? ''));
        sf_admin_flash($ok ? 'success':'error',$ok ? 'Provider cost reconciliation saved.':'Cost reconciliation could not be saved.');
    } elseif ($runId > 0 && $action === 'manual_check') {
        $ok = sf_ai_cert_manual_check($runId,(string)($_POST['check_key'] ?? ''),(string)($_POST['check_status'] ?? 'pending'),(string)($_POST['notes'] ?? ''));
        sf_admin_flash($ok ? 'success':'error',$ok ? 'Manual certification evidence saved.':'Manual evidence could not be saved.');
    } elseif ($runId > 0 && $action === 'complete_run') {
        $result = sf_ai_cert_complete($runId,(string)($_POST['notes'] ?? ''));
        sf_admin_flash(!empty($result['ok']) ? 'success':'warning',(string)($result['message'] ?? 'Certification completion evaluated.'));
    }
    sf_admin_redirect(sf_url('admin/ai-staging-certification.php' . ($runId > 0 ? '?run_id=' . $runId : '')));
}

$runs = sf_ai_cert_runs();
$selectedRunId = (int)($_GET['run_id'] ?? ($runs[0]['id'] ?? 0));
$run = sf_ai_cert_run($selectedRunId);
$checks = $run ? sf_ai_cert_checks($selectedRunId) : [];
$usageRows = sf_ai_cert_usage_rows();
$costRows = $run ? sf_ai_cert_cost_reconciliations($selectedRunId) : [];
$costByProvider = [];
foreach ($costRows as $row) $costByProvider[(string)$row['provider_key']] = $row;
$providers = sf_ai_providers();
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('AI Operations','AI Staging Certification','Connect live providers safely, exercise failure and concurrency controls, prove rollback behavior, and retain a signed checklist before production rollout.','ai-staging-certification');
?>
<style>
.ai-certification-page .cert-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}.ai-certification-page .cert-card{padding:18px;border:1px solid rgba(232,198,127,.16);border-radius:20px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.025))}.ai-certification-page .cert-card strong{display:block;color:#fff;font-size:28px;margin:6px 0}.ai-certification-page .cert-card small,.ai-certification-page .cert-copy{color:rgba(255,255,255,.66);line-height:1.5}.ai-certification-page .cert-checks{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.ai-certification-page .cert-check{padding:15px;border:1px solid rgba(255,255,255,.08);border-radius:16px;background:rgba(0,0,0,.16)}.ai-certification-page .cert-check h3{margin:8px 0;color:#fff}.ai-certification-page .cert-meta{display:flex;flex-wrap:wrap;gap:7px}.ai-certification-page .cert-pill{border:1px solid rgba(232,198,127,.2);border-radius:999px;padding:4px 8px;font-size:11px;color:#f5d98d}.ai-certification-page .cert-actions{display:flex;flex-wrap:wrap;gap:10px;align-items:end}.ai-certification-page .cert-actions form{margin:0}.ai-certification-page .cert-run-list{display:flex;flex-wrap:wrap;gap:8px}.ai-certification-page .cert-run-list a{padding:8px 12px;border:1px solid rgba(255,255,255,.1);border-radius:999px;color:#fff;text-decoration:none}.ai-certification-page .cert-run-list a.is-active{border-color:#d6ad6c;color:#f5d98d}@media(max-width:1050px){.ai-certification-page .cert-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:760px){.ai-certification-page .cert-grid,.ai-certification-page .cert-checks{grid-template-columns:1fr}}
</style>
<?php if (!sf_ai_cert_ready()): ?>
<div class="sf-admin-alert sf-admin-alert-warning"><strong>SQL required:</strong> Import <code>database/ai_staging_certification_v1.sql</code> before creating a certification run.</div>
<?php endif; ?>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Certification Run</span><h2>Create or select a staging run</h2></div><a href="<?= sf_url('admin/ai-settings.php') ?>">AI Provider Settings</a></div>
  <form method="post" class="sf-admin-form">
    <?= sf_csrf_field() ?><input type="hidden" name="action" value="create_run">
    <div class="sf-admin-form-grid"><label>Run label<input name="run_label" placeholder="July staging provider certification"></label><div class="sf-admin-form-actions"><button type="submit"<?= sf_ai_cert_ready() ? '':' disabled' ?>>Create Certification Run</button></div></div>
  </form>
  <?php if ($runs): ?><div class="cert-run-list"><?php foreach ($runs as $item): ?><a class="<?= (int)$item['id'] === $selectedRunId ? 'is-active':'' ?>" href="<?= sf_url('admin/ai-staging-certification.php?run_id=' . (int)$item['id']) ?>"><?= sf_admin_h($item['run_label']) ?> · <?= sf_admin_h($item['run_status']) ?></a><?php endforeach; ?></div><?php endif; ?>
</section>
<?php if ($run): ?>
<section class="cert-grid">
  <div class="cert-card"><span class="sf-panel-eyebrow">Environment</span><strong><?= sf_admin_h($run['environment_key']) ?></strong><small>Certification requires <code>SF_ENV=staging</code>.</small></div>
  <div class="cert-card"><span class="sf-panel-eyebrow">Score</span><strong><?= number_format((float)$run['overall_score'],1) ?>%</strong><small><?= (int)$run['passed_checks'] ?> of <?= (int)$run['required_checks'] ?> required checks passed.</small></div>
  <div class="cert-card"><span class="sf-panel-eyebrow">Failures</span><strong><?= (int)$run['failed_checks'] ?></strong><small><?= (int)$run['pending_checks'] ?> pending, running, or skipped.</small></div>
  <div class="cert-card"><span class="sf-panel-eyebrow">Status</span><strong><?= sf_admin_h(ucwords(str_replace('_',' ',$run['run_status']))) ?></strong><small><?= sf_admin_h($run['run_key']) ?></small></div>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Automated Gate</span><h2>Environment, limits, transport, locks, queues, and permissions</h2></div></div>
  <div class="cert-actions">
    <form method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="run_automated"><input type="hidden" name="run_id" value="<?= $selectedRunId ?>"><button type="submit">Run Automated Certification Checks</button></form>
    <?php foreach ($providers as $provider): ?><form method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="test_provider"><input type="hidden" name="run_id" value="<?= $selectedRunId ?>"><input type="hidden" name="provider_key" value="<?= sf_admin_h($provider['provider_key']) ?>"><button type="submit">Test <?= sf_admin_h($provider['provider_label']) ?></button></form><?php endforeach; ?>
  </div>
  <p class="cert-copy">Provider tests make one tiny, non-mutating text request. They still enforce configured budgets, limits, throttles, encryption, and advisory locks.</p>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Rollback Proof</span><h2>Snapshot, temporary mutation, and restoration</h2></div></div>
  <div class="cert-checks">
    <?php foreach (['storyboard'=>'Storyboard','scene'=>'Scene','episode'=>'Episode'] as $type=>$label): ?>
    <form method="post" class="cert-check sf-admin-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="restore_roundtrip"><input type="hidden" name="run_id" value="<?= $selectedRunId ?>"><input type="hidden" name="entity_type" value="<?= $type ?>"><h3><?= $label ?> restore roundtrip</h3><label>Record ID <input type="number" min="0" name="entity_id" value="0"><small>Use 0 to test the newest record.</small></label><div class="sf-admin-form-actions"><button type="submit">Run <?= $label ?> Restore Test</button></div></form>
    <?php endforeach; ?>
  </div>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Cost Controls</span><h2>Reservations versus provider invoices</h2></div></div>
  <div class="cert-checks">
  <?php foreach ($usageRows as $usage): $saved = $costByProvider[$usage['provider_key']] ?? []; ?>
    <form method="post" class="cert-check sf-admin-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="save_cost"><input type="hidden" name="run_id" value="<?= $selectedRunId ?>"><input type="hidden" name="provider_key" value="<?= sf_admin_h($usage['provider_key']) ?>"><h3><?= sf_admin_h($usage['provider_label']) ?></h3><p class="cert-copy">Reserved <?= sf_ai_format_cents($usage['reserved_cost_cents'] ?? 0) ?> · <?= number_format((int)($usage['prompt_tokens'] ?? 0) + (int)($usage['completion_tokens'] ?? 0)) ?> tokens · <?= (int)($usage['image_count'] ?? 0) ?> images · <?= (int)($usage['request_count'] ?? 0) ?> requests</p><label>Provider invoice cents<input type="number" min="0" name="provider_invoice_cents" value="<?= (int)($saved['provider_invoice_cents'] ?? 0) ?>"></label><label>Notes<textarea name="notes" rows="2"><?= sf_admin_h($saved['notes'] ?? '') ?></textarea></label><div class="sf-admin-form-actions"><button type="submit">Save Reconciliation</button></div></form>
  <?php endforeach; ?>
  </div>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Certification Checklist</span><h2><?= count($checks) ?> controls</h2></div></div>
  <div class="cert-checks">
    <?php foreach ($checks as $check): ?>
    <article class="cert-check">
      <div class="cert-meta"><span class="cert-pill"><?= sf_admin_h($check['category_key']) ?></span><span class="cert-pill"><?= sf_admin_h($check['severity']) ?></span><span class="cert-pill"><?= !empty($check['is_automated']) ? 'automated':'manual' ?></span></div>
      <h3><?= sf_admin_h($check['check_label']) ?></h3>
      <?= sf_admin_status_badge((string)$check['check_status']) ?>
      <p class="cert-copy"><?= sf_admin_h($check['result_message'] ?: 'Not tested yet.') ?></p>
      <?php if (empty($check['is_automated'])): ?><form method="post" class="sf-admin-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="manual_check"><input type="hidden" name="run_id" value="<?= $selectedRunId ?>"><input type="hidden" name="check_key" value="<?= sf_admin_h($check['check_key']) ?>"><label>Status<?= sf_admin_select('check_status',['passed'=>'Passed','failed'=>'Failed','pending'=>'Pending','skipped'=>'Skipped'],$check['check_status']) ?></label><label>Evidence / notes<textarea name="notes" rows="3"><?= sf_admin_h($check['result_message'] ?? '') ?></textarea></label><div class="sf-admin-form-actions"><button type="submit">Save Manual Evidence</button></div></form><?php endif; ?>
    </article>
    <?php endforeach; ?>
  </div>
</section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Final Gate</span><h2>Complete staging certification</h2></div></div>
  <form method="post" class="sf-admin-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="complete_run"><input type="hidden" name="run_id" value="<?= $selectedRunId ?>"><label>Certification notes<textarea name="notes" rows="4" placeholder="Summarize staging environment, provider accounts, test evidence, and remaining operational restrictions."><?= sf_admin_h($run['certification_notes'] ?? '') ?></textarea></label><div class="sf-admin-form-actions"><button type="submit">Evaluate & Complete Certification</button></div></form>
</section>
<?php else: ?><section class="sf-admin-panel"><p class="cert-copy">Create a certification run to begin.</p></section><?php endif; ?>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>