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

ob_start(static function (string $html): string {
    if ($html === '' || strpos($html, 'VP3 Media Group Platform Installer') === false) return $html;
    $pattern = '#<div class="vp3-checklist"><h3>▣ Database Checklist</h3>.*?<div class="vp3-checkline"><span>Backup Capability</span><b class="vp3-pass">Pass</b></div></div>#s';
    $updated = preg_replace($pattern, sfh_install_db_checklist(), $html, 1);
    if (is_string($updated)) $html = $updated;

    if (strpos($html, 'sf-installer-hardening') === false) {
        $css = <<<'CSS'
        /* sf-installer-hardening */
        .vp3-warn { color: #fbbf24; font-weight: 900; }
        .vp3-image-missing { min-height: 150px; display: grid !important; place-items: center; background: linear-gradient(135deg, rgba(49,93,255,.14), rgba(139,61,255,.12)); border: 1px dashed rgba(255,255,255,.26); color: #f8fafc; text-align: center; }
        .vp3-image-missing::after { content: "Installer preview image missing. Preserve /assets/images/installer/ during deploy."; display: block; padding: 20px; font-size: .78rem; font-weight: 850; line-height: 1.35; }
        .vp3-dashboard-frame.vp3-image-missing::after { min-height: 330px; display: grid; place-items: center; }
        .vp3-floating-card.vp3-image-missing::after { min-height: 120px; display: grid; place-items: center; }
        .vp3-why-grid .vp3-image-missing { min-height: 320px; border-color: rgba(49,93,255,.25); color: #334155; background: linear-gradient(135deg, #eef4ff, #f7f2ff); }
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
