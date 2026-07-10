<?php

declare(strict_types=1);

$pageTitle = 'Product License';
$pageDescription = 'Review the Stonefellow product identity, local activation receipt, authorized domains, update eligibility, and offline ledger status.';
$pageClass = 'membership-page admin-catalog-page product-license-page';

require_once __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/license.php';
if (function_exists('sf_agentic_require_permission')) sf_agentic_require_permission('admin.ops.manage');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'revalidate') {
        $result = sf_license_revalidate_receipt();
        sf_admin_flash(!empty($result['ok']) ? 'success' : 'warning', (string)($result['message'] ?? 'License validation completed.'));
    }
    sf_admin_redirect(sf_url('admin/license.php'));
}

$status = sf_license_status();
$product = $status['product'];
$receipt = $status['receipt'];
$record = $status['record'];
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Configuration', 'Product License', 'Review the installed product identity and activation status without exposing the complete license key.', 'license');
?>
<style>
.lic-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.lic-card{padding:18px;border:1px solid rgba(255,255,255,.1);border-radius:18px;background:rgba(255,255,255,.035)}.lic-card span{display:block;color:rgba(255,255,255,.62);font-size:.74rem;text-transform:uppercase;letter-spacing:.08em}.lic-card strong{display:block;color:#fff;font-size:1.15rem;margin:8px 0;word-break:break-word}.lic-two{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.lic-list{display:grid;gap:10px}.lic-row{display:grid;grid-template-columns:190px 1fr;gap:18px;padding:12px 0;border-bottom:1px solid rgba(255,255,255,.08)}.lic-row span{color:rgba(255,255,255,.62)}.lic-row strong,.lic-row code{color:#fff;word-break:break-word}.lic-domain{display:inline-flex;padding:6px 10px;border:1px solid rgba(255,255,255,.12);border-radius:999px;margin:3px;color:#fff;font-size:.78rem}@media(max-width:900px){.lic-grid,.lic-two{grid-template-columns:1fr 1fr}.lic-row{grid-template-columns:1fr}}@media(max-width:620px){.lic-grid,.lic-two{grid-template-columns:1fr}}
</style>
<section class="lic-grid">
    <article class="lic-card"><span>Status</span><strong><?= sf_admin_h(strtoupper((string)$status['status'])) ?></strong><small><?= sf_admin_h($status['message']) ?></small></article>
    <article class="lic-card"><span>Product ID</span><strong><?= sf_admin_h($product['product_id']) ?></strong><small><?= sf_admin_h($product['product_name']) ?></small></article>
    <article class="lic-card"><span>Edition</span><strong><?= sf_admin_h($receipt['edition'] ?? $product['default_edition']) ?></strong><small><?= sf_admin_h($receipt['license_id'] ?? 'No receipt') ?></small></article>
    <article class="lic-card"><span>Provider</span><strong><?= sf_admin_h($receipt['provider'] ?? $product['provider']) ?></strong><small>Future API adapter ready</small></article>
</section>

<section class="lic-two">
    <article class="sf-admin-panel">
        <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Activation Receipt</span><h2>Installed license identity</h2></div></div>
        <?php if ($receipt): ?><div class="lic-list">
            <div class="lic-row"><span>License ID</span><strong><?= sf_admin_h($receipt['license_id'] ?? '') ?></strong></div>
            <div class="lic-row"><span>Customer</span><strong><?= sf_admin_h($receipt['customer_name'] ?? '') ?></strong></div>
            <div class="lic-row"><span>Customer Email</span><strong><?= sf_admin_h($receipt['customer_email'] ?? '') ?></strong></div>
            <div class="lic-row"><span>Installation ID</span><code><?= sf_admin_h($receipt['installation_id'] ?? '') ?></code></div>
            <div class="lic-row"><span>Activated Domain</span><strong><?= sf_admin_h($receipt['activated_domain'] ?? '') ?></strong></div>
            <div class="lic-row"><span>Activated</span><strong><?= sf_admin_h($receipt['activated_at'] ?? '') ?></strong></div>
            <div class="lic-row"><span>Key Fingerprint</span><code><?= sf_admin_h($receipt['key_fingerprint'] ?? '') ?></code></div>
            <div class="lic-row"><span>Ledger Fingerprint</span><code><?= sf_admin_h($receipt['ledger_record_fingerprint'] ?? '') ?></code></div>
        </div><?php else: ?><p>No activation receipt is installed. Complete the licensed setup wizard.</p><?php endif; ?>
    </article>

    <article class="sf-admin-panel">
        <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Entitlement</span><h2>Domains and update eligibility</h2></div></div>
        <div class="lic-list">
            <div class="lic-row"><span>Authorized Domains</span><div><?php $domains = $receipt['authorized_domains'] ?? ($record['allowed_domains'] ?? []); foreach ((array)$domains as $domain): ?><span class="lic-domain"><?= sf_admin_h($domain) ?></span><?php endforeach; ?><?php if (!$domains): ?><strong>Any domain</strong><?php endif; ?></div></div>
            <div class="lic-row"><span>License Expires</span><strong><?= sf_admin_h($receipt['expires_at'] ?? 'No expiration') ?></strong></div>
            <div class="lic-row"><span>Updates Through</span><strong><?= sf_admin_h($receipt['updates_until'] ?? 'Not limited') ?></strong></div>
            <div class="lic-row"><span>Ledger File</span><strong><?= is_file(sf_license_ledger_path()) ? 'Configured' : 'Not available' ?></strong></div>
        </div>
        <form method="post" class="sf-admin-form" style="margin-top:20px"><?= sf_csrf_field() ?><input type="hidden" name="action" value="revalidate"><button type="submit">Revalidate Local License</button></form>
        <p class="sf-admin-copy">The complete license key is never displayed or stored in the activation receipt. Revalidation uses the license ID, current ledger status, expiration, product ID, and authorized domain.</p>
    </article>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
