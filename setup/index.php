<?php

declare(strict_types=1);

header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Cache-Control: no-store, private, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../includes/installer.php';
sf_install_handle_post();

$product = sf_license_product();
$locked = sf_install_is_locked();
$licenseBypassed = sf_install_license_bypass_enabled();
$licenseValid = sf_license_setup_valid();
$defaultStep = $licenseBypassed ? 'server' : 'license';
$allowedSteps = $licenseBypassed
    ? ['server', 'db', 'sql', 'admin', 'done']
    : ['license', 'server', 'db', 'sql', 'admin', 'done'];
$step = (string)($_GET['step'] ?? $defaultStep);
if (!in_array($step, $allowedSteps, true)) {
    $step = $defaultStep;
}

$checks = [];
$requiredChecksPass = false;
if ($licenseValid && !$locked) {
    $checks = sf_install_checks();
    $requiredChecksPass = sf_install_required_checks_pass($checks);
}

if ($locked) {
    $step = 'done';
} elseif (!$licenseValid) {
    $step = 'license';
} elseif ($step === 'db' && !$requiredChecksPass) {
    $step = 'server';
} elseif ($step === 'sql' && !sf_install_db_ready()) {
    $step = 'db';
} elseif ($step === 'admin' && !sf_install_sql_ready()) {
    $step = 'sql';
} elseif ($step === 'done') {
    $step = 'admin';
}

$db = sf_install_saved_db();
$sqlResults = is_array($_SESSION['sf_install_sql_results'] ?? null) ? $_SESSION['sf_install_sql_results'] : [];
$baseUrl = sf_install_current_url();
$licenseSession = sf_license_setup_session();
$licenseRecord = is_array($licenseSession['record'] ?? null) ? $licenseSession['record'] : null;

$steps = $licenseBypassed
    ? [
        'server' => ['number' => '01', 'label' => 'Server'],
        'db' => ['number' => '02', 'label' => 'Database'],
        'sql' => ['number' => '03', 'label' => 'Build Platform'],
        'admin' => ['number' => '04', 'label' => 'Owner Account'],
        'done' => ['number' => '05', 'label' => 'Complete'],
    ]
    : [
        'license' => ['number' => '01', 'label' => 'Product License'],
        'server' => ['number' => '02', 'label' => 'Server'],
        'db' => ['number' => '03', 'label' => 'Database'],
        'sql' => ['number' => '04', 'label' => 'Build Platform'],
        'admin' => ['number' => '05', 'label' => 'Owner Account'],
        'done' => ['number' => '06', 'label' => 'Complete'],
    ];

function sf_setup_step_available(string $key, bool $locked, bool $licenseValid, bool $serverReady, bool $dbReady, bool $sqlReady): bool
{
    if ($key === 'done') return $locked;
    if ($locked) return false;
    if ($key === 'license') return true;
    if (!$licenseValid) return false;
    if ($key === 'server') return true;
    if ($key === 'db') return $serverReady;
    if ($key === 'sql') return $serverReady && $dbReady;
    if ($key === 'admin') return $serverReady && $dbReady && $sqlReady;
    return false;
}

