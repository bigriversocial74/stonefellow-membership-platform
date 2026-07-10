<?php

declare(strict_types=1);

if (defined('SF_MEDIA_UPLOADS_LOADED')) return;
define('SF_MEDIA_UPLOADS_LOADED', true);

require_once __DIR__ . '/media_upload_sessions.php';
require_once __DIR__ . '/media_upload_chunks.php';
require_once __DIR__ . '/media_upload_finalize.php';
