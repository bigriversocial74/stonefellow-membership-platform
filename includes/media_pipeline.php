<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

if (defined('SF_MEDIA_PIPELINE_LOADED')) return;
define('SF_MEDIA_PIPELINE_LOADED', true);

require_once __DIR__ . '/media_storage.php';
require_once __DIR__ . '/media_uploads.php';
require_once __DIR__ . '/media_processing.php';
require_once __DIR__ . '/media_delivery_sessions.php';
