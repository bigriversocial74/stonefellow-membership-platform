<?php
require __DIR__ . '/includes/installer.php';
sf_install_handle_post();
$step = (string)($_GET['step'] ?? 'server');
if (!in_array($step, ['server','db','sql','admin','done'], true)) { $step = 'server'; }
$checks = sf_install_checks();
$score = sf_install_check_score($checks);
$db = sf_install_saved_db();
$sqlResults = $_SESSION['sf_install_sql_results'] ?? [];
$baseUrl = sf_install_current_url();
$locked = sf_install_is_locked();
$steps = ['server'=>'Server','db'=>'Database','sql'=>'SQL','admin'=>'Admin','done'=>'Launch'];
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Stonefellow Installer</title>
  <link rel="stylesheet" href="assets/css/stonefellow.css">
  <style>
    body{margin:0;background:#f6f4ef;color:#17130f;font-family:Inter,Arial,sans-serif}.sf-install-wrap{max-width:1180px;margin:0 auto;padding:34px 18px 64px}.sf-install-top{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;margin-bottom:22px}.sf-install-top h1{font-size:clamp(2.2rem,6vw,5rem);line-height:.92;margin:10px 0}.sf-install-badge{display:inline-flex;border:1px solid #ded5c7;border-radius:999px;padding:8px 12px;background:white;font-weight:800}.sf-install-score{min-width:180px;background:#111;color:white;border-radius:28px;padding:22px;box-shadow:0 20px 60px rgba(0,0,0,.16)}.sf-install-score strong{display:block;font-size:3rem}.sf-install-steps{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin:20px 0}.sf-install-steps a{background:white;border:1px solid #ddd3c4;border-radius:18px;padding:14px;text-decoration:none;color:#191511;font-weight:900}.sf-install-steps a.is-active{background:#191511;color:#fff}.sf-install-card{background:#fff;border:1px solid #ddd3c4;border-radius:28px;padding:24px;margin:18px 0;box-shadow:0 18px 50px rgba(40,30,10,.08)}.sf-install-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.sf-install-field{display:flex;flex-direction:column;gap:8px;font-weight:800}.sf-install-field input,.sf-install-field textarea{border:1px solid #d8cebf;border-radius:14px;padding:13px 14px;font:inherit}.sf-install-btn{border:0;border-radius:999px;background:#191511;color:#fff;padding:13px 18px;font-weight:900;cursor:pointer}.sf-install-table{width:100%;border-collapse:collapse}.sf-install-table th,.sf-install-table td{padding:12px;border-bottom:1px solid #eee3d3;text-align:left;vertical-align:top}.sf-ok{color:#097b45;font-weight:900}.sf-bad{color:#b42318;font-weight:900}.sf-warn{color:#9a6700;font-weight:900}.sf-install-alert{border-radius:16px;padding:13px 15px;margin:10px 0;font-weight:800}.sf-install-alert-success{background:#e8f7ee;color:#08613a}.sf-install-alert-error{background:#fdecec;color:#a31313}.sf-install-alert-warning{background:#fff4d5;color:#7a5200}@media(max-width:800px){.sf-install-top,.sf-install-grid{grid-template-columns:1fr;display:grid}.sf-install-steps{grid-template-columns:1fr 1fr}.sf-install-score{min-width:0}}
  </style>
</head>
<body>
<main class="sf-install-wrap">
  <section class="sf-install-top">
    <div><span class="sf-install-badge">Stonefellow Launch Wizard</span><h1>One-time web installer</h1><p>Upload the script, enter database details, run SQL, create the first admin, lock the installer, and launch the admin dashboard.</p></div>
    <div class="sf-install-score"><span>Readiness</span><strong><?= (int)$score ?>%</strong><small><?= $locked ? 'Installer locked' : 'Installer open' ?></small></div>
  </section>

  <?php foreach (sf_install_flashes() as $msg): ?><div class="sf-install-alert sf-install-alert-<?= sf_install_h($msg['type'] ?? 'warning') ?>"><?= sf_install_h($msg['message'] ?? '') ?></div><?php endforeach; ?>

  <nav class="sf-install-steps">
    <?php foreach ($steps as $key=>$label): ?><a class="<?= $step===$key?'is-active':'' ?>" href="install.php?step=<?= sf_install_h($key) ?>"><?= sf_install_h($label) ?></a><?php endforeach; ?>
  </nav>

  <?php if ($locked): ?>
    <section class="sf-install-card"><h2>Installer is locked</h2><p>This installation is complete. To run the installer again, manually remove <code>storage/install.lock</code> and confirm you have a database backup.</p><p><a class="sf-install-btn" href="admin/index.php">Open Admin</a></p></section>
  <?php elseif ($step === 'server'): ?>
    <section class="sf-install-card"><h2>Step 1 — Server check</h2><p>Fix any failed checks before continuing.</p><table class="sf-install-table"><thead><tr><th>Check</th><th>Status</th><th>Detail</th></tr></thead><tbody><?php foreach ($checks as $check): ?><tr><td><strong><?= sf_install_h($check['label']) ?></strong></td><td><?= !empty($check['ok'])?'<span class="sf-ok">Pass</span>':'<span class="sf-bad">Review</span>' ?></td><td><?= sf_install_h($check['detail'] ?? '') ?></td></tr><?php endforeach; ?></tbody></table><p><a class="sf-install-btn" href="install.php?step=db">Continue to Database</a></p></section>
  <?php elseif ($step === 'db'): ?>
    <section class="sf-install-card"><h2>Step 2 — Database setup</h2><form method="post"><input type="hidden" name="action" value="test_db"><div class="sf-install-grid"><label class="sf-install-field">Host<input name="db_host" value="<?= sf_install_h($db['host'] ?? 'localhost') ?>" required></label><label class="sf-install-field">Port<input name="db_port" value="<?= sf_install_h($db['port'] ?? '3306') ?>"></label><label class="sf-install-field">Database Name<input name="db_name" value="<?= sf_install_h($db['name'] ?? '') ?>" required></label><label class="sf-install-field">Database User<input name="db_user" value="<?= sf_install_h($db['user'] ?? '') ?>" required></label><label class="sf-install-field">Database Password<input type="password" name="db_pass" value="<?= sf_install_h($db['pass'] ?? '') ?>"></label></div><p><button class="sf-install-btn" type="submit">Test Connection</button></p></form></section>
  <?php elseif ($step === 'sql'): ?>
    <section class="sf-install-card"><h2>Step 3 — Run SQL</h2><p>This runs the base schema and migrations 001 through 011 in order. Completed files are recorded in <code>schema_migrations</code>.</p><form method="post"><input type="hidden" name="action" value="run_sql"><button class="sf-install-btn" type="submit">Run SQL Installer</button></form><?php if ($sqlResults): ?><table class="sf-install-table"><thead><tr><th>Key</th><th>Status</th><th>Detail</th></tr></thead><tbody><?php foreach ($sqlResults as $row): ?><tr><td><strong><?= sf_install_h($row['key'] ?? '') ?></strong><small> <?= sf_install_h($row['label'] ?? '') ?></small></td><td><?php $s=$row['status']??''; echo $s==='applied'?'<span class="sf-ok">Applied</span>':($s==='skipped'?'<span class="sf-warn">Skipped</span>':'<span class="sf-bad">'.sf_install_h($s).'</span>'); ?></td><td><?= sf_install_h($row['detail'] ?? '') ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></section>
  <?php elseif ($step === 'admin'): ?>
    <section class="sf-install-card"><h2>Step 4 — Site + first admin</h2><form method="post"><input type="hidden" name="action" value="finish"><div class="sf-install-grid"><label class="sf-install-field">Site Name<input name="site_name" value="Stonefellow" required></label><label class="sf-install-field">Site Tagline<input name="site_tagline" value="Watch the show. Stream the music. Wear the story."></label><label class="sf-install-field">Public Base URL<input name="base_url" value="<?= sf_install_h($baseUrl) ?>"></label><label class="sf-install-field">Support Email<input type="email" name="support_email" value="support@stonefellow.tv"></label><label class="sf-install-field">Admin Name<input name="name" required></label><label class="sf-install-field">Admin Email<input type="email" name="email" required></label><label class="sf-install-field">Admin Password<input type="password" name="password" required minlength="8"></label><label class="sf-install-field">Confirm Password<input type="password" name="password_confirm" required minlength="8"></label></div><p><button class="sf-install-btn" type="submit" onclick="return confirm('Finish install and lock the installer?')">Finish Install + Launch Admin</button></p></form></section>
  <?php else: ?>
    <section class="sf-install-card"><h2>Launch ready</h2><p>Open the admin dashboard to continue setup.</p><p><a class="sf-install-btn" href="admin/index.php">Open Admin</a></p></section>
  <?php endif; ?>
</main>
</body>
</html>
