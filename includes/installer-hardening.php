<?php
// Installer-only output hardening for the VP3 installer page.
if (defined('SF_INSTALL_HARDENING_BUFFER')) {
    return;
}
define('SF_INSTALL_HARDENING_BUFFER', true);

function sfh_install_db_ready(): bool {
    $db = $_SESSION['sf_install_db'] ?? [];
    return is_array($db)
        && trim((string)($db['host'] ?? '')) !== ''
        && trim((string)($db['name'] ?? '')) !== ''
        && trim((string)($db['user'] ?? '')) !== '';
}
function sfh_install_sql_ready(): bool {
    $results = $_SESSION['sf_install_sql_results'] ?? [];
    if (!is_array($results) || $results === []) return false;
    foreach ($results as $row) {
        $status = is_array($row) ? (string)($row['status'] ?? '') : '';
        if (in_array($status, ['failed', 'missing'], true)) return false;
    }
    return true;
}
function sfh_install_checkline(string $label, string $class, string $text): string {
    return '<div class="vp3-checkline"><span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span><b class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</b></div>';
}
function sfh_install_db_checklist(): string {
    $dbReady = sfh_install_db_ready();
    $sqlReady = sfh_install_sql_ready();
    $rows = [
        ['Database Connection', $dbReady ? 'vp3-pass' : 'vp3-warn', $dbReady ? 'Pass' : 'Pending'],
        ['User Permissions', $sqlReady ? 'vp3-pass' : 'vp3-warn', $sqlReady ? 'Pass' : ($dbReady ? 'Run SQL' : 'Pending')],
        ['Character Set (utf8mb4)', $dbReady ? 'vp3-pass' : 'vp3-warn', $dbReady ? 'Ready' : 'Pending'],
        ['Collation', $dbReady ? 'vp3-pass' : 'vp3-warn', $dbReady ? 'Ready' : 'Pending'],
        ['Backup Capability', 'vp3-warn', 'Manual'],
    ];
    $html = '<div class="vp3-checklist"><h3>▣ Database Checklist</h3>';
    foreach ($rows as $row) $html .= sfh_install_checkline($row[0], $row[1], $row[2]);
    return $html . '</div>';
}
function sfh_install_launch_item(string $status, string $title, string $detail, ?string $href = null, ?string $linkLabel = null): string {
    $html = '<article class="vp3-launch-item"><span>' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</span><h3>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h3><p>' . htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') . '</p>';
    if ($href !== null && $href !== '') {
        $html .= '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($linkLabel ?? 'Open', ENT_QUOTES, 'UTF-8') . '</a>';
    }
    return $html . '</article>';
}
function sfh_install_launch_checklist(): string {
    $items = [
        ['Ready', 'Admin Dashboard', 'Open the admin dashboard and confirm the first owner account can sign in.', 'admin/index.php', 'Open Admin'],
        ['Run', 'Migration Checker', 'Verify base schema and all migrations after the installer finishes.', 'admin/migration-checker.php', 'Run QA Check'],
        ['Review', 'Site Settings', 'Confirm site name, public URL, support email, runtime toggles, and public configuration.', 'admin/settings.php', 'Open Settings'],
        ['Review', 'Media & Upload Folders', 'Confirm image, audio, video, and document upload directories remain writable after deploy.', null, null],
        ['Review', 'Payment Settings', 'Set the payment provider, credentials, checkout mode, and launch-safe payment configuration.', 'admin/settings.php', 'Review Settings'],
        ['Review', 'Email / SMTP', 'Confirm support email and outbound email delivery before launch announcements.', 'admin/settings.php', 'Review Email'],
        ['Manual', 'Backup Plan', 'Create a database and file backup before public traffic or future installer reruns.', null, null],
        ['Protect', 'Install Lock', 'Keep storage/install.lock in place. Remove it only after confirming a current backup.', null, null],
        ['Preserve', 'Installer Media', 'Future ZIP deploys must preserve /assets/images/installer/ so VP3 preview images remain visible.', null, null],
    ];
    $html = '<section class="vp3-install-card dark vp3-launch-card"><div class="vp3-launch-head"><span>Launch checklist</span><h2>Your VP3 platform is ready.</h2><p>Use this final pass to move from installed to launch-ready with confidence.</p></div><div class="vp3-actions"><a class="vp3-btn" href="admin/index.php">Open Admin Dashboard</a><a class="vp3-btn secondary" href="index.php">View Public Site</a><a class="vp3-btn secondary" href="admin/migration-checker.php">Run QA Check</a></div><div class="vp3-launch-grid">';
    foreach ($items as $item) {
        $html .= sfh_install_launch_item($item[0], $item[1], $item[2], $item[3], $item[4]);
    }
    return $html . '</div><p class="vp3-launch-note"><strong>Deploy note:</strong> after pulling the latest main ZIP, preserve server-only installer preview files in <code>/assets/images/installer/</code>.</p></section>';
}

