<?php
require_once __DIR__ . '/storyboard_generation.php';
require_once __DIR__ . '/ai_mission_execution.php';

if (defined('SF_AI_STAGING_CERTIFICATION_LOADED')) return;
define('SF_AI_STAGING_CERTIFICATION_LOADED', true);

require_once __DIR__ . '/ai_staging_certification_core.php';
require_once __DIR__ . '/ai_staging_certification_checks.php';
require_once __DIR__ . '/ai_staging_certification_runtime.php';
?>
