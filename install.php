<?php

declare(strict_types=1);

$product = is_file(__DIR__ . '/config/product-license.php') ? require __DIR__ . '/config/product-license.php' : [];
$installerBypass = !empty($product['installer_bypass']);
$locked = is_file(__DIR__ . '/storage/install.lock');
$setupPath = $installerBypass ? 'setup/standalone.php' : 'setup/index.php';

if (isset($_GET['step'])) {
    $defaultStep = $installerBypass ? 'server' : 'license';
    $step = preg_replace('/[^a-z]/', '', strtolower((string)$_GET['step'])) ?: $defaultStep;
    if ($installerBypass && $step === 'license') {
        $step = 'server';
    }
    header('Location: ' . $setupPath . '?step=' . rawurlencode($step));
    exit;
}

$launchUrl = $locked ? 'admin/index.php' : $setupPath;
$launchLabel = $locked ? 'Open Admin Dashboard' : 'Start Installation';
$imageBase = 'assets/images/installer/';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Install the complete DesertRio streaming, membership, video, music, and commerce platform.">
    <title>DesertRio Platform Installer</title>
    <style>
        :root{--cream:#f7f1e7;--paper:#fffdf9;--ink:#17130f;--muted:#74695e;--gold:#b68a43;--gold-dark:#8e672d;--line:#ded2c1;--night:#17140f;--shadow:0 28px 80px rgba(52,38,23,.15)}
        *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--cream);color:var(--ink);font-family:Arial,Helvetica,sans-serif;line-height:1.55}.shell{width:min(1480px,calc(100% - 42px));margin:0 auto}.top{min-height:760px;background:radial-gradient(circle at 82% 18%,rgba(182,138,67,.26),transparent 34%),linear-gradient(115deg,#fffdf9 0 50%,#f1e5d3 100%);overflow:hidden}.nav{height:90px;display:flex;align-items:center;gap:30px}.brand{display:flex;align-items:center;gap:13px;color:var(--ink);text-decoration:none}.brand-mark{display:grid;place-items:center;width:46px;height:46px;border-radius:50%;background:var(--gold);color:#fff;font-family:Georgia,serif;font-weight:700}.brand-name{font-family:Georgia,'Times New Roman',serif;font-size:1.25rem}.nav-links{display:flex;gap:28px;margin-left:auto}.nav-links a{color:var(--muted);text-decoration:none;font-size:.79rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase}.btn{display:inline-flex;align-items:center;justify-content:center;border-radius:999px;padding:14px 24px;background:var(--gold);color:#fff;text-decoration:none;font-weight:800;border:1px solid var(--gold)}.btn:hover{background:var(--gold-dark)}.btn.secondary{background:transparent;color:var(--ink);border-color:rgba(23,19,15,.22)}.hero{display:grid;grid-template-columns:minmax(420px,.82fr) minmax(560px,1.18fr);gap:62px;align-items:center;padding:60px 0 90px}.eyebrow{display:inline-flex;padding:8px 13px;border:1px solid rgba(182,138,67,.3);border-radius:999px;color:var(--gold-dark);font-size:.73rem;font-weight:900;letter-spacing:.1em;text-transform:uppercase;background:rgba(255,255,255,.58)}h1,h2{font-family:Georgia,'Times New Roman',serif;font-weight:500;letter-spacing:-.045em}.hero h1{font-size:clamp(3.8rem,6vw,7rem);line-height:.92;margin:22px 0 26px}.hero p{max-width:650px;color:var(--muted);font-size:1.04rem}.actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:30px}.proof{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:48px}.proof article{padding:17px;border-top:1px solid var(--line)}.proof strong{display:block;font-size:.86rem}.proof small{display:block;color:var(--muted);margin-top:5px}.art{position:relative;min-height:570px}.dashboard{position:absolute;inset:0 12% 7% 0;border-radius:20px;overflow:hidden;box-shadow:var(--shadow);background:#111}.dashboard img,.float img{width:100%;height:100%;object-fit:cover;display:block}.float{position:absolute;border-radius:16px;overflow:hidden;border:5px solid rgba(255,253,249,.88);box-shadow:0 20px 50px rgba(52,38,23,.2)}.float.one{right:0;top:7%;width:23%;height:31%}.float.two{right:0;top:43%;width:25%;height:32%}.float.three{left:50%;bottom:0;width:24%;height:25%}.section{padding:76px 0;background:var(--paper)}.section.alt{background:#efe4d3}.head{max-width:780px;margin-bottom:38px}.head h2{font-size:clamp(2.4rem,4vw,4.4rem);line-height:1;margin:0 0 14px}.head p{color:var(--muted);margin:0}.features{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}.card{background:#fff;border:1px solid var(--line);border-radius:18px;padding:24px;box-shadow:0 15px 35px rgba(52,38,23,.07)}.card span{color:var(--gold-dark);font-size:.74rem;font-weight:900;letter-spacing:.09em;text-transform:uppercase}.card h3{font-family:Georgia,serif;font-size:1.45rem;font-weight:500;margin:12px 0 9px}.card p{color:var(--muted);font-size:.88rem;margin:0}.steps{display:grid;grid-template-columns:repeat(5,1fr);gap:14px}.step{padding:24px;border:1px solid rgba(23,19,15,.12);border-radius:18px;background:rgba(255,255,255,.62)}.step b{display:grid;place-items:center;width:36px;height:36px;border-radius:50%;background:var(--gold);color:#fff}.step h3{font-family:Georgia,serif;font-weight:500;font-size:1.3rem}.step p{color:var(--muted);font-size:.86rem}.bottom{padding:70px 0;background:var(--night);color:#fff;text-align:center}.bottom h2{font-size:clamp(2.5rem,4vw,4.8rem);margin:0 0 14px}.bottom p{color:#d5ccbf}.status{display:inline-flex;margin-top:16px;padding:8px 13px;border-radius:999px;background:rgba(114,189,136,.15);color:#b9efc8;font-size:.75rem;font-weight:900}.footer{display:flex;justify-content:space-between;gap:20px;padding:28px 0;color:var(--muted);font-size:.78rem}@media(max-width:1100px){.hero{grid-template-columns:1fr}.art{min-height:520px}.features{grid-template-columns:repeat(2,1fr)}.steps{grid-template-columns:repeat(3,1fr)}}@media(max-width:680px){.nav-links{display:none}.hero h1{font-size:4rem}.proof,.features,.steps{grid-template-columns:1fr}.art{min-height:390px}.float{display:none}.dashboard{inset:0}.footer{display:block}.footer div+div{margin-top:8px}}
    </style>
</head>
<body>
<!-- Licensed platform launcher -->
<!-- Server diagnostics are not displayed publicly -->
<section class="top" id="top">
    <div class="shell">
        <nav class="nav">
            <a class="brand" href="#top"><span class="brand-mark">DR</span><span class="brand-name">DesertRio</span></a>
            <div class="nav-links"><a href="#platform">Platform</a><a href="#steps">Setup</a><a href="#launch">Launch</a></div>
            <a class="btn" href="<?= htmlspecialchars($launchUrl, ENT_QUOTES) ?>"><?= htmlspecialchars($launchLabel, ENT_QUOTES) ?></a>
        </nav>
        <div class="hero">
            <div>
                <span class="eyebrow">Standalone show platform</span>
                <h1>Install.<br>Build.<br>Stream.</h1>
                <p>Deploy the complete DesertRio video, membership, music, merchandise, account, and administration platform from one self-contained script package.</p>
                <div class="actions"><a class="btn" href="<?= htmlspecialchars($launchUrl, ENT_QUOTES) ?>"><?= htmlspecialchars($launchLabel, ENT_QUOTES) ?></a><a class="btn secondary" href="#platform">Explore the platform</a></div>
                <div class="proof"><article><strong>Complete codebase</strong><small>No prior Stonefellow installation required.</small></article><article><strong>Guided setup</strong><small>Server, database, schema, and owner account.</small></article><article><strong>Licensing preserved</strong><small>Temporarily bypassed, not removed.</small></article></div>
                <?= $locked ? '<span class="status">DesertRio is installed</span>' : '' ?>
            </div>
            <div class="art" aria-label="Platform previews">
                <div class="dashboard"><img src="<?= htmlspecialchars($imageBase . 'vp3-dashboard.png', ENT_QUOTES) ?>" alt="Platform dashboard preview"></div>
                <div class="float one"><img src="<?= htmlspecialchars($imageBase . 'vp3-music-player.png', ENT_QUOTES) ?>" alt="Music player preview"></div>
                <div class="float two"><img src="<?= htmlspecialchars($imageBase . 'vp3-memberships.png', ENT_QUOTES) ?>" alt="Membership preview"></div>
                <div class="float three"><img src="<?= htmlspecialchars($imageBase . 'vp3-merch.png', ENT_QUOTES) ?>" alt="Merchandise preview"></div>
            </div>
        </div>
    </div>
</section>

<section class="section" id="platform"><div class="shell"><div class="head"><span class="eyebrow">Complete application</span><h2>Everything needed to run the show.</h2><p>The DesertRio branch includes the same application logic as the main platform with its own public-facing layout, artwork, CSS, and show identity.</p></div><div class="features">
<?php foreach ([['Video','Series, episodes, protected playback, progress tracking, and member access.'],['Membership','Registration, sign-in, subscriptions, entitlements, accounts, and libraries.'],['Commerce','Merchandise, product pages, cart, checkout, orders, inventory, and receipts.'],['Administration','Catalog management, users, payments, media, analytics, operations, and publishing.']] as $feature): ?>
<article class="card"><span>Included</span><h3><?= htmlspecialchars($feature[0], ENT_QUOTES) ?></h3><p><?= htmlspecialchars($feature[1], ENT_QUOTES) ?></p></article>
<?php endforeach; ?>
</div></div></section>

<section class="section alt" id="steps"><div class="shell"><div class="head"><span class="eyebrow">Five-step installation</span><h2>From upload to owner account.</h2><p>The temporary DesertRio installer bypass starts directly with server validation. The original licensing files and licensed wizard remain in the package.</p></div><div class="steps">
<?php foreach ([['Server','Confirm PHP, required extensions, writable folders, and migration files.'],['Database','Connect a new MySQL or MariaDB database.'],['Build','Apply the base schema and all versioned migrations safely.'],['Owner','Create the first administrator and save site configuration.'],['Complete','Write the install lock and continue into the admin dashboard.']] as $index=>$row): ?>
<article class="step"><b><?= $index + 1 ?></b><h3><?= htmlspecialchars($row[0], ENT_QUOTES) ?></h3><p><?= htmlspecialchars($row[1], ENT_QUOTES) ?></p></article>
<?php endforeach; ?>
</div></div></section>

<section class="bottom" id="launch"><div class="shell"><h2><?= $locked ? 'DesertRio is ready.' : 'Ready to install DesertRio?' ?></h2><p><?= $locked ? 'Continue managing the platform from the administrator dashboard.' : 'Upload the full branch package, open this installer, and complete the guided setup.' ?></p><div class="actions" style="justify-content:center"><a class="btn" href="<?= htmlspecialchars($launchUrl, ENT_QUOTES) ?>"><?= htmlspecialchars($launchLabel, ENT_QUOTES) ?></a></div></div></section>
<footer><div class="shell footer"><div><strong>DesertRio Platform</strong><br>Powered by the VP3 application core.</div><div>Standalone installation · Original licensing runtime preserved.</div></div></footer>
</body>
</html>
