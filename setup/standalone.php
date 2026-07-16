<?php

declare(strict_types=1);

header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Cache-Control: no-store, private, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../includes/installer.php';
sf_install_handle_post();

$locked = sf_install_is_locked();
$allowedSteps = ['server', 'db', 'sql', 'admin', 'done'];
$step = preg_replace('/[^a-z]/', '', strtolower((string)($_GET['step'] ?? 'server'))) ?: 'server';
if (!in_array($step, $allowedSteps, true)) {
    $step = 'server';
}

$checks = $locked ? [] : sf_install_checks();
$requiredChecksPass = $locked ? true : sf_install_required_checks_pass($checks);

if ($locked) {
    $step = 'done';
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
$score = $checks ? sf_install_check_score($checks) : 100;
$sqlApplied = count(array_filter($sqlResults, static fn($row) => ($row['status'] ?? '') === 'applied'));
$sqlSkipped = count(array_filter($sqlResults, static fn($row) => ($row['status'] ?? '') === 'skipped'));
$sqlFailed = count(array_filter($sqlResults, static fn($row) => in_array(($row['status'] ?? ''), ['failed', 'missing'], true)));

$steps = [
    'server' => ['number' => '01', 'label' => 'Server'],
    'db' => ['number' => '02', 'label' => 'Database'],
    'sql' => ['number' => '03', 'label' => 'Build Platform'],
    'admin' => ['number' => '04', 'label' => 'Owner Account'],
    'done' => ['number' => '05', 'label' => 'Complete'],
];

function sf_desertrio_setup_step_available(string $key, bool $locked, bool $serverReady, bool $dbReady, bool $sqlReady): bool
{
    if ($key === 'done') return $locked;
    if ($locked) return false;
    if ($key === 'server') return true;
    if ($key === 'db') return $serverReady;
    if ($key === 'sql') return $serverReady && $dbReady;
    if ($key === 'admin') return $serverReady && $dbReady && $sqlReady;
    return false;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>DesertRio Setup</title>
    <style>
        :root{--cream:#f7f1e7;--paper:#fffdf9;--ink:#17130f;--muted:#756b60;--gold:#b68a43;--gold-dark:#8e672d;--line:#dfd4c4;--rose:#b85d55;--green:#4f7c61;--shadow:0 28px 80px rgba(58,42,26,.14)}
        *{box-sizing:border-box}html{background:var(--cream)}body{margin:0;color:var(--ink);font-family:Arial,Helvetica,sans-serif;background:radial-gradient(circle at 92% 5%,rgba(182,138,67,.16),transparent 32%),linear-gradient(180deg,#fffdf9 0,#f7f1e7 100%);min-height:100vh}.wrap{width:min(1180px,calc(100% - 36px));margin:0 auto;padding:26px 0 52px}.top{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:4px 0 28px}.brand{display:flex;align-items:center;gap:14px;color:var(--ink);text-decoration:none}.mark{display:grid;place-items:center;width:50px;height:50px;border-radius:50%;background:var(--gold);color:#fff;font-family:Georgia,serif;font-size:1.1rem;font-weight:700;box-shadow:0 14px 35px rgba(182,138,67,.28)}.brand strong{display:block;font-family:Georgia,'Times New Roman',serif;font-size:1.25rem;letter-spacing:.03em}.brand small{display:block;color:var(--muted);margin-top:3px}.back{color:var(--gold-dark);text-decoration:none;font-size:.86rem;font-weight:700}.hero{display:grid;grid-template-columns:1fr auto;gap:24px;align-items:end;margin-bottom:22px}.eyebrow{display:inline-flex;padding:7px 12px;border:1px solid rgba(182,138,67,.32);border-radius:999px;color:var(--gold-dark);font-size:.71rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;background:rgba(255,255,255,.62)}.hero h1{font-family:Georgia,'Times New Roman',serif;font-size:clamp(2.5rem,5vw,4.9rem);font-weight:500;line-height:.98;letter-spacing:-.045em;margin:16px 0 12px}.hero p{max-width:760px;color:var(--muted);font-size:1rem;line-height:1.65;margin:0}.mode{padding:11px 14px;border:1px solid var(--line);border-radius:13px;background:rgba(255,255,255,.7);color:var(--muted);font-size:.78rem}.mode strong{display:block;color:var(--green);margin-top:3px}.steps{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:9px;margin-bottom:18px}.step{display:block;min-height:76px;padding:13px;border:1px solid var(--line);border-radius:14px;background:rgba(255,255,255,.62);color:var(--muted);text-decoration:none}.step b{display:block;color:var(--gold-dark);font-size:.71rem;margin-bottom:7px}.step span{font-size:.79rem;font-weight:800}.step.active{background:#fff;border-color:var(--gold);color:var(--ink);box-shadow:0 14px 35px rgba(58,42,26,.08)}.step.disabled{opacity:.42;pointer-events:none}.panel{background:rgba(255,253,249,.94);border:1px solid var(--line);border-radius:24px;box-shadow:var(--shadow);padding:clamp(22px,4vw,42px)}.panel-head{display:flex;justify-content:space-between;gap:24px;align-items:flex-start;margin-bottom:26px}.panel h2{font-family:Georgia,'Times New Roman',serif;font-size:clamp(1.8rem,3vw,2.8rem);font-weight:500;letter-spacing:-.035em;margin:0 0 9px}.panel p{color:var(--muted);line-height:1.6;margin:0}.badge{display:inline-flex;padding:7px 12px;border-radius:999px;font-size:.72rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase}.badge.ok{background:rgba(79,124,97,.12);color:var(--green)}.badge.warn{background:rgba(182,138,67,.14);color:var(--gold-dark)}.badge.bad{background:rgba(184,93,85,.12);color:var(--rose)}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}.field{display:flex;flex-direction:column;gap:8px;color:var(--ink);font-size:.82rem;font-weight:800}.field.full{grid-column:1/-1}.field input,.field select,.field textarea{width:100%;border:1px solid var(--line);background:#fff;color:var(--ink);border-radius:12px;padding:14px 15px;font:inherit;outline:none}.field input:focus,.field textarea:focus{border-color:var(--gold);box-shadow:0 0 0 4px rgba(182,138,67,.12)}.actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:24px}.btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:999px;padding:14px 22px;background:var(--gold);color:#fff;text-decoration:none;font-weight:800;cursor:pointer}.btn:hover{background:var(--gold-dark)}.btn.secondary{background:#fff;border:1px solid var(--line);color:var(--ink)}.alerts{display:grid;gap:10px;margin-bottom:16px}.alert{padding:14px 16px;border-radius:13px;font-weight:700}.alert-success{background:rgba(79,124,97,.12);color:#315b43}.alert-error{background:rgba(184,93,85,.12);color:#8e3832}.alert-warning{background:rgba(182,138,67,.14);color:#78521e}.table-wrap{overflow:auto;border:1px solid var(--line);border-radius:16px}.table{width:100%;border-collapse:collapse;min-width:720px}.table th,.table td{padding:13px 15px;border-bottom:1px solid #eee5d9;text-align:left;vertical-align:top}.table th{font-size:.7rem;text-transform:uppercase;letter-spacing:.09em;color:var(--muted);background:#fbf7f0}.table td{font-size:.86rem}.status-ok{color:var(--green);font-weight:800}.status-bad{color:var(--rose);font-weight:800}.status-warn{color:var(--gold-dark);font-weight:800}.summary{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:20px 0}.metric{padding:18px;border:1px solid var(--line);border-radius:14px;background:#fff}.metric strong{display:block;font-family:Georgia,serif;font-size:1.9rem;font-weight:500}.metric small{color:var(--muted)}.footer{text-align:center;color:var(--muted);font-size:.74rem;padding-top:22px}.note{margin-top:18px;padding:14px 16px;border-left:3px solid var(--gold);background:#fbf6ed;color:var(--muted);line-height:1.55;font-size:.84rem}@media(max-width:900px){.hero{grid-template-columns:1fr}.steps{grid-template-columns:repeat(3,1fr)}.form-grid{grid-template-columns:1fr}.field.full{grid-column:auto}}@media(max-width:560px){.steps{grid-template-columns:repeat(2,1fr)}.panel{padding:20px}.panel-head{display:block}.summary{grid-template-columns:1fr}.top{align-items:flex-start}.back{margin-top:10px}}
    </style>
</head>
<body>
<div class="wrap">
    <header class="top">
        <a class="brand" href="../install.php"><span class="mark">DR</span><span><strong>DesertRio</strong><small>Standalone platform installation</small></span></a>
        <a class="back" href="../install.php">← Installer overview</a>
    </header>

    <section class="hero">
        <div><span class="eyebrow">DesertRio setup</span><h1>Build the show platform.</h1><p>Verify the server, connect the database, install the complete application schema, and create the first owner account. Product-license registration is temporarily bypassed on this branch.</p></div>
        <div class="mode">Installation mode<strong>Standalone bypass active</strong></div>
    </section>

    <?php $flashes = sf_install_flashes(); if ($flashes): ?>
        <div class="alerts"><?php foreach ($flashes as $flash): ?><div class="alert alert-<?= sf_install_h($flash['type'] ?? 'warning') ?>"><?= sf_install_h($flash['message'] ?? '') ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <nav class="steps" aria-label="Setup progress">
        <?php foreach ($steps as $key => $meta): $available = sf_desertrio_setup_step_available($key, $locked, $requiredChecksPass, sf_install_db_ready(), sf_install_sql_ready()); ?>
            <a class="step <?= $step === $key ? 'active' : '' ?> <?= !$available ? 'disabled' : '' ?>" href="?step=<?= sf_install_h($key) ?>"><b><?= sf_install_h($meta['number']) ?></b><span><?= sf_install_h($meta['label']) ?></span></a>
        <?php endforeach; ?>
    </nav>

    <main class="panel">
        <?php if ($locked): ?>
            <div class="panel-head"><div><span class="badge ok">Installed</span><h2>DesertRio is ready.</h2><p>The installer is locked. Continue configuration and catalog setup from the administrator dashboard.</p></div></div>
            <div class="actions"><a class="btn" href="../admin/index.php">Open Admin Dashboard</a><a class="btn secondary" href="../index.php">View Public Site</a></div>

        <?php elseif ($step === 'server'): ?>
            <div class="panel-head"><div><span class="badge <?= $requiredChecksPass ? 'ok' : 'bad' ?>">Step 1 of 5</span><h2>Server requirements</h2><p>Required PHP extensions, writable storage, and the complete SQL migration package must be available before database setup.</p></div><div class="badge <?= $requiredChecksPass ? 'ok' : 'bad' ?>"><?= $score ?>% ready</div></div>
            <div class="table-wrap"><table class="table"><thead><tr><th>Requirement</th><th>Status</th><th>Result</th></tr></thead><tbody><?php foreach ($checks as $check): ?><tr><td><strong><?= sf_install_h($check['label']) ?></strong><?= empty($check['required']) ? '<br><small>Recommended</small>' : '' ?></td><td class="<?= !empty($check['ok']) ? 'status-ok' : (!empty($check['required']) ? 'status-bad' : 'status-warn') ?>"><?= !empty($check['ok']) ? 'Pass' : (!empty($check['required']) ? 'Required' : 'Review') ?></td><td><?= sf_install_h($check['detail'] ?? '') ?></td></tr><?php endforeach; ?></tbody></table></div>
            <div class="actions"><a class="btn <?= !$requiredChecksPass ? 'secondary' : '' ?>" href="?step=db" <?= !$requiredChecksPass ? 'aria-disabled="true" onclick="return false"' : '' ?>>Continue to Database</a></div>

        <?php elseif ($step === 'db'): ?>
            <div class="panel-head"><div><span class="badge warn">Step 2 of 5</span><h2>Connect the database</h2><p>Create an empty MySQL or MariaDB database and enter its credentials below.</p></div></div>
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
            <div class="panel-head"><div><span class="badge warn">Step 3 of 5</span><h2>Build the application database</h2><p>The installer applies the base schema and every versioned migration in order using checksums and a database migration lock.</p></div></div>
            <div class="summary"><div class="metric"><strong><?= $sqlApplied ?></strong><small>Applied</small></div><div class="metric"><strong><?= $sqlSkipped ?></strong><small>Already installed</small></div><div class="metric"><strong><?= $sqlFailed ?></strong><small>Needs review</small></div></div>
            <form method="post"><?= sf_install_csrf_field() ?><input type="hidden" name="action" value="run_sql"><div class="actions"><button class="btn" type="submit">Run Database Installer</button><a class="btn secondary" href="?step=db">Back</a></div></form>
            <?php if ($sqlResults): ?><div class="table-wrap" style="margin-top:22px"><table class="table"><thead><tr><th>Migration</th><th>Status</th><th>Result</th></tr></thead><tbody><?php foreach ($sqlResults as $row): $status = (string)($row['status'] ?? ''); ?><tr><td><strong><?= sf_install_h($row['key'] ?? '') ?></strong><br><small><?= sf_install_h($row['label'] ?? '') ?></small></td><td class="<?= in_array($status, ['applied','skipped'], true) ? 'status-ok' : 'status-bad' ?>"><?= sf_install_h(ucfirst($status)) ?></td><td><?= sf_install_h($row['detail'] ?? '') ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>

        <?php elseif ($step === 'admin'): ?>
            <div class="panel-head"><div><span class="badge warn">Step 4 of 5</span><h2>Create the owner account</h2><p>This account becomes the first administrator. The installer locks immediately after completion.</p></div></div>
            <form method="post" autocomplete="off">
                <?= sf_install_csrf_field() ?><input type="hidden" name="action" value="finish">
                <div class="form-grid">
                    <label class="field"><span>Site Name</span><input name="site_name" value="DesertRio" required></label>
                    <label class="field"><span>Site Tagline</span><input name="site_tagline" value="Angel faces. Desert heat. No secrets."></label>
                    <label class="field"><span>Public Base URL</span><input name="base_url" value="<?= sf_install_h($baseUrl) ?>" required></label>
                    <label class="field"><span>Support Email</span><input type="email" name="support_email" value="support@desertrio.com" required></label>
                    <label class="field"><span>Owner Name</span><input name="name" required></label>
                    <label class="field"><span>Owner Email</span><input type="email" name="email" required></label>
                    <label class="field"><span>Owner Password</span><input type="password" name="password" required minlength="12" autocomplete="new-password"></label>
                    <label class="field"><span>Confirm Password</span><input type="password" name="password_confirm" required minlength="12" autocomplete="new-password"></label>
                </div>
                <div class="note">The original license registration files remain in the codebase. Set <code>SF_INSTALL_LICENSE_BYPASS=0</code> later to restore the licensed setup wizard.</div>
                <div class="actions"><button class="btn" type="submit" onclick="return confirm('Finish installation and lock the installer?')">Finish Installation</button><a class="btn secondary" href="?step=sql">Back</a></div>
            </form>
        <?php endif; ?>
    </main>

    <footer class="footer">DesertRio · Standalone installer · Original licensing runtime preserved but temporarily bypassed.</footer>
</div>
</body>
</html>
