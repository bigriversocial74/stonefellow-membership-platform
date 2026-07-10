<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/frontend_quality.php';
$sfHeaderUser = sf_auth_user();
$sfPageClass = (string)($pageClass ?? '');
$sfIsAdminSurface = strpos($sfPageClass, 'admin-catalog-page') !== false || strpos(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/admin') !== false;
$sfIsAdminUser = $sfHeaderUser && (($sfHeaderUser['role'] ?? '') === 'admin');
if ($sfIsAdminSurface && sf_db() instanceof PDO) {
  if (!$sfIsAdminUser) sf_require_admin();
  require_once __DIR__ . '/admin_security.php';
  sf_sec_route_guard();
}
$sfIsCustomerSurface = $sfHeaderUser && !$sfIsAdminSurface && strpos($sfPageClass, 'membership-page') !== false;
$sfPublicNav = [
  ['label' => 'Home', 'href' => 'index.php', 'pages' => ['index.php']],
  ['label' => 'Series', 'href' => 'series.php', 'pages' => ['series.php']],
  ['label' => 'Episodes', 'href' => 'episodes.php', 'pages' => ['episodes.php', 'episode.php', 'watch.php']],
  ['label' => 'Music', 'href' => 'music.php', 'pages' => ['music.php', 'player.php', 'album.php', 'song.php']],
  ['label' => 'Cast', 'href' => 'cast.php', 'pages' => ['cast.php']],
  ['label' => 'Merch', 'href' => 'merch.php', 'pages' => ['merch.php', 'product.php']],
];
$sfCurrentPage = sf_current_page();
$sfMainNav = $sfHeaderUser ? [] : $sfPublicNav;
$sfBodyClass = trim($sfPageClass . ($sfHeaderUser ? ' sf-logged-in' : '') . ($sfIsCustomerSurface ? ' sf-customer-ui-page' : ''));
$sfSiteName = (string)($site['name'] ?? 'Stonefellow');
$sfMetaTitle = trim((string)($pageTitle ?? $sfSiteName));
$sfDocumentTitle = strcasecmp($sfMetaTitle, $sfSiteName) === 0 ? $sfSiteName : $sfMetaTitle . ' | ' . $sfSiteName;
$sfMetaDescription = trim((string)($pageDescription ?? $site['tagline'] ?? ''));
$sfCanonical = sf_frontend_canonical_url();
$sfSocialImage = sf_frontend_social_image();
$sfRobots = (string)($pageRobots ?? ($sfIsAdminSurface ? 'noindex,nofollow,noarchive' : 'index,follow,max-image-preview:large'));
?>
<!doctype html>
<html lang="<?= htmlspecialchars((string)($pageLang ?? 'en'), ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= htmlspecialchars($sfDocumentTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="description" content="<?= htmlspecialchars($sfMetaDescription, ENT_QUOTES, 'UTF-8') ?>">
  <meta name="robots" content="<?= htmlspecialchars($sfRobots, ENT_QUOTES, 'UTF-8') ?>">
  <meta name="theme-color" content="#0b0907">
  <meta name="color-scheme" content="dark light">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Stonefellow">
  <link rel="canonical" href="<?= htmlspecialchars($sfCanonical, ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="<?= htmlspecialchars($sfSiteName, ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:title" content="<?= htmlspecialchars($sfDocumentTitle, ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:description" content="<?= htmlspecialchars($sfMetaDescription, ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:url" content="<?= htmlspecialchars($sfCanonical, ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:image" content="<?= htmlspecialchars($sfSocialImage, ENT_QUOTES, 'UTF-8') ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= htmlspecialchars($sfDocumentTitle, ENT_QUOTES, 'UTF-8') ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($sfMetaDescription, ENT_QUOTES, 'UTF-8') ?>">
  <meta name="twitter:image" content="<?= htmlspecialchars($sfSocialImage, ENT_QUOTES, 'UTF-8') ?>">
  <link rel="manifest" href="<?= sf_url('manifest.webmanifest') ?>">
  <link rel="apple-touch-icon" href="<?= sf_asset('images/brand/logo-mark.png') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Bebas+Neue&family=Cinzel:wght@600;700&family=Bodoni+Moda:opsz,wght@6..96,500;6..96,600;6..96,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= sf_asset('css/stonefellow.css') ?>">
  <link rel="stylesheet" href="<?= sf_asset('css/pwa-upload.css') ?>">
  <link rel="stylesheet" href="<?= sf_asset('css/nav-cleanup.css') ?>">
  <link rel="stylesheet" href="<?= sf_asset('css/light-card-text.css') ?>">
  <link rel="stylesheet" href="<?= sf_asset('css/customer-ui.css') ?>">
  <link rel="stylesheet" href="<?= sf_asset('css/mobile-home.css') ?>">
  <link rel="stylesheet" href="<?= sf_asset('css/frontend-quality.css') ?>">
  <?php if ($sfIsAdminSurface): ?><link rel="stylesheet" href="<?= sf_asset('css/admin-polish.css') ?>"><link rel="stylesheet" href="<?= sf_asset('css/admin-tabs.css') ?>"><link rel="stylesheet" href="<?= sf_asset('css/storyboarding-system.css') ?>"><?php endif; ?>
  <script type="application/ld+json"><?= sf_frontend_json_ld($sfMetaTitle, $sfMetaDescription) ?></script>
</head>
<body class="<?= htmlspecialchars($sfBodyClass, ENT_QUOTES, 'UTF-8') ?>">
  <a class="sf-skip-link" href="#main-content">Skip to main content</a>
  <div class="site-noise" aria-hidden="true"></div>
  <header class="home-header-full site-global-header">
    <div class="home-header">
      <a class="home-brand" href="<?= sf_url('index.php') ?>" aria-label="Stonefellow home"><img src="<?= sf_asset('images/brand/home-brand-approved.png') ?>" alt="Stonefellow" class="home-brand-image" decoding="async" fetchpriority="high"></a>
      <?php if ($sfMainNav): ?><button class="nav-toggle home-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="site-navigation" data-nav-toggle><span aria-hidden="true"></span><span aria-hidden="true"></span><span aria-hidden="true"></span></button><?php endif; ?>
      <?php if ($sfMainNav): ?><nav class="home-nav" id="site-navigation" aria-label="Primary navigation" data-site-nav><?php foreach ($sfMainNav as $item): ?><?php $isActive = in_array($sfCurrentPage, $item['pages'], true); ?><a class="<?= $isActive ? 'is-active' : '' ?>"<?= $isActive ? ' aria-current="page"' : '' ?> href="<?= sf_url($item['href']) ?>"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></a><?php endforeach; ?></nav><?php endif; ?>
      <div class="home-header-actions">
        <?php if ($sfHeaderUser): ?>
          <details class="home-user-menu"><summary class="home-user-summary"><span><?= htmlspecialchars($sfHeaderUser['display_name'] ?: 'Account', ENT_QUOTES, 'UTF-8') ?></span></summary><div class="home-user-dropdown"><a href="<?= sf_url('member.php') ?>">Member Dashboard</a><a href="<?= sf_url('library.php') ?>">My Library</a><a href="<?= sf_url('watchlist.php') ?>">Watchlist</a><a href="<?= sf_url('playlists.php') ?>">Playlists</a><a href="<?= sf_url('notifications.php') ?>">Notifications</a><a href="<?= sf_url('messages.php') ?>">Messages</a><a href="<?= sf_url('comments.php') ?>">Comments</a><a href="<?= sf_url('cart.php') ?>">Cart</a><a href="<?= sf_url('account.php') ?>">Account Settings</a><a href="<?= sf_url('account-billing.php') ?>">Billing</a><a href="<?= sf_url('support.php') ?>">Support</a><?php if ($sfIsAdminUser): ?><a class="home-user-admin-link" href="<?= sf_url('admin/index.php') ?>">Admin Dashboard</a><?php endif; ?><a href="<?= sf_url('logout.php') ?>">Logout</a></div></details>
        <?php else: ?>
          <details class="home-user-menu home-user-menu-public"><summary class="home-user-summary"><span>Account</span></summary><div class="home-user-dropdown"><a href="<?= sf_url('signin.php') ?>">Sign In</a><a href="<?= sf_url('signup.php') ?>">Create Account</a><a href="<?= sf_url('forgot-password.php') ?>">Forgot Password</a></div></details><a href="<?= sf_url('signup.php') ?>" class="home-subscribe-btn <?= sf_is_active('signup.php') ?>">Subscribe</a>
        <?php endif; ?>
      </div>
    </div>
  </header>
  <main id="main-content" tabindex="-1">
    <?php $sfAuthFlashes = sf_auth_flashes(); ?>
    <?php if (strpos($sfPageClass, 'auth-template') !== false): ?><?php $sfAuthFlashes = array_values(array_filter($sfAuthFlashes, static function ($flash): bool { return !((string)($flash['type'] ?? '') === 'warning' && trim((string)($flash['message'] ?? '')) === 'Sign in to continue.'); })); ?><?php endif; ?>
    <?php if ($sfAuthFlashes): ?><div class="sf-flash-stack" aria-live="polite"><?php foreach ($sfAuthFlashes as $flash): ?><?php $sfFlashType = (string)($flash['type'] ?? 'info'); ?><div class="sf-flash sf-flash-<?= htmlspecialchars($sfFlashType, ENT_QUOTES, 'UTF-8') ?>" role="<?= in_array($sfFlashType, ['error', 'danger'], true) ? 'alert' : 'status' ?>"><?= htmlspecialchars($flash['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></div><?php endforeach; ?></div><?php endif; ?>
