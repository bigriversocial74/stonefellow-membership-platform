<?php

declare(strict_types=1);

require_once __DIR__ . '/admin_catalog.php';
require_once __DIR__ . '/publishing.php';

if (defined('SF_LCO_LOADED')) return;
define('SF_LCO_LOADED', true);

require_once __DIR__ . '/catalog_operations_core.php';
require_once __DIR__ . '/catalog_operations_readiness.php';
require_once __DIR__ . '/catalog_operations_actions.php';
require_once __DIR__ . '/catalog_operations_transfer.php';
