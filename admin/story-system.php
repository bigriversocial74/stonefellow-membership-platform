<?php
require __DIR__ . '/../includes/config.php';
$target = 'admin/storyboards.php';
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ' . sf_url($target . ($query !== '' ? '?' . $query : '')), true, 302);
exit;
?>