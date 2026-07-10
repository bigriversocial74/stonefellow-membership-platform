<?php

declare(strict_types=1);

require_once __DIR__ . '/store.php';
require_once __DIR__ . '/commerce_provider.php';

if (defined('SF_LIVE_COMMERCE_LOADED')) return;
define('SF_LIVE_COMMERCE_LOADED', true);

require_once __DIR__ . '/live_commerce_core.php';
require_once __DIR__ . '/live_commerce_checkout_create.php';
require_once __DIR__ . '/live_commerce_checkout_settlement.php';
require_once __DIR__ . '/live_commerce_events.php';
require_once __DIR__ . '/live_commerce_operations.php';
