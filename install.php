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
$installerImageBase = '/images/installer/';
$installerImages = [
  'dashboard' => $installerImageBase . 'vp3-dashboard.png',
  'devices' => $installerImageBase . 'vp3-devices.png',
  'readiness' => $installerImageBase . 'vp3-readiness.png',
  'music' => $installerImageBase . 'vp3-music-player.png',
  'memberships' => $installerImageBase . 'vp3-memberships.png',
  'merch' => $installerImageBase . 'vp3-merch.png',
];
$steps = [
  'server' => ['label' => 'Check Server', 'eyebrow' => 'Step 01', 'icon' => '▦'],
  'db' => ['label' => 'Connect Database', 'eyebrow' => 'Step 02', 'icon' => '◉'],
  'sql' => ['label' => 'Build Platform', 'eyebrow' => 'Step 03', 'icon' => '◆'],
  'admin' => ['label' => 'Create Owner Account', 'eyebrow' => 'Step 04', 'icon' => '●'],
  'done' => ['label' => 'Launch', 'eyebrow' => 'Step 05', 'icon' => '⚑'],
];
$passedChecks = count(array_filter($checks, static fn($check) => !empty($check['ok'])));
$totalChecks = max(count($checks), 1);
$sqlApplied = count(array_filter($sqlResults, static fn($row) => ($row['status'] ?? '') === 'applied'));
$sqlSkipped = count(array_filter($sqlResults, static fn($row) => ($row['status'] ?? '') === 'skipped'));
$sqlFailed = count(array_filter($sqlResults, static fn($row) => in_array(($row['status'] ?? ''), ['failed','missing'], true)));
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>VP3 Media Group Platform Installer</title>
  <link rel="stylesheet" href="assets/css/stonefellow.css">
  <style>
    :root{--ink:#f8fbff;--muted:#9aa7c7;--purple:#8f42ff;--blue:#1c8dff;--green:#35d987;--paper:#f5f7fb}*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:linear-gradient(180deg,#050711 0%,#081022 42%,#f6f7fb 42%,#f6f7fb 100%);color:#111827;font-family:Inter,Arial,sans-serif}.vp3-installer{overflow:hidden}.vp3-hero{position:relative;background:radial-gradient(circle at 15% 18%,rgba(143,66,255,.35),transparent 34%),radial-gradient(circle at 88% 12%,rgba(28,141,255,.3),transparent 32%),linear-gradient(135deg,#050711,#081023 58%,#0b1530);color:var(--ink);padding:22px 22px 72px}.vp3-shell,.vp3-section,.vp3-bottom-cta,.vp3-footer{max-width:1220px;margin:0 auto}.vp3-nav{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:10px 0 34px}.vp3-brand{display:flex;align-items:center;gap:12px;font-weight:900;color:#fff;text-decoration:none}.vp3-logo{font-size:2rem;letter-spacing:-.09em;line-height:1;background:linear-gradient(135deg,#fff 0%,#fff 38%,#9747ff 39%,#238cff 100%);-webkit-background-clip:text;background-clip:text;color:transparent}.vp3-links{display:flex;gap:24px;font-size:.86rem}.vp3-links a{color:#dce6ff;text-decoration:none}.vp3-cta,.vp3-btn{border:0;border-radius:999px;background:linear-gradient(135deg,var(--blue),var(--purple));color:#fff;text-decoration:none;font-weight:900;padding:13px 18px;display:inline-flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 14px 34px rgba(73,75,255,.34);cursor:pointer}.vp3-btn.secondary{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.24);box-shadow:none}.vp3-hero-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(420px,.95fr);gap:42px;align-items:center}.vp3-kicker{display:inline-flex;border:1px solid rgba(255,255,255,.18);background:rgba(255,255,255,.06);padding:8px 12px;border-radius:999px;color:#dfe8ff;font-weight:800;font-size:.8rem}.vp3-hero h1{font-size:clamp(3rem,7vw,6.7rem);line-height:.9;margin:18px 0;letter-spacing:-.08em}.vp3-gradient{background:linear-gradient(135deg,#58a7ff,#a44cff);-webkit-background-clip:text;background-clip:text;color:transparent}.vp3-hero p{color:#c8d2ed;font-size:1.05rem;line-height:1.7;max-width:650px}.vp3-actions{display:flex;gap:14px;flex-wrap:wrap;margin:28px 0}.vp3-proof{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;max-width:650px}.vp3-proof div{border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.045);border-radius:18px;padding:14px}.vp3-proof strong{display:block;color:#fff;font-size:.92rem}.vp3-proof small{color:#aebad7}.vp3-media-stage{position:relative;min-height:540px}.vp3-asset{display:block;width:100%;height:auto;border-radius:28px;filter:drop-shadow(0 32px 70px rgba(0,0,0,.42))}.vp3-asset-card{position:absolute;background:rgba(5,10,24,.86);border:1px solid rgba(255,255,255,.16);border-radius:22px;padding:10px;backdrop-filter:blur(10px);box-shadow:0 22px 60px rgba(0,0,0,.35)}.vp3-asset-card img{display:block;width:100%;height:auto;border-radius:16px}.vp3-asset-card.music{width:34%;left:-2%;bottom:2%}.vp3-asset-card.members{width:34%;right:-2%;bottom:14%}.vp3-asset-card.merch{width:34%;right:6%;top:2%}.vp3-fallback-dashboard{display:none;min-height:440px;border:1px solid rgba(125,154,255,.36);border-radius:28px;background:linear-gradient(145deg,rgba(14,20,36,.94),rgba(5,8,18,.96));box-shadow:0 32px 90px rgba(0,0,0,.45);padding:24px;color:#fff}.vp3-section{padding:54px 22px}.vp3-section-head{text-align:center;max-width:720px;margin:0 auto 28px}.vp3-section-head h2{font-size:clamp(2rem,4vw,3.2rem);line-height:1;margin:0 0 10px;letter-spacing:-.05em}.vp3-section-head p{color:#667085;line-height:1.7}.vp3-feature-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:14px}.vp3-feature{background:#fff;border:1px solid #e4e8f0;border-radius:22px;padding:22px;text-align:center;box-shadow:0 20px 50px rgba(15,23,42,.06)}.vp3-feature i{display:inline-grid;place-items:center;width:48px;height:48px;border-radius:15px;background:linear-gradient(135deg,var(--purple),var(--blue));color:#fff;font-style:normal;font-weight:900;margin-bottom:12px}.vp3-feature h3{font-size:1rem;margin:0 0 8px}.vp3-feature p{font-size:.86rem;color:#6b7280;line-height:1.55;margin:0}.vp3-wide-visual{margin-top:28px;background:#fff;border:1px solid #e4e8f0;border-radius:28px;padding:16px;box-shadow:0 20px 60px rgba(15,23,42,.08)}.vp3-wide-visual img{width:100%;height:auto;border-radius:20px;display:block}.vp3-launch-path{display:grid;grid-template-columns:repeat(5,1fr);gap:18px}.vp3-step{background:#fff;border:1px solid #e4e8f0;border-radius:22px;padding:22px;box-shadow:0 20px 50px rgba(15,23,42,.06);text-decoration:none}.vp3-step b{display:inline-grid;place-items:center;width:34px;height:34px;border-radius:999px;background:linear-gradient(135deg,var(--blue),var(--purple));color:#fff;margin-bottom:14px}.vp3-step span{display:block;color:#667085;font-size:.77rem;font-weight:900;text-transform:uppercase;letter-spacing:.08em}.vp3-step h3{margin:8px 0;color:#111827}.vp3-step p{color:#6b7280;font-size:.88rem;line-height:1.55;margin:0}.vp3-install-panel{background:linear-gradient(135deg,#071025,#0d1630);border-radius:30px;padding:26px;color:#fff;border:1px solid rgba(54,91,255,.24);box-shadow:0 24px 80px rgba(5,12,30,.25)}.vp3-install-layout{display:grid;grid-template-columns:280px minmax(0,1fr);gap:26px}.vp3-score-card{background:radial-gradient(circle at 30% 20%,rgba(143,66,255,.3),transparent 48%),rgba(255,255,255,.045);border:1px solid rgba(255,255,255,.1);border-radius:24px;padding:24px;text-align:center}.vp3-score-card img{width:100%;border-radius:18px;margin-top:18px}.vp3-score-ring{width:170px;height:170px;border-radius:50%;margin:0 auto 16px;background:conic-gradient(var(--green) calc(var(--score)*1%),rgba(255,255,255,.12) 0);display:grid;place-items:center;box-shadow:0 0 48px rgba(53,217,135,.2)}.vp3-score-ring span{width:124px;height:124px;border-radius:50%;background:#0b1122;display:grid;place-items:center;font-size:2.4rem;font-weight:900}.vp3-install-card{background:#fff;color:#111827;border-radius:24px;padding:24px}.vp3-install-card.dark{background:rgba(255,255,255,.05);color:#fff;border:1px solid rgba(255,255,255,.12)}.vp3-install-card h2{margin:0 0 8px;font-size:1.75rem;letter-spacing:-.04em}.vp3-install-card p{color:#657083;line-height:1.65}.vp3-install-card.dark p{color:#b7c2dc}.vp3-install-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.vp3-field{display:flex;flex-direction:column;gap:8px;font-weight:900;color:#263143}.vp3-field input{border:1px solid #d8dfeb;border-radius:14px;padding:13px 14px;font:inherit;background:#f8fafc}.vp3-table-wrap{overflow:auto;border:1px solid #e5e7ef;border-radius:18px;margin-top:18px}.vp3-table{width:100%;border-collapse:collapse;background:#fff}.vp3-table th,.vp3-table td{padding:13px 14px;border-bottom:1px solid #eef1f6;text-align:left;vertical-align:top}.vp3-table th{font-size:.78rem;text-transform:uppercase;letter-spacing:.08em;color:#667085;background:#f9fafb}.vp3-ok{color:#08945c;font-weight:900}.vp3-bad{color:#c52222;font-weight:900}.vp3-warn{color:#a16a00;font-weight:900}.vp3-alert{max-width:1220px;margin:12px auto;border-radius:16px;padding:14px 16px;font-weight:800}.vp3-alert-success{background:#e8f8ef;color:#08613a}.vp3-alert-error{background:#fdecec;color:#a31313}.vp3-alert-warning{background:#fff4d5;color:#7a5200}.vp3-locked{display:grid;grid-template-columns:1fr auto;gap:18px;align-items:center}.vp3-bottom-cta{margin:0 auto 54px;background:radial-gradient(circle at 90% 30%,rgba(143,66,255,.28),transparent 40%),linear-gradient(135deg,#070b19,#111a3a);color:#fff;border-radius:30px;padding:44px 24px;text-align:center}.vp3-bottom-cta h2{font-size:clamp(2rem,4vw,4rem);margin:0 0 10px;letter-spacing:-.06em}.vp3-footer{padding:0 22px 40px;color:#667085;display:flex;justify-content:space-between;gap:18px;font-size:.86rem}@media(max-width:1000px){.vp3-hero-grid,.vp3-install-layout{grid-template-columns:1fr}.vp3-feature-grid{grid-template-columns:repeat(2,1fr)}.vp3-launch-path{grid-template-columns:1fr 1fr}.vp3-links{display:none}.vp3-media-stage{min-height:auto}.vp3-asset-card{position:relative;width:100%!important;left:auto!important;right:auto!important;top:auto!important;bottom:auto!important;margin-top:14px}}@media(max-width:640px){.vp3-hero{padding:18px 16px 48px}.vp3-proof,.vp3-feature-grid,.vp3-launch-path,.vp3-install-grid,.vp3-locked{grid-template-columns:1fr}.vp3-hero h1{font-size:3.2rem}.vp3-section{padding:40px 16px}.vp3-footer{display:block}}
  </style>
</head>
<body>
<main class="vp3-installer">
  <section class="vp3-hero" id="top">
    <div class="vp3-shell">
      <nav class="vp3-nav" aria-label="Installer navigation">
        <a class="vp3-brand" href="install.php"><span class="vp3-logo">VP3</span><span>VP3 Media Group</span></a>
        <div class="vp3-links"><a href="#platform">Platform</a><a href="#features">Features</a><a href="#launch-path">Launch Path</a><a href="#installer">Installer</a></div>
        <a class="vp3-cta" href="#installer">🚀 Start Installation</a>
      </nav>
      <div class="vp3-hero-grid">
        <div>
          <span class="vp3-kicker">◆ Guided platform launcher</span>
          <h1>Launch Your Media Platform in <span class="vp3-gradient">Minutes</span></h1>
          <p>VP3 Media Group gives creators, artists, educators, ministries, and entertainment brands a complete streaming, music, membership, merch, and admin platform with a guided self-hosted installer.</p>
          <div class="vp3-actions"><a class="vp3-btn" href="#installer">🚀 Start Installation</a><a class="vp3-btn secondary" href="#features">▶ See What’s Included</a></div>
          <div class="vp3-proof"><div><strong>Fast & Automated</strong><small>Guided launch path</small></div><div><strong>Secure & Reliable</strong><small>Server readiness checks</small></div><div><strong>All-in-One Platform</strong><small>Streaming, store, and admin</small></div></div>
        </div>
        <div class="vp3-media-stage" id="platform" aria-label="VP3 platform image preview">
          <img class="vp3-asset" src="<?= sf_install_h($installerImages['dashboard']) ?>" alt="VP3 Media Group dashboard preview" loading="eager" onerror="this.style.display='none';this.nextElementSibling.style.display='block';">
          <div class="vp3-fallback-dashboard"><h2>VP3 Platform Preview</h2><p>Streaming, music, memberships, merch, analytics, users, settings, and guided installer readiness in one command center.</p></div>
          <div class="vp3-asset-card music"><img src="<?= sf_install_h($installerImages['music']) ?>" alt="VP3 music player preview" loading="lazy"></div>
          <div class="vp3-asset-card members"><img src="<?= sf_install_h($installerImages['memberships']) ?>" alt="VP3 membership analytics preview" loading="lazy"></div>
          <div class="vp3-asset-card merch"><img src="<?= sf_install_h($installerImages['merch']) ?>" alt="VP3 merch store preview" loading="lazy"></div>
        </div>
      </div>
    </div>
  </section>

  <?php foreach (sf_install_flashes() as $msg): ?><div class="vp3-alert vp3-alert-<?= sf_install_h($msg['type'] ?? 'warning') ?>"><?= sf_install_h($msg['message'] ?? '') ?></div><?php endforeach; ?>

  <section class="vp3-section" id="features">
    <div class="vp3-section-head"><h2>What the Platform Includes</h2><p>One installation gives you the core foundation for a modern media business: content, community, commerce, admin, and launch operations.</p></div>
    <div class="vp3-feature-grid">
      <article class="vp3-feature"><i>▶</i><h3>Streaming</h3><p>Upload, organize, and monetize video content for public or member-only access.</p></article>
      <article class="vp3-feature"><i>♫</i><h3>Music</h3><p>Manage songs, albums, public tracks, and full member-player experiences.</p></article>
      <article class="vp3-feature"><i>👥</i><h3>Memberships</h3><p>Run subscriptions, gated content, account access, and member operations.</p></article>
      <article class="vp3-feature"><i>▣</i><h3>Merch</h3><p>Sell products, track orders, manage carts, and support drops or bundles.</p></article>
      <article class="vp3-feature"><i>▥</i><h3>Admin Dashboard</h3><p>Operate content, users, settings, QA, migrations, and platform health.</p></article>
      <article class="vp3-feature"><i>✦</i><h3>Themes & Branding</h3><p>Control public visuals, theme images, landing pages, and brand presentation.</p></article>
    </div>
    <div class="vp3-wide-visual"><img src="<?= sf_install_h($installerImages['devices']) ?>" alt="VP3 Media Group device mockups" loading="lazy"></div>
  </section>

  <section class="vp3-section" id="launch-path">
    <div class="vp3-section-head"><h2>Launch Path: 5 Simple Steps</h2><p>The installer walks you from environment checks to a locked, launch-ready admin account.</p></div>
    <div class="vp3-launch-path">
      <?php foreach ($steps as $key => $meta): ?><a class="vp3-step" href="install.php?step=<?= sf_install_h($key) ?>#installer"><b><?= sf_install_h($meta['icon']) ?></b><span><?= sf_install_h($meta['eyebrow']) ?></span><h3><?= sf_install_h($meta['label']) ?></h3><p><?= $key === 'server' ? 'Verify PHP, extensions, files, and writable folders.' : ($key === 'db' ? 'Connect and validate your MySQL database.' : ($key === 'sql' ? 'Install the schema and platform migrations.' : ($key === 'admin' ? 'Create the first owner/admin account.' : 'Open admin and begin building.'))) ?></p></a><?php endforeach; ?>
    </div>
  </section>

  <section class="vp3-section" id="installer">
    <div class="vp3-install-panel">
      <div class="vp3-install-layout">
        <aside class="vp3-score-card" style="--score:<?= (int)$score ?>">
          <div class="vp3-score-ring"><span><?= (int)$score ?>%</span></div>
          <h2>Launch Readiness</h2>
          <p><?= (int)$passedChecks ?> of <?= (int)$totalChecks ?> checks passing. <?= $locked ? 'Installer locked for security.' : 'Installer open and ready.' ?></p>
          <img src="<?= sf_install_h($installerImages['readiness']) ?>" alt="VP3 readiness checklist preview" loading="lazy">
          <div class="vp3-actions" style="justify-content:center"><a class="vp3-btn secondary" href="install.php?step=server#installer">Refresh Checks</a></div>
        </aside>
        <section>
          <nav class="vp3-launch-path" style="grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:18px">
            <?php foreach ($steps as $key => $meta): ?><a class="vp3-step" style="padding:13px;border-radius:16px;box-shadow:none;background:<?= $step === $key ? 'linear-gradient(135deg,#216dff,#8c42ff)' : 'rgba(255,255,255,.08)' ?>;color:#fff;border-color:rgba(255,255,255,.14)" href="install.php?step=<?= sf_install_h($key) ?>#installer"><span style="color:rgba(255,255,255,.7)"><?= sf_install_h($meta['eyebrow']) ?></span><strong><?= sf_install_h($meta['label']) ?></strong></a><?php endforeach; ?>
          </nav>

          <?php if ($locked): ?>
            <section class="vp3-install-card dark vp3-locked"><div><h2>Installer locked for security.</h2><p>Your platform has already been installed. To run the installer again, manually remove <code>storage/install.lock</code> only after confirming you have a database backup.</p></div><div class="vp3-actions"><a class="vp3-btn" href="admin/index.php">Open Admin</a><a class="vp3-btn secondary" href="index.php">View Site</a></div></section>
          <?php elseif ($step === 'server'): ?>
            <section class="vp3-install-card"><h2>Check Server</h2><p>VP3 checks PHP, extensions, writable folders, upload storage, and required SQL files before you connect the database.</p><div class="vp3-table-wrap"><table class="vp3-table"><thead><tr><th>Check</th><th>Status</th><th>Detail</th></tr></thead><tbody><?php foreach ($checks as $check): ?><tr><td><strong><?= sf_install_h($check['label']) ?></strong></td><td><?= !empty($check['ok'])?'<span class="vp3-ok">Pass</span>':'<span class="vp3-bad">Review</span>' ?></td><td><?= sf_install_h($check['detail'] ?? '') ?></td></tr><?php endforeach; ?></tbody></table></div><div class="vp3-actions"><a class="vp3-btn" href="install.php?step=db#installer">Continue to Database</a></div></section>
          <?php elseif ($step === 'db'): ?>
            <section class="vp3-install-card"><h2>Connect Database</h2><p>Stonefellow uses your database to store members, episodes, songs, products, purchases, settings, themes, and admin content. Create an empty MySQL database and enter the credentials below.</p><form method="post"><input type="hidden" name="action" value="test_db"><div class="vp3-install-grid"><label class="vp3-field">Host<input name="db_host" value="<?= sf_install_h($db['host'] ?? 'localhost') ?>" required></label><label class="vp3-field">Port<input name="db_port" value="<?= sf_install_h($db['port'] ?? '3306') ?>"></label><label class="vp3-field">Database Name<input name="db_name" value="<?= sf_install_h($db['name'] ?? '') ?>" required></label><label class="vp3-field">Database User<input name="db_user" value="<?= sf_install_h($db['user'] ?? '') ?>" required></label><label class="vp3-field">Database Password<input type="password" name="db_pass" value="<?= sf_install_h($db['pass'] ?? '') ?>"></label></div><div class="vp3-actions"><button class="vp3-btn" type="submit">Test Connection</button><a class="vp3-btn secondary" href="install.php?step=server#installer">Back to Checks</a></div></form></section>
          <?php elseif ($step === 'sql'): ?>
            <section class="vp3-install-card"><h2>Build Platform Database</h2><p>This creates the base schema and applies all versioned migrations in order. Completed files are recorded in <code>schema_migrations</code>.</p><div class="vp3-proof" style="max-width:none;margin:18px 0"><div><strong><?= (int)$sqlApplied ?></strong><small>Applied</small></div><div><strong><?= (int)$sqlSkipped ?></strong><small>Skipped</small></div><div><strong><?= (int)$sqlFailed ?></strong><small>Needs review</small></div></div><form method="post"><input type="hidden" name="action" value="run_sql"><button class="vp3-btn" type="submit">Run SQL Installer</button></form><?php if ($sqlResults): ?><div class="vp3-table-wrap"><table class="vp3-table"><thead><tr><th>Key</th><th>Status</th><th>Detail</th></tr></thead><tbody><?php foreach ($sqlResults as $row): ?><tr><td><strong><?= sf_install_h($row['key'] ?? '') ?></strong><small> <?= sf_install_h($row['label'] ?? '') ?></small></td><td><?php $s=$row['status']??''; echo $s==='applied'?'<span class="vp3-ok">Applied</span>':($s==='skipped'?'<span class="vp3-warn">Skipped</span>':'<span class="vp3-bad">'.sf_install_h($s).'</span>'); ?></td><td><?= sf_install_h($row['detail'] ?? '') ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
          <?php elseif ($step === 'admin'): ?>
            <section class="vp3-install-card"><h2>Create Owner Account</h2><p>This account becomes the first admin and platform owner. You’ll use it to manage content, members, payments, merch, themes, and releases.</p><form method="post"><input type="hidden" name="action" value="finish"><div class="vp3-install-grid"><label class="vp3-field">Site Name<input name="site_name" value="VP3 Media Group" required></label><label class="vp3-field">Site Tagline<input name="site_tagline" value="Launch your media platform in minutes."></label><label class="vp3-field">Public Base URL<input name="base_url" value="<?= sf_install_h($baseUrl) ?>"></label><label class="vp3-field">Support Email<input type="email" name="support_email" value="support@vp3mediagroup.com"></label><label class="vp3-field">Admin Name<input name="name" required></label><label class="vp3-field">Admin Email<input type="email" name="email" required></label><label class="vp3-field">Admin Password<input type="password" name="password" required minlength="8"></label><label class="vp3-field">Confirm Password<input type="password" name="password_confirm" required minlength="8"></label></div><div class="vp3-actions"><button class="vp3-btn" type="submit" onclick="return confirm('Finish install and lock the installer?')">Finish Install + Launch Admin</button></div></form></section>
          <?php else: ?>
            <section class="vp3-install-card dark"><h2>Your VP3 platform is ready.</h2><p>Open the admin dashboard to continue setup, run QA, and begin building your media platform.</p><div class="vp3-actions"><a class="vp3-btn" href="admin/index.php">Open Admin Dashboard</a><a class="vp3-btn secondary" href="index.php">View Public Site</a><a class="vp3-btn secondary" href="admin/migration-checker.php">Run QA Check</a></div></section>
          <?php endif; ?>
        </section>
      </div>
    </div>
  </section>

  <section class="vp3-section">
    <div class="vp3-section-head"><h2>Why VP3 Media Group?</h2><p>Built for creators and operators who want ownership, monetization flexibility, and a full-stack media platform they can control.</p></div>
    <div class="vp3-feature-grid" style="grid-template-columns:repeat(4,1fr)"><article class="vp3-feature"><i>◎</i><h3>Own Your Brand</h3><p>No platform lock-in. Your content, your domain, your audience.</p></article><article class="vp3-feature"><i>$</i><h3>Monetize Your Way</h3><p>Memberships, merch, checkout, access gates, and sales tools.</p></article><article class="vp3-feature"><i>↗</i><h3>Built to Scale</h3><p>Start lean, then expand content, users, themes, and operations.</p></article><article class="vp3-feature"><i>✱</i><h3>All-in-One Power</h3><p>One platform foundation instead of scattered plugins and patches.</p></article></div>
  </section>

  <section class="vp3-bottom-cta"><h2>Your Platform. Your Audience. Your Future.</h2><p>Install VP3 Media Group today and launch your media platform in minutes.</p><div class="vp3-actions" style="justify-content:center"><a class="vp3-btn" href="#installer">🚀 Start Installation Now</a></div></section>
  <footer class="vp3-footer"><div><strong>VP3 Media Group</strong><br>Empowering creators and brands to own their media future.</div><div>Fast • Secure • All-in-One</div></footer>
</main>
</body>
</html>
