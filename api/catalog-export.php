<?php

declare(strict_types=1);
require __DIR__.'/../includes/catalog_operations.php';
$type=(string)($_GET['type']??'');
if(!sf_lco_type($type)){http_response_code(422);echo'Invalid catalog type.';exit;}
$csv=sf_lco_export_csv($type);
if($csv===''){http_response_code(404);echo'No catalog rows found.';exit;}
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="stonefellow-'.$type.'-'.date('Ymd-His').'.csv"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
echo$csv;
