<?php
require_once __DIR__ . '/../includes/search.php';
$q = trim((string)($_GET['q'] ?? ''));
$type = trim((string)($_GET['type'] ?? ''));
$results = sf_search_results($q, $type);
sf_json_response(['ok'=>true,'query'=>$q,'type'=>$type,'facets'=>sf_search_facets(sf_search_results($q, '')),'results'=>$results]);