$score = $checks ? sf_install_check_score($checks) : 0;
$sqlApplied = count(array_filter($sqlResults, static fn($row) => ($row['status'] ?? '') === 'applied'));
$sqlSkipped = count(array_filter($sqlResults, static fn($row) => ($row['status'] ?? '') === 'skipped'));
$sqlFailed = count(array_filter($sqlResults, static fn($row) => in_array(($row['status'] ?? ''), ['failed', 'missing'], true)));
$totalSteps = $licenseBypassed ? 5 : 6;
$serverStep = $licenseBypassed ? 1 : 2;
$dbStep = $licenseBypassed ? 2 : 3;
$sqlStep = $licenseBypassed ? 3 : 4;
$adminStep = $licenseBypassed ? 4 : 5;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Likenessing Setup</title>
    <style>
        :root{--bg:#050505;--panel:#0c0c0b;--panel2:#12110f;--line:rgba(224,173,61,.28);--soft:rgba(255,255,255,.10);--text:#fff;--muted:#b9b4aa;--gold:#e0ad3d;--gold2:#c6902f;--green:#55d59a;--yellow:#f4c95d;--red:#ff7c7c;--shadow:0 28px 80px rgba(0,0,0,.52)}
        *{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(circle at 82% 5%,rgba(224,173,61,.13),transparent 30%),radial-gradient(circle at 8% 90%,rgba(224,173,61,.07),transparent 28%),var(--bg);color:var(--text);font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.wrap{width:min(1180px,calc(100% - 36px));margin:0 auto;padding:28px 0 52px}.top{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:4px 0 26px}.brand{display:flex;align-items:center;gap:15px;color:#fff;text-decoration:none}.brand img{display:block;width:210px;max-height:64px;object-fit:contain;object-position:left center}.brand small{display:block;color:var(--muted);margin-top:4px}.back{color:#fff;text-decoration:none;font-weight:800;font-size:.86rem}.hero{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:28px;align-items:end;margin-bottom:22px}.eyebrow{display:inline-flex;padding:7px 12px;border-radius:999px;background:rgba(224,173,61,.10);border:1px solid var(--line);color:var(--gold);font-size:.74rem;font-weight:900;letter-spacing:.08em;text-transform:uppercase}.hero h1{font-family:Georgia,serif;font-size:clamp(2.5rem,5vw,4.8rem);font-weight:500;letter-spacing:-.05em;line-height:.98;margin:16px 0 12px}.hero p{max-width:760px;color:var(--muted);font-size:1rem;line-height:1.65;margin:0}.product-chip{text-align:right;color:var(--muted);font-size:.78rem}.product-chip code{display:block;color:#fff;background:rgba(255,255,255,.04);border:1px solid var(--soft);padding:10px 13px;border-radius:8px;margin-top:7px}.steps{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:9px;margin-bottom:18px}.step{display:block;padding:13px;border:1px solid var(--soft);background:rgba(255,255,255,.025);border-radius:8px;text-decoration:none;color:var(--muted);min-height:76px}.step b{display:block;color:var(--gold);font-size:.72rem;margin-bottom:7px}.step span{font-size:.77rem;font-weight:800}.step.active{background:linear-gradient(135deg,rgba(224,173,61,.20),rgba(198,144,47,.10));border-color:var(--line);color:#fff}.step.disabled{opacity:.36;pointer-events:none}.panel{background:linear-gradient(145deg,rgba(18,17,15,.97),rgba(7,7,7,.98));border:1px solid var(--line);border-radius:12px;box-shadow:var(--shadow);padding:clamp(22px,4vw,42px)}.panel-head{display:flex;justify-content:space-between;gap:24px;align-items:flex-start;margin-bottom:26px}.panel h2{font-family:Georgia,serif;font-size:clamp(1.75rem,3vw,2.8rem);font-weight:500;letter-spacing:-.04em;margin:0 0 9px}.panel p{color:var(--muted);line-height:1.6;margin:0}.badge{display:inline-flex;padding:7px 12px;border-radius:999px;font-size:.73rem;font-weight:900}.badge.ok{background:rgba(85,213,154,.12);color:#afffda}.badge.warn{background:rgba(244,201,93,.12);color:#ffe29a}.badge.bad{background:rgba(255,124,124,.12);color:#ffc4c4}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}.field{display:flex;flex-direction:column;gap:8px;color:#f4efe5;font-size:.84rem;font-weight:850}.field.full{grid-column:1/-1}.field input,.field select,.field textarea{width:100%;border:1px solid var(--soft);background:rgba(255,255,255,.045);color:#fff;border-radius:8px;padding:14px 15px;font:inherit;outline:none}.field input:focus,.field textarea:focus{border-color:var(--gold);box-shadow:0 0 0 4px rgba(224,173,61,.10)}.field input[readonly]{color:#c9c1b2;background:rgba(255,255,255,.02)}.actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:24px}.btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:6px;padding:14px 20px;background:linear-gradient(180deg,#efc65e,var(--gold2));color:#090806;text-decoration:none;font-weight:900;cursor:pointer}.btn.secondary{background:rgba(255,255,255,.05);border:1px solid var(--soft);color:#fff}.btn:disabled{opacity:.45;cursor:not-allowed}.alerts{display:grid;gap:10px;margin-bottom:16px}.alert{padding:14px 16px;border-radius:8px;font-weight:800}.alert-success{background:rgba(85,213,154,.12);color:#bdffe2}.alert-error{background:rgba(255,124,124,.12);color:#ffd0d0}.alert-warning{background:rgba(244,201,93,.12);color:#ffe6a8}.table-wrap{overflow:auto;border:1px solid var(--soft);border-radius:8px}.table{width:100%;border-collapse:collapse;min-width:720px}.table th,.table td{padding:13px 15px;border-bottom:1px solid rgba(255,255,255,.07);text-align:left;vertical-align:top}.table th{font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#a89f90}.table td{font-size:.87rem;color:#eee8dd}.status-ok{color:#7ef0b7;font-weight:900}.status-bad{color:#ff9a9a;font-weight:900}.status-warn{color:#f7d77e;font-weight:900}.summary{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:20px 0}.metric{padding:18px;border:1px solid var(--soft);border-radius:8px;background:rgba(255,255,255,.025)}.metric strong{display:block;font-size:1.8rem;color:var(--gold)}.metric small{color:var(--muted)}.license-card{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(280px,.85fr);gap:28px}.license-aside{padding:22px;border-radius:10px;background:rgba(255,255,255,.035);border:1px solid var(--soft)}.license-aside ul{padding-left:18px;color:#d2cabd;line-height:1.8}.notice{margin-top:16px;padding:13px 15px;border-radius:8px;background:rgba(244,201,93,.1);color:#ffe3a3;font-size:.82rem;line-height:1.55}.footer{text-align:center;color:#80786c;font-size:.75rem;padding-top:22px}.mode-note{margin-top:18px;padding:13px 15px;border:1px solid var(--line);background:rgba(224,173,61,.06);border-radius:8px;color:#d9d0c0;font-size:.8rem;line-height:1.5}@media(max-width:900px){.steps{grid-template-columns:repeat(3,1fr)}.hero,.license-card{grid-template-columns:1fr}.product-chip{text-align:left}.form-grid{grid-template-columns:1fr}.field.full{grid-column:auto}}@media(max-width:560px){.steps{grid-template-columns:repeat(2,1fr)}.panel{padding:20px}.panel-head{display:block}.summary{grid-template-columns:1fr}.brand img{width:175px}}
    </style>
</head>
<body>
<div class="wrap">
    <header class="top">
        <a class="brand" href="../install.php"><img src="../likenessing-asset.php?name=logo&amp;v=20260716" alt="Likenessing"><span><small>Private platform installation</small></span></a>
        <a class="back" href="../install.php">← Back to overview</a>
    </header>

    <section class="hero">
        <div><span class="eyebrow"><?= $licenseBypassed ? 'Standalone deployment' : 'Guided product setup' ?></span><h1>Install Likenessing.</h1><p><?= $licenseBypassed ? 'Verify the server, connect the database, apply the platform migrations, and create the owner account. The product licensing module remains in the codebase but is bypassed for this standalone show installation.' : 'License the product first, then securely verify the server, connect the database, apply the platform migrations, and create the owner account.' ?></p></div>
        <div class="product-chip">Platform<code>Likenessing</code></div>
    </section>

    <?php $flashes = sf_install_flashes(); if ($flashes): ?><div class="alerts"><?php foreach ($flashes as $flash): ?><div class="alert alert-<?= sf_install_h($flash['type'] ?? 'warning') ?>"><?= sf_install_h($flash['message'] ?? '') ?></div><?php endforeach; ?></div><?php endif; ?>

    <nav class="steps" aria-label="Setup progress">
        <?php foreach ($steps as $key => $meta): $available = sf_setup_step_available($key, $locked, $licenseValid, $requiredChecksPass, sf_install_db_ready(), sf_install_sql_ready()); ?>
            <a class="step <?= $step === $key ? 'active' : '' ?> <?= !$available ? 'disabled' : '' ?>" href="?step=<?= sf_install_h($key) ?>"><b><?= sf_install_h($meta['number']) ?></b><span><?= sf_install_h($meta['label']) ?></span></a>
        <?php endforeach; ?>
    </nav>

    <main class="panel">
        <?php if ($locked): ?>
            <div class="panel-head"><div><span class="badge ok">Installed</span><h2>Likenessing is ready.</h2><p>The installer is closed. Continue platform configuration from the protected administrator dashboard.</p></div></div>
            <div class="actions"><a class="btn" href="../admin/index.php">Open Admin Dashboard</a><a class="btn secondary" href="../index.php">View Public Site</a></div>
        <?php elseif ($step === 'license'): ?>
            <div class="license-card">
                <section>
                    <div class="panel-head"><div><span class="badge warn">Step 1 of 6</span><h2>Activate your product license</h2><p>Enter the license key issued for this product and domain. Technical server details remain hidden until the license is verified.</p></div></div>
                    <form method="post" autocomplete="off">
                        <?= sf_install_csrf_field() ?>
                        <input type="hidden" name="action" value="activate_license">
                        <div class="form-grid">
                            <label class="field"><span>Product</span><input value="<?= sf_install_h($product['product_name']) ?>" readonly></label>
                            <label class="field"><span>Product ID</span><input value="<?= sf_install_h($product['product_id']) ?>" readonly></label>
                            <label class="field full"><span>Product License Key</span><input type="password" name="license_key" inputmode="text" autocomplete="off" placeholder="SFP-XXXX-XXXX-XXXX-XXXX-XXXX" required></label>
                        </div>
                        <div class="actions"><button class="btn" type="submit">Verify License &amp; Continue</button></div>
                    </form>
                    <?php if (!is_file(sf_license_ledger_path())): ?><div class="notice"><strong>License service unavailable:</strong> contact the product vendor before continuing installation.</div><?php endif; ?>
                </section>
                <aside class="license-aside"><span class="badge ok">Offline licensing</span><h2 style="font-size:1.5rem">Private by design</h2><ul><li>The public landing page exposes no server diagnostics.</li><li>The complete key is never stored after activation.</li><li>Only a fingerprint and local receipt are retained.</li><li>The provider adapter can later switch to the VP3 licensing API.</li></ul></aside>
            </div>
        <?php elseif ($step === 'server'): ?>
            <div class="panel-head"><div><span class="badge <?= $requiredChecksPass ? 'ok' : 'bad' ?>">Step <?= $serverStep ?> of <?= $totalSteps ?></span><h2>Server requirements</h2><p>Confirm the required PHP extensions, writable folders, and migration packages before database setup.</p></div><div class="badge <?= $requiredChecksPass ? 'ok' : 'bad' ?>"><?= $score ?>% ready</div></div>
            <div class="table-wrap"><table class="table"><thead><tr><th>Requirement</th><th>Status</th><th>Result</th></tr></thead><tbody><?php foreach ($checks as $check): ?><tr><td><strong><?= sf_install_h($check['label']) ?></strong><?= empty($check['required']) ? '<br><small>Recommended</small>' : '' ?></td><td class="<?= !empty($check['ok']) ? 'status-ok' : (!empty($check['required']) ? 'status-bad' : 'status-warn') ?>"><?= !empty($check['ok']) ? 'Pass' : (!empty($check['required']) ? 'Required' : 'Review') ?></td><td><?= sf_install_h($check['detail'] ?? '') ?></td></tr><?php endforeach; ?></tbody></table></div>
            <?php if ($licenseBypassed): ?><div class="mode-note"><strong>Standalone mode:</strong> the license entry screen is intentionally bypassed for this show deployment. Set <code>enabled</code> to <code>false</code> in <code>config/standalone-install.php</code> to restore the original license-first installer.</div><?php endif; ?>
            <div class="actions"><a class="btn <?= !$requiredChecksPass ? 'secondary' : '' ?>" href="?step=db" <?= !$requiredChecksPass ? 'aria-disabled="true" onclick="return false"' : '' ?>>Continue to Database</a></div>
        <?php elseif ($step === 'db'): ?>
            <div class="panel-head"><div><span class="badge warn">Step <?= $dbStep ?> of <?= $totalSteps ?></span><h2>Connect the database</h2><p>Use an empty MySQL or MariaDB database. Credentials are stored only in the private local configuration after installation.</p></div></div>
            <form method="post" autocomplete="off">
                <?= sf_install_csrf_field() ?><input type="hidden" name="action" value="test_db">
                <div class="form-grid">
                    <label class="field"><span>Host</span><input name="db_host" value="<?= sf_install_h($db['host'] ?? 'localhost') ?>" required></label>
                    <label class="field"><span>Port</span><input name="db_port" value="<?= sf_install_h($db['port'] ?? '3306') ?>" required></label>
                    <label class="field"><span>Database Name</span><input name="db_name" value="<?= sf_install_h($db['name'] ?? '') ?>" required></label>
                    <label class="field"><span>Database User</span><input name="db_user" value="<?= sf_install_h($db['user'] ?? '') ?>" required></label>
                    <label class="field full"><span>Database Password</span><input type="password" name="db_pass" value="" autocomplete="new-password" placeholder="<?= isset($db['pass']) ? 'Saved in this setup session — leave blank to keep it' : '' ?>"></label>
                </div>
                <div class="actions"><button class="btn" type="submit">Test Connection</button><a class="btn secondary" href="?step=server">Back</a></div>
            </form>
        <?php elseif ($step === 'sql'): ?>
            <div class="panel-head"><div><span class="badge warn">Step <?= $sqlStep ?> of <?= $totalSteps ?></span><h2>Build the platform database</h2><p>The installer applies the base schema and all versioned migrations in order with checksums and a database migration lock.</p></div></div>
            <div class="summary"><div class="metric"><strong><?= $sqlApplied ?></strong><small>Applied</small></div><div class="metric"><strong><?= $sqlSkipped ?></strong><small>Already installed</small></div><div class="metric"><strong><?= $sqlFailed ?></strong><small>Needs review</small></div></div>
            <form method="post"><?= sf_install_csrf_field() ?><input type="hidden" name="action" value="run_sql"><div class="actions"><button class="btn" type="submit">Run Database Installer</button><a class="btn secondary" href="?step=db">Back</a></div></form>
            <?php if ($sqlResults): ?><div class="table-wrap" style="margin-top:22px"><table class="table"><thead><tr><th>Migration</th><th>Status</th><th>Result</th></tr></thead><tbody><?php foreach ($sqlResults as $row): $status = (string)($row['status'] ?? ''); ?><tr><td><strong><?= sf_install_h($row['key'] ?? '') ?></strong><br><small><?= sf_install_h($row['label'] ?? '') ?></small></td><td class="<?= in_array($status, ['applied','skipped'], true) ? 'status-ok' : 'status-bad' ?>"><?= sf_install_h(ucfirst($status)) ?></td><td><?= sf_install_h($row['detail'] ?? '') ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
        <?php elseif ($step === 'admin'): ?>
            <div class="panel-head"><div><span class="badge warn">Step <?= $adminStep ?> of <?= $totalSteps ?></span><h2>Create the owner account</h2><p>This account becomes the first administrator and platform owner. The installer locks immediately after completion.</p></div><?php if ($licenseRecord): ?><div class="badge ok"><?= sf_install_h($licenseRecord['edition'] ?? 'standalone') ?></div><?php endif; ?></div>
            <form method="post" autocomplete="off">
                <?= sf_install_csrf_field() ?><input type="hidden" name="action" value="finish">
                <div class="form-grid">
                    <label class="field"><span>Site Name</span><input name="site_name" value="Likenessing" required></label>
                    <label class="field"><span>Site Tagline</span><input name="site_tagline" value="Your face. Your voice. Their contract."></label>
                    <label class="field"><span>Public Base URL</span><input name="base_url" value="<?= sf_install_h($baseUrl) ?>" required></label>
                    <label class="field"><span>Support Email</span><input type="email" name="support_email" value="support@likenessing.com" required></label>
                    <label class="field"><span>Owner Name</span><input name="name" required></label>
                    <label class="field"><span>Owner Email</span><input type="email" name="email" required></label>
                    <label class="field"><span>Owner Password</span><input type="password" name="password" required minlength="12" autocomplete="new-password"></label>
                    <label class="field"><span>Confirm Password</span><input type="password" name="password_confirm" required minlength="12" autocomplete="new-password"></label>
                </div>
                <div class="actions"><button class="btn" type="submit" onclick="return confirm('Finish installation and close the setup wizard?')">Finish Installation</button><a class="btn secondary" href="?step=sql">Back</a></div>
            </form>
        <?php endif; ?>
    </main>
    <footer class="footer">Likenessing · Private platform installation · Installer locks after successful setup.</footer>
</div>
</body>
</html>
