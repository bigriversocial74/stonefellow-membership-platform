<?php

declare(strict_types=1);

if (defined('SF_MEDIA_PROCESSING_LOADED')) return;
define('SF_MEDIA_PROCESSING_LOADED', true);

require_once __DIR__ . '/media_processing_queue.php';
require_once __DIR__ . '/media_processing_runtime.php';
require_once __DIR__ . '/media_processing_audio.php';
require_once __DIR__ . '/media_processing_video.php';
require_once __DIR__ . '/media_processing_worker.php';
