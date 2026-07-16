<?php

declare(strict_types=1);

$standaloneConfigPath = __DIR__ . '/config/standalone-install.php';
$standaloneConfig = is_file($standaloneConfigPath) ? require $standaloneConfigPath : [];
$licenseBypassed = is_array($standaloneConfig) && !empty($standaloneConfig['enabled']);

if (isset($_GET['step'])) {
    $defaultStep = $licenseBypassed ? 'server' : 'license';
    $step = preg_replace('/[^a-z]/', '', strtolower((string)$_GET['step'])) ?: $defaultStep;
    if ($licenseBypassed && $step === 'license') {
        $step = 'server';
    }
    header('Location: setup/index.php?step=' . rawurlencode($step));
    exit;
}

$locked = is_file(__DIR__ . '/storage/install.lock');
$setupUrl = $locked ? 'admin/index.php' : 'setup/index.php?step=' . ($licenseBypassed ? 'server' : 'license');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="description" content="Install the Likenessing streaming and membership platform.">
    <title>Likenessing Platform Installer</title>
    <style>
        :root{--black:#050505;--panel:#0d0d0c;--gold:#e0ad3d;--gold2:#c6902f;--cream:#f1eadb;--muted:#b9b4aa;--line:rgba(224,173,61,.30);--soft:rgba(255,255,255,.10);--shadow:0 28px 80px rgba(0,0,0,.5)}*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--black);color:#fff;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;line-height:1.6}.shell{width:min(1440px,calc(100% - 42px));margin:0 auto}.top{min-height:100vh;background:linear-gradient(90deg,#050505 0%,rgba(5,5,5,.95) 32%,rgba(5,5,5,.25) 68%,rgba(5,5,5,.08) 100%),url('likenessing-asset.php?name=hero&v=20260716') center/cover no-repeat;display:flex;flex-direction:column}.nav{min-height:92px;display:flex;align-items:center;gap:28px;border-bottom:1px solid var(--soft)}.brand img{display:block;width:220px;max-height:70px;object-fit:contain;object-position:left center}.nav-note{margin-left:auto;color:var(--muted);font-size:.78rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.button{display:inline-flex;align-items:center;justify-content:center;min-height:48px;padding:12px 22px;border:0;border-radius:5px;background:linear-gradient(180deg,#efc65e,var(--gold2));color:#090806;text-decoration:none;font-size:.82rem;font-weight:900;letter-spacing:.04em;text-transform:uppercase}.button.secondary{background:rgba(0,0,0,.38);border:1px solid var(--line);color:#fff}.hero{flex:1;display:flex;align-items:center;padding:70px 0 90px}.hero-copy{max-width:720px}.eyebrow{color:var(--gold);font-size:.78rem;font-weight:900;letter-spacing:.11em;text-transform:uppercase}.hero h1{font-family:Georgia,serif;font-size:clamp(3.8rem,8vw,8.5rem);font-weight:500;letter-spacing:-.07em;line-height:.86;margin:20px 0 24px}.hero h1 strong{color:var(--gold);font-weight:500}.hero p{max-width:660px;color:#fff;font-size:1.05rem}.actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:28px}.proof{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:52px}.proof article{padding:18px;border:1px solid var(--soft);background:rgba(0,0,0,.42);backdrop-filter:blur(8px)}.proof strong{display:block;color:var(--gold);font-size:.82rem;text-transform:uppercase}.proof span{display:block;color:#fff;font-size:.8rem;margin-top:5px}.section{padding:76px 0}.section-head{text-align:center;max-width:760px;margin:0 auto 38px}.section-head .eyebrow{display:block}.section-head h2{font-family:Georgia,serif;font-size:clamp(2.4rem,5vw,4.6rem);font-weight:500;letter-spacing:-.045em;line-height:1;margin:12px 0 16px}.section-head p{color:var(--muted)}.steps{display:grid;grid-template-columns:repeat(5,1fr);gap:12px}.step{padding:26px 22px;border:1px solid var(--soft);background:var(--panel);min-height:210px}.step b{display:grid;place-items:center;width:38px;height:38px;border-radius:999px;background:linear-gradient(180deg,#efc65e,var(--gold2));color:#090806}.step h3{font-size:1rem;margin:26px 0 10px}.step p{color:var(--muted);font-size:.86rem;margin:0}.mode{border-top:1px solid var(--line);border-bottom:1px solid var(--line);background:#0a0a09}.mode-grid{display:grid;grid-template-columns:.9fr 1.1fr;gap:50px;align-items:center;padding:62px 0}.mode h2{font-family:Georgia,serif;font-size:clamp(2.2rem,4vw,4rem);font-weight:500;line-height:1.05;margin:12px 0 18px}.mode p{color:var(--muted)}.mode-card{padding:28px;border:1px solid var(--line);background:rgba(224,173,61,.05)}.mode-card strong{display:block;color:var(--gold);margin-bottom:8px}.mode-card code{color:#fff}.cta{text-align:center;padding:74px 20px}.cta h2{font-family:Georgia,serif;font-size:clamp(2.4rem,5vw,4.6rem);font-weight:500;margin:0 0 14px}.cta p{color:var(--muted);margin:0 0 25px}.footer{border-top:1px solid var(--soft);padding:24px 0;color:#81796d;font-size:.78rem;display:flex;justify-content:space-between;gap:20px}.status{color:#afffda}@media(max-width:980px){.proof,.steps{grid-template-columns:1fr 1fr}.mode-grid{grid-template-columns:1fr}.nav-note{display:none}}@media(max-width:620px){.shell{width:min(100% - 28px,1440px)}.nav{min-height:78px}.brand img{width:175px}.nav .button{padding:10px 13px;font-size:.72rem}.hero h1{font-size:4rem}.proof,.steps{grid-template-columns:1fr}.footer{display:block}.footer div+div{margin-top:8px}}
    </style>
</head>
<body>
<section class="top">
    <div class="shell">
        <nav class="nav">
            <a class="brand" href="index.php"><img src="likenessing-asset.php?name=logo&amp;v=20260716" alt="Likenessing"></a>
            <span class="nav-note">Private platform installation</span>
            <a class="button" href="<?= htmlspecialchars($setupUrl, ENT_QUOTES, 'UTF-8') ?>"><?= $locked ? 'Open Admin' : 'Start Installation' ?></a>
        </nav>
        <div class="hero">
            <div class="hero-copy">
                <span class="eyebrow">Standalone show deployment</span>
                <h1>Install<br>Likeness<strong>ing</strong>.</h1>
                <p>Launch the complete Likenessing streaming, membership, episode, account, commerce, and administration platform through a guided private setup.</p>
                <div class="actions"><a class="button" href="<?= htmlspecialchars($setupUrl, ENT_QUOTES, 'UTF-8') ?>"><?= $locked ? 'Open Admin Dashboard' : 'Begin Setup' ?></a><a class="button secondary" href="#steps">Review Setup Steps</a></div>
                <div class="proof"><article><strong>Complete Platform</strong><span>The existing application and business logic remain intact.</span></article><article><strong>Private Setup</strong><span>Server and database details remain inside the installer.</span></article><article><strong>Automatic Lock</strong><span>The installer closes after the owner account is created.</span></article></div>
                <?php if ($locked): ?><p class="status">Platform installed and locked.</p><?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="section" id="steps">
    <div class="shell">
        <div class="section-head"><span class="eyebrow">Installation path</span><h2>Five secure setup steps.</h2><p>The standalone Likenessing deployment begins with server validation and does not require the product-license entry screen.</p></div>
        <div class="steps">
            <?php foreach ([
                ['Server','Validate PHP, required extensions, writable storage, and migration packages.'],
                ['Database','Connect an empty MySQL or MariaDB database using private credentials.'],
                ['Build','Apply the base schema and every versioned migration with checksums and locks.'],
                ['Owner','Create the first administrator and save the local platform configuration.'],
                ['Complete','Write the installation lock and continue inside the administrator console.'],
            ] as $i => $row): ?>
                <article class="step"><b><?= $i + 1 ?></b><h3><?= htmlspecialchars($row[0], ENT_QUOTES, 'UTF-8') ?></h3><p><?= htmlspecialchars($row[1], ENT_QUOTES, 'UTF-8') ?></p></article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="mode">
    <div class="shell mode-grid">
        <div><span class="eyebrow">Reversible configuration</span><h2>Licensing is preserved, not deleted.</h2><p>The licensing runtime, providers, admin tools, and receipt system remain in the platform. This show branch uses an isolated standalone adapter so installation can proceed without presenting the licensing page.</p></div>
        <div class="mode-card"><strong>Restore license-first installation</strong><span>Change <code>'enabled' =&gt; true</code> to <code>false</code> inside <code>config/standalone-install.php</code>. No licensing source files need to be restored.</span></div>
    </div>
</section>

<section class="cta"><h2>Ready to install Likenessing?</h2><p>Connect the server, build the database, and create the owner account.</p><a class="button" href="<?= htmlspecialchars($setupUrl, ENT_QUOTES, 'UTF-8') ?>"><?= $locked ? 'Open Admin Dashboard' : 'Start Installation' ?></a></section>
<footer class="footer shell"><div>Likenessing Productions · Standalone platform build</div><div>Existing installer, licensing, membership, playback, and commerce runtimes preserved.</div></footer>
</body>
</html>
