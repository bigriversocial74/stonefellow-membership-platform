<?php
require_once __DIR__ . '/config.php';

function sf_frontend_absolute_url(string $path = ''): string
{
    global $site;
    $base = rtrim((string)($site['base_url'] ?? ''), '/');
    if ($base !== '' && preg_match('#^https?://#i', $base)) {
        return $base . '/' . ltrim($path, '/');
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host = preg_replace('/[^a-z0-9.:-]/i', '', (string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') return '/' . ltrim($path, '/');
    return ($https ? 'https' : 'http') . '://' . $host . '/' . ltrim($path, '/');
}

function sf_frontend_canonical_url(): string
{
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';
    $allowed = [];
    foreach ($_GET as $key => $value) {
        if (in_array((string)$key, ['page', 'season', 'album', 'song', 'episode', 'product', 'slug', 'q'], true) && is_scalar($value)) {
            $raw = (string)$value;
            $allowed[(string)$key] = function_exists('mb_substr') ? mb_substr($raw, 0, 120) : substr($raw, 0, 120);
        }
    }
    return sf_frontend_absolute_url(ltrim($path, '/')) . ($allowed ? '?' . http_build_query($allowed) : '');
}

function sf_frontend_social_image(): string
{
    global $pageImage;
    $image = trim((string)($pageImage ?? 'assets/images/brand/home-brand-approved.png'));
    return preg_match('#^https?://#i', $image) ? $image : sf_frontend_absolute_url($image);
}

function sf_frontend_json_ld(string $title, string $description): string
{
    global $site;
    $payload = [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => $title,
        'description' => $description,
        'url' => sf_frontend_canonical_url(),
        'isPartOf' => [
            '@type' => 'WebSite',
            'name' => (string)($site['name'] ?? 'Stonefellow'),
            'url' => sf_frontend_absolute_url(''),
        ],
    ];
    return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}';
}
