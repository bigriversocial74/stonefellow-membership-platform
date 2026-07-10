<?php

declare(strict_types=1);

header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Cache-Control: no-store, private, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../includes/installer.php';
sf_install_handle_post();

$product = sf_license_product();
$locked = sf_install_is_locked();
$allowedSteps = ['license', 'server', 'db', 'sql', 'admin', 'done'];
$step = (string)($_GET['step'] ?? 'license');
if (!in_array($step, $allowedSteps, true)) $step = 'license';

$checks = [];
$requiredChecksPass = false;
if (sf_license_setup_valid() && !$locked) {
    $checks = sf_install_checks();
    $requiredChecksPass = sf_install_required_checks_pass($checks);
}

if ($locked) {
    $step = 'done';
} elseif (!sf_license_setup_valid()) {
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

$steps = [
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
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= sf_install_h($product['product_name']) ?> Setup</title>
    <style>
        :root{--bg:#050914;--panel:#0d1528;--panel2:#111c34;--line:rgba(255,255,255,.11);--text:#f8fafc;--muted:#9eacc4;--blue:#4e73ff;--violet:#9949ff;--green:#42d392;--yellow:#f4c95d;--red:#ff7070;--shadow:0 28px 80px rgba(0,0,0,.38)}
        *{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 82% 8%,rgba(78,115,255,.22),transparent 32%),radial-gradient(circle at 15% 85%,rgba(153,73,255,.18),transparent 28%),var(--bg);color:var(--text);font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;min-height:100vh}.wrap{width:min(1180px,calc(100% - 36px));margin:0 auto;padding:28px 0 52px}.top{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:4px 0 26px}.brand{display:flex;align-items:center;gap:14px;color:#fff;text-decoration:none}.mark{display:grid;place-items:center;width:46px;height:46px;border-radius:14px;background:linear-gradient(135deg,var(--blue),var(--violet));font-weight:950;box-shadow:0 14px 40px rgba(78,115,255,.3)}.brand strong{display:block}.brand small{display:block;color:var(--muted);margin-top:3px}.back{color:#dce5ff;text-decoration:none;font-weight:800;font-size:.88rem}.hero{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:28px;align-items:end;margin-bottom:22px}.eyebrow{display:inline-flex;padding:7px 12px;border-radius:999px;background:rgba(78,115,255,.15);border:1px solid rgba(78,115,255,.28);color:#cbd7ff;font-size:.74rem;font-weight:900;letter-spacing:.08em;text-transform:uppercase}.hero h1{font-size:clamp(2.3rem,5vw,4.6rem);letter-spacing:-.06em;line-height:.98;margin:16px 0 12px}.hero p{max-width:720px;color:var(--muted);font-size:1rem;line-height:1.65;margin:0}.product-chip{text-align:right;color:var(--muted);font-size:.8rem}.product-chip code{display:block;color:#fff;background:rgba(255,255,255,.06);border:1px solid var(--line);padding:10px 13px;border-radius:12px;margin-top:7px}.steps{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:9px;margin-bottom:18px}.step{display:block;padding:13px;border:1px solid var(--line);background:rgba(255,255,255,.035);border-radius:14px;text-decoration:none;color:var(--muted);min-height:76px}.step b{display:block;color:#fff;font-size:.72rem;margin-bottom:7px}.step span{font-size:.77rem;font-weight:800}.step.active{background:linear-gradient(135deg,rgba(78,115,255,.32),rgba(153,73,255,.28));border-color:rgba(122,107,255,.58);color:#fff}.step.disabled{opacity:.38;pointer-events:none}.panel{background:linear-gradient(145deg,rgba(17,28,52,.96),rgba(8,14,28,.96));border:1px solid var(--line);border-radius:24px;box-shadow:var(--shadow);padding:clamp(22px,4vw,42px)}.panel-head{display:flex;justify-content:space-between;gap:24px;align-items:flex-start;margin-bottom:26px}.panel h2{font-size:clamp(1.65rem,3vw,2.6rem);letter-spacing:-.045em;margin:0 0 9px}.panel p{color:var(--muted);line-height:1.6;margin:0}.badge{display:inline-flex;padding:7px 12px;border-radius:999px;font-size:.75rem;font-weight:900}.badge.ok{background:rgba(66,211,146,.13);color:#a9ffd8}.badge.warn{background:rgba(244,201,93,.13);color:#ffe29a}.badge.bad{background:rgba(255,112,112,.13);color:#ffc0c0}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}.field{display:flex;flex-direction:column;gap:8px;color:#e8edfa;font-size:.84rem;font-weight:850}.field.full{grid-column:1/-1}.field input,.field select,.field textarea{width:100%;border:1px solid var(--line);background:rgba(255,255,255,.055);color:#fff;border-radius:12px;padding:14px 15px;font:inherit;outline:none}.field input:focus,.field textarea:focus{border-color:#758fff;box-shadow:0 0 0 4px rgba(78,115,255,.13)}.field input[readonly]{color:#b9c6dd;background:rgba(255,255,255,.025)}.actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:24px}.btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:12px;padding:14px 20px;background:linear-gradient(135deg,var(--blue),var(--violet));color:#fff;text-decoration:none;font-weight:900;cursor:pointer}.btn.secondary{background:rgba(255,255,255,.06);border:1px solid var(--line)}.btn:disabled{opacity:.45;cursor:not-allowed}.alerts{display:grid;gap:10px;margin-bottom:16px}.alert{padding:14px 16px;border-radius:13px;font-weight:800}.alert-success{background:rgba(66,211,146,.13);color:#b9ffe1}.alert-error{background:rgba(255,112,112,.13);color:#ffd0d0}.alert-warning{background:rgba(244,201,93,.13);color:#ffe6a8}.table-wrap{overflow:auto;border:1px solid var(--line);border-radius:16px}.table{width:100%;border-collapse:collapse;min-width:720px}.table th,.table td{padding:13px 15px;border-bottom:1px solid rgba(255,255,255,.075);text-align:left;vertical-align:top}.table th{font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#8ea0bd}.table td{font-size:.87rem;color:#dfe6f4}.status-ok{color:#7ef0b7;font-weight:900}.status-bad{color:#ff9a9a;font-weight:900}.status-warn{color:#f7d77e;font-weight:900}.summary{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:20px 0}.metric{padding:18px;border:1px solid var(--line);border-radius:14px;background:rgba(255,255,255,.035)}.metric strong{display:block;font-size:1.8rem}.metric small{color:var(--muted)}.license-card{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(280px,.85fr);gap:28px}.license-aside{padding:22px;border-radius:17px;background:rgba(255,255,255,.045);border:1px solid var(--line)}.license-aside ul{padding-left:18px;color:#c8d3e7;line-height:1.8}.notice{margin-top:16px;padding:13px 15px;border-radius:12px;background:rgba(244,201,93,.1);color:#ffe3a3;font-size:.82rem;line-height:1.55}.footer{text-align:center;color:#75839c;font-size:.75rem;padding-top:22px}@media(max-width:900px){.steps{grid-template-columns:repeat(3,1fr)}.hero,.license-card{grid-template-columns:1fr}.product-chip{text-align:left}.form-grid{grid-template-columns:1fr}.field.full{grid-column:auto}}@media(max-width:560px){.steps{grid-template-columns:repeat(2,1fr)}.panel{padding:20px}.panel-head{display:block}.summary{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="wrap">
    <header class="top">
        <a class="brand" href="../install.php"><span class="mark">VP3</span><span><strong><?= sf_install_h($product['product_name']) ?></strong><small>Secure installation</small></span></a>
        <a class="back" href="../install.php">← Back to overview</a>
    </header>

    <section class="hero">
        <div><span class="eyebrow">Guided product setup</span><h1>Install with confidence.</h1><p>License the product first, then securely verify the server, connect the database, apply the platform migrations, and create the owner account.</p></div>
        <div class="product-chip">Product ID<code><?= sf_install_h($product['product_id']) ?></code></div>
    </section>

    <?php $flashes = sf_install_flashes(); if ($flashes): ?><div class="alerts"><?php foreach ($flashes as $flash): ?><div class="alert alert-<?= sf_install_h($flash['type'] ?? 'warning') ?>"><?= sf_install_h($flash['message'] ?? '') ?></div><?php endforeach; ?></div><?php endif; ?>

    <nav class="steps" aria-label="Setup progress">
        <?php foreach ($steps as $key => $meta): $available = sf_setup_step_available($key, $locked, sf_license_setup_valid(), $requiredChecksPass, sf_install_db_ready(), sf_install_sql_ready()); ?>
            <a class="step <?= $step === $key ? 'active' : '' ?> <?= !$available ? 'disabled' : '' ?>" href="?step=<?= sf_install_h($key) ?>"><b><?= sf_install_h($meta['number']) ?></b><span><?= sf_install_h($meta['label']) ?></span></a>
        <?php endforeach; ?>
    </nav>

    <main class="panel">
        <?php if ($locked): ?>
            <div class="panel-head"><div><span class="badge ok">Installed</span><h2>Stonefellow is ready.</h2><p>The installer is closed. Continue platform configuration from the protected administrator dashboard.</p></div></div>
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
                        <div class="actions"><button class="btn" type="submit">Verify License & Continue</button></div>
                    </form>
                    <?php if (!is_file(sf_license_ledger_path())): ?><div class="notice"><strong>License service unavailable:</strong> contact the product vendor before continuing installation.</div><?php endif; ?>
                </section>
                <aside class="license-aside"><span class="badge ok">Offline licensing</span><h2 style="font-size:1.5rem">Private by design</h2><ul><li>The public landing page exposes no server diagnostics.</li><li>The complete key is never stored after activation.</li><li>Only a fingerprint and local receipt are retained.</li><li>The provider adapter can later switch to the VP3 licensing API.</li></ul></aside>
            </div>
        <?php elseif ($step === 'server'): ?>
            <div class="panel-head"><div><span class="badge <?= $requiredChecksPass ? 'ok' : 'bad' ?>">Step 2 of 6</span><h2>Server requirements</h2><p>These checks are visible only after license validation. Required failures must be corrected before database setup.</p></div><div class="badge <?= $requiredChecksPass ? 'ok' : 'bad' ?>"><?= $score ?>% ready</div></div>
            <div class="table-wrap"><table class="table"><thead><tr><th>Requirement</th><th>Status</th><th>Result</th></tr></thead><tbody><?php foreach ($checks as $check): ?><tr><td><strong><?= sf_install_h($check['label']) ?></strong><?= empty($check['required']) ? '<br><small>Recommended</small>' : '' ?></td><td class="<?= !empty($check['ok']) ? 'status-ok' : (!empty($check['required']) ? 'status-bad' : 'status-warn') ?>"><?= !empty($check['ok']) ? 'Pass' : (!empty($check['required']) ? 'Required' : 'Review') ?></td><td><?= sf_install_h($check['detail'] ?? '') ?></td></tr><?php endforeach; ?></tbody></table></div>
            <div class="actions"><a class="btn <?= !$requiredChecksPass ? 'secondary' : '' ?>" href="?step=db" <?= !$requiredChecksPass ? 'aria-disabled="true" onclick="return false"' : '' ?>>Continue to Database</a></div>
        <?php elseif ($step === 'db'): ?>
            <div class="panel-head"><div><span class="badge warn">Step 3 of 6</span><h2>Connect the database</h2><p>Use an empty MySQL or MariaDB database. Credentials are stored only in the private local configuration after installation.</p></div></div>
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
            <div class="panel-head"><div><span class="badge warn">Step 4 of 6</span><h2>Build the platform database</h2><p>The installer applies the base schema and all versioned migrations in order with checksums and a database migration lock.</p></div></div>
            <div class="summary"><div class="metric"><strong><?= $sqlApplied ?></strong><small>Applied</small></div><div class="metric"><strong><?= $sqlSkipped ?></strong><small>Already installed</small></div><div class="metric"><strong><?= $sqlFailed ?></strong><small>Needs review</small></div></div>
            <form method="post"><?= sf_install_csrf_field() ?><input type="hidden" name="action" value="run_sql"><div class="actions"><button class="btn" type="submit">Run Database Installer</button><a class="btn secondary" href="?step=db">Back</a></div></form>
            <?php if ($sqlResults): ?><div class="table-wrap" style="margin-top:22px"><table class="table"><thead><tr><th>Migration</th><th>Status</th><th>Result</th></tr></thead><tbody><?php foreach ($sqlResults as $row): $status = (string)($row['status'] ?? ''); ?><tr><td><strong><?= sf_install_h($row['key'] ?? '') ?></strong><br><small><?= sf_install_h($row['label'] ?? '') ?></small></td><td class="<?= in_array($status, ['applied','skipped'], true) ? 'status-ok' : 'status-bad' ?>"><?= sf_install_h(ucfirst($status)) ?></td><td><?= sf_install_h($row['detail'] ?? '') ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
        <?php elseif ($step === 'admin'): ?>
            <div class="panel-head"><div><span class="badge warn">Step 5 of 6</span><h2>Create the owner account</h2><p>This account becomes the first administrator and platform owner. The installer locks immediately after completion.</p></div><?php if ($licenseRecord): ?><div class="badge ok"><?= sf_install_h($licenseRecord['edition'] ?? 'licensed') ?></div><?php endif; ?></div>
            <form method="post" autocomplete="off">
                <?= sf_install_csrf_field() ?><input type="hidden" name="action" value="finish">
                <div class="form-grid">
                    <label class="field"><span>Site Name</span><input name="site_name" value="Stonefellow" required></label>
                    <label class="field"><span>Site Tagline</span><input name="site_tagline" value="Watch the show. Stream the music. Wear the story."></label>
                    <label class="field"><span>Public Base URL</span><input name="base_url" value="<?= sf_install_h($baseUrl) ?>" required></label>
                    <label class="field"><span>Support Email</span><input type="email" name="support_email" value="support@stonefellow.tv" required></label>
                    <label class="field"><span>Owner Name</span><input name="name" required></label>
                    <label class="field"><span>Owner Email</span><input type="email" name="email" required></label>
                    <label class="field"><span>Owner Password</span><input type="password" name="password" required minlength="12" autocomplete="new-password"></label>
                    <label class="field"><span>Confirm Password</span><input type="password" name="password_confirm" required minlength="12" autocomplete="new-password"></label>
                </div>
                <div class="actions"><button class="btn" type="submit" onclick="return confirm('Finish installation and close the setup wizard?')">Finish Installation</button><a class="btn secondary" href="?step=sql">Back</a></div>
            </form>
        <?php endif; ?>
    </main>
    <footer class="footer">VP3 Media Group · Licensed product setup · Technical details are never displayed on the public installer homepage.</footer>
</div>
</body>
</html>
