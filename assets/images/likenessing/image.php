<?php
declare(strict_types=1);

$images = [
    'hero' => ['prefix' => 'hero-', 'type' => 'image/webp'],
    'premise' => ['prefix' => 'premise-', 'type' => 'image/webp'],
    'newsletter' => ['prefix' => 'newsletter-', 'type' => 'image/webp'],
    'cast' => ['prefix' => 'cast-', 'type' => 'image/webp'],
    'episodes' => ['prefix' => 'episodes-', 'type' => 'image/webp'],
    'logo' => ['prefix' => 'logo-', 'type' => 'image/webp'],
];

$key = strtolower(trim((string)($_GET['key'] ?? '')));
if (!isset($images[$key])) {
    http_response_code(404);
    exit;
}

$parts = glob(__DIR__ . '/data/' . $images[$key]['prefix'] . '*.b64') ?: [];
sort($parts, SORT_STRING);
if (!$parts) {
    http_response_code(404);
    exit;
}

$encoded = '';
foreach ($parts as $part) {
    $chunk = file_get_contents($part);
    if ($chunk === false) {
        http_response_code(500);
        exit;
    }
    $encoded .= trim($chunk);
}

$image = base64_decode($encoded, true);
if ($image === false || $image === '') {
    http_response_code(500);
    exit;
}

header('Content-Type: ' . $images[$key]['type']);
header('Content-Length: ' . strlen($image));
header('Cache-Control: public, max-age=31536000, immutable');
header('X-Content-Type-Options: nosniff');
echo $image;