ob_start(static function (string $html): string {
    if ($html === '' || strpos($html, 'VP3 Media Group Platform Installer') === false) return $html;
    $pattern = '#<div class="vp3-checklist"><h3>▣ Database Checklist</h3>.*?<div class="vp3-checkline"><span>Backup Capability</span><b class="vp3-pass">Pass</b></div></div>#s';
    $updated = preg_replace($pattern, sfh_install_db_checklist(), $html, 1);
    if (is_string($updated)) $html = $updated;

    $donePattern = '#<section class="vp3-install-card dark"><h2>Your VP3 platform is ready\.</h2>.*?<a class="vp3-btn secondary" href="admin/migration-checker\.php">Run QA Check</a></div></section>#s';
    $doneUpdated = preg_replace($donePattern, sfh_install_launch_checklist(), $html, 1);
    if (is_string($doneUpdated)) $html = $doneUpdated;

    if (strpos($html, 'sf-installer-hardening') === false) {
        $css = <<<'CSS'
        /* sf-installer-hardening */
        .vp3-warn { color: #fbbf24; font-weight: 900; }
        .vp3-image-missing { min-height: 150px; display: grid !important; place-items: center; background: linear-gradient(135deg, rgba(49,93,255,.14), rgba(139,61,255,.12)); border: 1px dashed rgba(255,255,255,.26); color: #f8fafc; text-align: center; }
        .vp3-image-missing::after { content: "Installer preview image missing. Preserve /assets/images/installer/ during deploy."; display: block; padding: 20px; font-size: .78rem; font-weight: 850; line-height: 1.35; }
        .vp3-dashboard-frame.vp3-image-missing::after { min-height: 330px; display: grid; place-items: center; }
        .vp3-floating-card.vp3-image-missing::after { min-height: 120px; display: grid; place-items: center; }
        .vp3-why-grid .vp3-image-missing { min-height: 320px; border-color: rgba(49,93,255,.25); color: #334155; background: linear-gradient(135deg, #eef4ff, #f7f2ff); }
        .vp3-launch-card { overflow: hidden; }
        .vp3-launch-head { max-width: 860px; margin-bottom: 22px; }
        .vp3-launch-head > span { display: inline-flex; color: #b6ffd8; background: rgba(42,192,122,.16); border: 1px solid rgba(42,192,122,.24); border-radius: 999px; padding: 7px 13px; font-size: .76rem; font-weight: 900; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 14px; }
        .vp3-launch-head p { color: #c8d3ea; max-width: 760px; }
        .vp3-launch-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-top: 26px; }
        .vp3-launch-item { background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.13); border-radius: 14px; padding: 18px; min-height: 178px; }
        .vp3-launch-item span { display: inline-flex; color: #b6ffd8; background: rgba(88,221,148,.13); border-radius: 999px; padding: 5px 10px; font-size: .68rem; font-weight: 900; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 12px; }
        .vp3-launch-item h3 { color: #fff !important; font-family: Inter, system-ui, sans-serif !important; font-size: 1rem; margin: 0 0 8px; }
        .vp3-launch-item p { color: #d9e3f6; font-size: .86rem; line-height: 1.48; margin: 0; }
        .vp3-launch-item a { display: inline-flex; margin-top: 13px; color: #b6d3ff !important; font-size: .8rem; font-weight: 900; text-decoration: none !important; }
        .vp3-launch-note { margin: 22px 0 0; color: #e7edfb !important; background: rgba(251,191,36,.12); border: 1px solid rgba(251,191,36,.22); border-radius: 12px; padding: 14px 16px; }
        .vp3-launch-note code { color: #fff; }
        @media (max-width: 1100px) { .vp3-launch-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 720px) { .vp3-launch-grid { grid-template-columns: 1fr; } }
CSS;
        $html = str_replace('</style>', $css . "\n    </style>", $html);
        $js = <<<'JS'
<script>
(function () {
    function markMissing(img) {
        var target = img.parentElement || img;
        target.classList.add('vp3-image-missing');
        img.remove();
    }
    document.querySelectorAll('img[src*="/assets/images/installer/"]').forEach(function (img) {
        img.addEventListener('error', function () { markMissing(img); }, { once: true });
        if (img.complete && img.naturalWidth === 0) markMissing(img);
    });
}());
</script>
JS;
        $html = str_replace('</body>', $js . "\n</body>", $html);
    }
    return $html;
});
