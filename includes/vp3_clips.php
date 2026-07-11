<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/license.php';
require_once __DIR__ . '/media_pipeline.php';

if (defined('SF_VP3_CLIPS_LOADED')) {
    return;
}
define('SF_VP3_CLIPS_LOADED', true);

require_once __DIR__ . '/vp3_clips_core.php';
require_once __DIR__ . '/vp3_clips_render.php';
require_once __DIR__ . '/vp3_clips_bridge.php';
require_once __DIR__ . '/vp3_clips_jobs.php';
