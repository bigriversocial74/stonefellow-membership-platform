<?php
require __DIR__ . '/../includes/storyboarding_system.php';
$query = $_SERVER['QUERY_STRING'] ?? '';
sf_admin_redirect(sf_url('admin/characters.php' . ($query !== '' ? '?' . $query : '')));
