<?php
$sfCustomerNav = [
  ['label' => 'Dashboard', 'href' => 'member.php', 'pages' => ['member.php']],
  ['label' => 'Library', 'href' => 'library.php', 'pages' => ['library.php']],
  ['label' => 'Watchlist', 'href' => 'watchlist.php', 'pages' => ['watchlist.php']],
  ['label' => 'Playlists', 'href' => 'playlists.php', 'pages' => ['playlists.php']],
  ['label' => 'Messages', 'href' => 'messages.php', 'pages' => ['messages.php']],
  ['label' => 'Notifications', 'href' => 'notifications.php', 'pages' => ['notifications.php']],
  ['label' => 'Comments', 'href' => 'comments.php', 'pages' => ['comments.php']],
  ['label' => 'Billing', 'href' => 'account-billing.php', 'pages' => ['account-billing.php']],
  ['label' => 'Account', 'href' => 'account.php', 'pages' => ['account.php']],
  ['label' => 'Support', 'href' => 'support.php', 'pages' => ['support.php']],
];
?>
<aside class="sf-customer-sidebar" aria-label="Member navigation">
  <div class="sf-customer-side-brand"><span>Member Area</span><strong><?= htmlspecialchars($sfHeaderUser['display_name'] ?: 'Stonefellow') ?></strong></div>
  <nav class="sf-customer-nav">
    <?php foreach ($sfCustomerNav as $item): ?>
      <?php $isActive = in_array($sfCurrentPage, $item['pages'], true) ? 'is-active' : ''; ?>
      <a class="<?= $isActive ?>" href="<?= sf_url($item['href']) ?>"><?= htmlspecialchars($item['label']) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="sf-customer-side-actions"><a href="<?= sf_url('player.php') ?>">Open Player</a><a href="<?= sf_url('logout.php') ?>">Logout</a></div>
</aside>
