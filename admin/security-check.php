<?php
$pageTitle = 'Security Check';
$pageDescription = 'Review Stonefellow security hardening checks for admin, auth, API, uploads, and webhooks.';
$pageClass = 'membership-page admin-catalog-page qa-page';
require __DIR__ . '/../includes/qa.php';
$checks = sf_qa_security_checks();
$score = sf_qa_score($checks);
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Security Check', 'Hardening readiness', 'Review admin protection, CSRF, password storage, upload validation, payment webhook boundaries, and database safety.', 'security-check');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>Security Score</span><strong><?= (int)$score ?>%</strong><small><?= sf_qa_h(sf_qa_grade($score)) ?> scoped security readiness.</small></div>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/system-health.php') ?>"><span>Health</span><strong>System</strong><small>Runtime environment checks.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/payment-gateways.php') ?>"><span>Payments</span><strong>Gateway Config</strong><small>Configure live processor adapters.</small></a>
</section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Checks</span><h2>Security matrix</h2></div></div><?php sf_qa_render_check_table($checks); ?></section>
<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Launch Rules</span><h2>Before going live</h2></div></div>
  <div class="sf-admin-roadmap">
    <div><span>1</span><strong>Set secrets</strong><p>Configure DB credentials, mail credentials, payment webhook secrets, and hash salt outside the web root.</p></div>
    <div><span>2</span><strong>Use HTTPS</strong><p>Force SSL on production before activating payment or login traffic.</p></div>
    <div><span>3</span><strong>Lock admin</strong><p>Confirm the first admin account is yours, rotate temporary passwords, and disable unused admin accounts.</p></div>
    <div><span>4</span><strong>Test webhooks</strong><p>Verify billing/payment/notification webhooks in sandbox before live payment switch-over.</p></div>
  </div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
