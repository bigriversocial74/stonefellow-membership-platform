<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/settings.php';
$sfHeaderUser = sf_auth_user();
$sfPageClass = (string)($pageClass ?? '');
$sfIsAdminSurface = strpos($sfPageClass, 'admin-catalog-page') !== false || strpos(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/admin') !== false;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= htmlspecialchars($pageTitle ?? $site['name']) ?> | <?= htmlspecialchars($site['name']) ?></title>
  <meta name="description" content="<?= htmlspecialchars($pageDescription ?? $site['tagline']) ?>">
  <meta name="theme-color" content="#0b0907">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Stonefellow">
  <link rel="manifest" href="<?= sf_url('manifest.webmanifest') ?>">
  <link rel="apple-touch-icon" href="<?= sf_asset('images/brand/logo-mark.png') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Bebas+Neue&family=Cinzel:wght@600;700&family=Bodoni+Moda:opsz,wght@6..96,500;6..96,600;6..96,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= sf_asset('css/stonefellow.css') ?>">
  <link rel="stylesheet" href="<?= sf_asset('css/pwa-upload.css') ?>">
  <?php if ($sfIsAdminSurface): ?><link rel="stylesheet" href="<?= sf_asset('css/admin-polish.css') ?>"><?php endif; ?>
</head>
<body class="<?= htmlspecialchars($pageClass ?? '') ?>">
  <div class="site-noise" aria-hidden="true"></div>
  <header class="home-header-full site-global-header">
    <div class="home-header">
      <a class="home-brand" href="<?= sf_url('index.php') ?>" aria-label="Stonefellow home"><img src="<?= sf_asset('images/brand/home-brand-approved.png') ?>" alt="Stonefellow" class="home-brand-image"></a>
      <button class="nav-toggle home-nav-toggle" type="button" aria-label="Open navigation" data-nav-toggle><span></span><span></span><span></span></button>
      <nav class="home-nav" data-site-nav>
        <a class="<?= sf_is_active('index.php') ?>" href="<?= sf_url('index.php') ?>">Home</a>
        <a class="<?= sf_is_active('series.php') ?>" href="<?= sf_url('series.php') ?>">Series</a>
        <?php $episodesNavActive = in_array(sf_current_page(), ['episodes.php', 'episode.php', 'watch.php'], true) ? 'is-active' : ''; ?>
        <a class="<?= $episodesNavActive ?>" href="<?= sf_url('episodes.php') ?>">Episodes</a>
        <?php $musicNavActive = in_array(sf_current_page(), ['music.php', 'player.php', 'album.php', 'song.php'], true) ? 'is-active' : ''; ?>
        <a class="<?= $musicNavActive ?>" href="<?= sf_url('music.php') ?>">Music</a>
        <a class="<?= sf_is_active('feed.php') ?>" href="<?= sf_url('feed.php') ?>">Feed</a>
        <a class="<?= sf_is_active('cast.php') ?>" href="<?= sf_url('cast.php') ?>">Cast</a>
        <?php $merchNavActive = in_array(sf_current_page(), ['merch.php', 'product.php', 'cart.php', 'checkout.php', 'order-confirmation.php'], true) ? 'is-active' : ''; ?>
        <a class="<?= $merchNavActive ?>" href="<?= sf_url('merch.php') ?>">Merch</a>
        <a class="<?= sf_is_active('search.php') ?>" href="<?= sf_url('search.php') ?>">Search</a>
        <a class="<?= sf_is_active('cart.php') ?>" href="<?= sf_url('cart.php') ?>">Cart</a>
        <?php $memberNavActive = in_array(sf_current_page(), ['member.php', 'library.php', 'watchlist.php', 'playlists.php', 'account.php', 'account-billing.php', 'billing-checkout.php', 'billing-success.php', 'billing-cancel.php', 'notifications.php', 'comments.php', 'messages.php', 'support.php'], true) ? 'is-active' : ''; ?>
        <a class="<?= $memberNavActive ?>" href="<?= sf_url('member.php') ?>">Member</a>
        <?php $adminNavActive = strpos(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/admin') !== false ? 'is-active' : ''; ?>
        <a class="<?= $adminNavActive ?>" href="<?= sf_url('admin/index.php') ?>">Admin</a>
      </nav>
      <div class="home-header-actions"><?php if ($sfHeaderUser): ?><a href="<?= sf_url('account.php') ?>" class="home-link-btn <?= sf_is_active('account.php') ?>"><?= htmlspecialchars($sfHeaderUser['display_name'] ?: 'Account') ?></a><a href="<?= sf_url('logout.php') ?>" class="home-subscribe-btn">Logout</a><?php else: ?><a href="<?= sf_url('signin.php') ?>" class="home-link-btn <?= sf_is_active('signin.php') ?>">Sign In</a><a href="<?= sf_url('signup.php') ?>" class="home-subscribe-btn <?= sf_is_active('signup.php') ?>">Subscribe</a><?php endif; ?></div>
    </div>
  </header>
  <main>
    <?php $sfAuthFlashes = sf_auth_flashes(); ?>
    <?php if ($sfAuthFlashes): ?><div class="sf-flash-stack" role="status" aria-live="polite"><?php foreach ($sfAuthFlashes as $flash): ?><div class="sf-flash sf-flash-<?= htmlspecialchars($flash['type'] ?? 'info') ?>"><?= htmlspecialchars($flash['message'] ?? '') ?></div><?php endforeach; ?></div><?php endif; ?>
