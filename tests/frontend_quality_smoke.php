<?php

declare(strict_types=1);

$_SERVER['HTTP_HOST'] = 'stonefellow.test';
$_SERVER['HTTPS'] = 'on';
$_SERVER['REQUEST_URI'] = '/song.php?slug=born-to-burn&utm_source=test';
$_GET = ['slug' => 'born-to-burn', 'utm_source' => 'test'];

require_once __DIR__ . '/../includes/frontend_quality.php';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$absolute = sf_frontend_absolute_url('music.php');
$assert(str_contains($absolute, '/music.php'), 'Absolute URL helper should include the requested path.');
$assert(str_starts_with($absolute, 'https://') || str_starts_with($absolute, '/'), 'Absolute URL helper should return HTTPS or a safe root-relative fallback.');

$canonical = sf_frontend_canonical_url();
$assert(str_contains($canonical, 'song.php'), 'Canonical URL should preserve the current path.');
$assert(str_contains($canonical, 'slug=born-to-burn'), 'Canonical URL should preserve approved content identifiers.');
$assert(!str_contains($canonical, 'utm_source'), 'Canonical URL should remove tracking parameters.');

$json = sf_frontend_json_ld('Song', 'Stonefellow song page');
$decoded = json_decode($json, true);
$assert(is_array($decoded), 'JSON-LD should decode as an object.');
$assert(($decoded['@context'] ?? '') === 'https://schema.org', 'JSON-LD should use Schema.org context.');
$assert(($decoded['@type'] ?? '') === 'WebPage', 'JSON-LD should describe a WebPage.');

$root = dirname(__DIR__);
$required = [
    'includes/header.php' => ['sf-skip-link', 'aria-current="page"', 'rel="canonical"', 'frontend-quality.css'],
    'includes/footer.php' => ['Footer navigation', 'noopener noreferrer', 'frontend-quality.js', 'defer src'],
    'player.php' => ['Skip to player content', 'aria-label="Now playing controls"', 'frontend-quality.css', 'frontend-quality.js'],
    'assets/css/frontend-quality.css' => [':focus-visible', 'prefers-reduced-motion', 'forced-colors', 'min-height: 44px'],
    'assets/js/frontend-quality.js' => ['aria-expanded', 'ArrowLeft', 'progressbar', 'MutationObserver'],
];
foreach ($required as $file => $markers) {
    $body = (string)file_get_contents($root . '/' . $file);
    foreach ($markers as $marker) $assert(str_contains($body, $marker), $file . ' should contain ' . $marker . '.');
}

if ($failures) {
    fwrite(STDERR, "Frontend quality smoke failures:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "Frontend quality smoke: PASS\n";
