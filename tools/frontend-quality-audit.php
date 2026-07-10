<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$read = static function (string $path) use ($root): string {
    $full = $root . '/' . $path;
    return is_file($full) ? (string)file_get_contents($full) : '';
};
$has = static function (string $path, array $markers) use ($read): bool {
    $body = $read($path);
    if ($body === '') return false;
    foreach ($markers as $marker) if (!str_contains($body, $marker)) return false;
    return true;
};

$sections = [
    'Semantic structure' => [
        ['includes/header.php', ['<main id="main-content"', 'aria-label="Primary navigation"', 'sf-skip-link']],
        ['includes/footer.php', ['<footer', 'aria-label="Site footer"', 'Footer navigation']],
        ['player.php', ['<main class="sf-stream-main" id="main-content"', 'aria-label="Player sidebar"']],
    ],
    'Keyboard navigation' => [
        ['assets/js/frontend-quality.js', ['Escape', 'ArrowLeft', 'ArrowRight', 'Home', 'End']],
        ['includes/header.php', ['aria-expanded="false"', 'aria-controls="site-navigation"']],
        ['assets/css/frontend-quality.css', [':focus-visible', 'sf-skip-link:focus']],
    ],
    'Forms and validation' => [
        ['assets/js/frontend-quality.js', ['querySelectorAll(\'input, select, textarea\')', 'addEventListener(\'invalid\'']],
        ['includes/footer.php', ['Join Stonefellow', 'Create Account']],
        ['assets/css/frontend-quality.css', [':user-invalid', 'font: inherit']],
    ],
    'Media accessibility' => [
        ['assets/js/frontend-quality.js', ['audio, video', 'progressbar', 'Playback progress']],
        ['player.php', ['aria-label="Stonefellow audio player"', 'aria-label="Now playing controls"', 'aria-label="Play or pause"']],
        ['index.php', ['aria-haspopup="dialog"', 'aria-modal="true"', 'title="Stonefellow video preview"']],
    ],
    'Responsive and touch' => [
        ['assets/css/frontend-quality.css', ['@media (max-width: 880px)', 'min-width: 44px', 'min-height: 44px', 'touch-action: manipulation']],
        ['includes/header.php', ['viewport-fit=cover']],
        ['player.php', ['viewport-fit=cover']],
    ],
    'Motion and display preferences' => [
        ['assets/css/frontend-quality.css', ['prefers-reduced-motion', 'forced-colors', 'scroll-behavior: auto']],
        ['includes/header.php', ['color-scheme']],
        ['player.php', ['color-scheme']],
    ],
    'Metadata and discovery' => [
        ['includes/header.php', ['rel="canonical"', 'property="og:title"', 'twitter:card', 'application/ld+json', 'meta name="robots"']],
        ['includes/frontend_quality.php', ['sf_frontend_canonical_url', 'sf_frontend_json_ld', 'http_build_query']],
        ['player.php', ['rel="canonical"', 'property="og:image"', 'noindex,nofollow,noarchive']],
    ],
    'Asset loading performance' => [
        ['includes/header.php', ['fetchpriority="high"', 'decoding="async"']],
        ['includes/footer.php', ['loading="lazy"', 'defer src']],
        ['assets/js/frontend-quality.js', ['loading', 'lazy', 'decoding', 'async']],
    ],
    'Runtime safety and compatibility' => [
        ['assets/js/frontend-quality.js', ['window.CSS && CSS.escape', 'requestAnimationFrame', 'MutationObserver']],
        ['includes/frontend_quality.php', ['function_exists(\'mb_substr\')', 'JSON_HEX_TAG']],
        ['includes/footer.php', ['noopener noreferrer']],
    ],
    'Status, errors and print' => [
        ['includes/header.php', ['aria-live="polite"', 'role="alert"', 'role="status"']],
        ['assets/css/frontend-quality.css', ['@media print', 'sf-flash[role="alert"]']],
        ['assets/js/frontend-quality.js', ['field.focus()', 'aria-valuenow']],
    ],
];

$failed = [];
$totalPoints = 0;
$earnedPoints = 0;
echo "Stonefellow Front-End Production Quality Audit v1\n";
echo str_repeat('=', 54) . "\n";
foreach ($sections as $section => $checks) {
    $passed = 0;
    foreach ($checks as [$file, $markers]) {
        $totalPoints++;
        if ($has($file, $markers)) {
            $passed++;
            $earnedPoints++;
        } else {
            $failed[] = $section . ': ' . $file . ' missing one or more required controls.';
        }
    }
    $score = (int)round(($passed / count($checks)) * 10);
    echo sprintf("%-36s %d/10 (%d/%d)\n", $section, $score, $passed, count($checks));
}
$overall = $totalPoints ? round(($earnedPoints / $totalPoints) * 10, 1) : 0;
echo str_repeat('-', 54) . "\n";
echo "Overall score: {$overall}/10\n";
if ($failed) {
    echo "\nBlocking findings:\n- " . implode("\n- ", $failed) . "\n";
    exit(1);
}
echo "Result: PASS — all ten sections score 10/10.\n";
