<?php

declare(strict_types=1);

if (defined('SF_MEDIA_DELIVERY_SESSIONS_LOADED')) return;
define('SF_MEDIA_DELIVERY_SESSIONS_LOADED', true);

require_once __DIR__ . '/media_delivery_tokens.php';
require_once __DIR__ . '/media_delivery_manifests.php';
require_once __DIR__ . '/media_delivery_transport.php';
