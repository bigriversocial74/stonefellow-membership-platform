<?php
require_once __DIR__ . '/../includes/publishing.php';

$result = sf_publish_run_due();
sf_json_response($result);
