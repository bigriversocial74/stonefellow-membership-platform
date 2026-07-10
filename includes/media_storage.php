<?php

declare(strict_types=1);

if (defined('SF_MEDIA_STORAGE_LOADED')) return;
define('SF_MEDIA_STORAGE_LOADED', true);

require_once __DIR__ . '/media_storage_core.php';
require_once __DIR__ . '/media_storage_registry.php';
require_once __DIR__ . '/media_storage_s3.php';
require_once __DIR__ . '/media_storage_operations.php';
