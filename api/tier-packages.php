<?php
require_once __DIR__ . '/../includes/membership_tiers.php';
sf_json_response(['ok'=>true,'plans'=>sf_tiers_public_plans(),'matrix'=>sf_tiers_benefit_matrix(),'summary'=>sf_tiers_admin_summary()]);
