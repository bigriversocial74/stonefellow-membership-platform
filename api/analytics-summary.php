<?php
require_once __DIR__ . '/../includes/analytics_v2.php';
$days = isset($_GET['days']) && is_numeric($_GET['days']) ? (int)$_GET['days'] : 30;
if (!in_array($days, [7,30,90,365], true)) $days = 30;
sf_json_response(['ok'=>true,'snapshot'=>sf_analytics_v2_snapshot($days)]);
