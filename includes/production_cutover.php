<?php

declare(strict_types=1);

require_once __DIR__.'/production_launch.php';
require_once __DIR__.'/monitoring_alerts.php';
require_once __DIR__.'/staging_activation.php';

if(defined('SF_PRODUCTION_CUTOVER_LOADED'))return;
define('SF_PRODUCTION_CUTOVER_LOADED',true);

require_once __DIR__.'/production_cutover_core.php';
require_once __DIR__.'/production_cutover_checks.php';
require_once __DIR__.'/production_cutover_hypercare.php';
