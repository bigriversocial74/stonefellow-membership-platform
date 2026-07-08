document.addEventListener('DOMContentLoaded', function () {
  if (!document.body.classList.contains('sf-logged-in')) return;
  var actions = document.querySelector('.home-header-actions');
  if (!actions) return;

  var drawer = document.querySelector('.sf-customer-drawer');
  if (!drawer) {
    var links = [
      ['Dashboard', 'member.php'],
      ['Library', 'library.php'],
      ['Watchlist', 'watchlist.php'],
      ['Playlists', 'playlists.php'],
      ['Player', 'player.php'],
      ['Merch Store', 'merch.php'],
      ['Cart', 'cart.php'],
      ['Messages', 'messages.php'],
      ['Notifications', 'notifications.php'],
      ['Comments', 'comments.php'],
      ['Billing', 'account-billing.php'],
      ['Account', 'account.php'],
      ['Support', 'support.php']
    ];
    drawer = document.createElement('details');
    drawer.className = 'sf-customer-drawer';
    drawer.innerHTML = '<summary class="sf-customer-drawer-toggle"><span aria-hidden="true"><i></i><i></i><i></i></span><b>Menu</b></summary><div class="sf-customer-drawer-panel"><div class="sf-customer-drawer-head"><span>Member Area</span><strong>Stonefellow</strong></div><nav class="sf-customer-nav"></nav><div class="sf-customer-side-actions"><a href="player.php">Open Player</a><a href="member.php">Dashboard</a></div></div>';
    var nav = drawer.querySelector('.sf-customer-nav');
    var current = (location.pathname.split('/').pop() || 'index.php').toLowerCase();
    links.forEach(function (item) {
      var a = document.createElement('a');
      a.href = item[1];
      a.textContent = item[0];
      if (current === item[1]) a.className = 'is-active';
      nav.appendChild(a);
    });
  }

  if (drawer.parentElement !== actions) {
    actions.insertBefore(drawer, actions.firstChild);
  }

  drawer.addEventListener('click', function (event) {
    var link = event.target.closest('a');
    if (link) drawer.removeAttribute('open');
  });
});
