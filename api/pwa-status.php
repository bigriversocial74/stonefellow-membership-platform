<?php
require_once __DIR__ . '/../includes/config.php';
$root = dirname(__DIR__);
$files = [
  'manifest' => 'manifest.webmanifest',
  'service_worker' => 'service-worker.js',
  'offline_page' => 'offline.php',
  'runtime_js' => 'assets/js/pwa-upload.js',
  'runtime_css' => 'assets/css/pwa-upload.css',
];
$status = [];
foreach ($files as $key => $path) {
  $status[$key] = ['path' => $path, 'exists' => is_file($root . '/' . $path), 'url' => sf_url($path)];
}
sf_json_response(['ok' => true, 'pwa' => $status]);
