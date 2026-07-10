<?php

declare(strict_types=1);

function sf_security_content_policy(): string
{
    $directives = [
        "default-src 'self'",
        "base-uri 'self'",
        "object-src 'none'",
        "frame-ancestors 'self'",
        "form-action 'self'",
        "script-src 'self' 'unsafe-inline'",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
        "font-src 'self' https://fonts.gstatic.com data:",
        "img-src 'self' data: blob: https:",
        "media-src 'self' blob: https:",
        "connect-src 'self'",
        "frame-src 'self' https://www.youtube.com https://youtube.com https://www.youtube-nocookie.com",
        "manifest-src 'self'",
        "worker-src 'self' blob:",
    ];
    if (function_exists('sf_is_production') && sf_is_production()) $directives[] = 'upgrade-insecure-requests';
    return implode('; ', $directives);
}

function sf_security_send_hardened_headers(): void
{
    if (PHP_SAPI === 'cli' || headers_sent()) return;
    $policy = sf_security_content_policy();
    $reportOnly = sf_env_bool('SF_CSP_REPORT_ONLY', false);
    header(($reportOnly ? 'Content-Security-Policy-Report-Only: ' : 'Content-Security-Policy: ') . $policy);
    header('Cross-Origin-Resource-Policy: same-site');
    header('Origin-Agent-Cluster: ?1');
}
