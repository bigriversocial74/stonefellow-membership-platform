<?php

declare(strict_types=1);

require_once __DIR__ . '/admin_catalog.php';
require_once __DIR__ . '/catalog_operations.php';
require_once __DIR__ . '/media_pipeline.php';
require_once __DIR__ . '/live_commerce.php';
require_once __DIR__ . '/staging_launch_certification.php';
require_once __DIR__ . '/staging_integration_matrix.php';
require_once __DIR__ . '/data_ops_recovery.php';

if (defined('SF_STAGING_ACTIVATION_LOADED')) return;
define('SF_STAGING_ACTIVATION_LOADED', true);

require_once __DIR__ . '/staging_activation_core.php';
require_once __DIR__ . '/staging_activation_checks.php';
require_once __DIR__ . '/staging_activation_candidate.php';
