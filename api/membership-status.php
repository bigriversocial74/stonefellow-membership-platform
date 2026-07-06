<?php
require_once __DIR__ . '/../includes/membership.php';
sf_json_response(['ok' => true, 'member' => sf_member_snapshot()]);
