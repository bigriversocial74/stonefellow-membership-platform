<?php
if (!function_exists('lk_asset_url')) {
  function lk_asset_url(string $name): string {
    return sf_url('likenessing-asset.php?name=' . rawurlencode($name) . '&v=20260716');
  }
}
