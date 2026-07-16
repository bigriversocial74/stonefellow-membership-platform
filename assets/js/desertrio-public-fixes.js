(function () {
  'use strict';

  function buildPublicNavigation(header) {
    let nav = header.querySelector('.dr-nav');
    if (nav) return nav;

    const current = (window.location.pathname.split('/').pop() || 'index.php').toLowerCase();
    const items = [
      ['Home', 'index.php', ['index.php']],
      ['About', 'series.php', ['series.php']],
      ['Cast', 'cast.php', ['cast.php', 'series-characters.php']],
      ['Episodes', 'episodes.php', ['episodes.php', 'episode.php', 'watch.php']],
      ['Gallery', 'episodes.php#gallery', []],
      ['News', 'series.php#news', []],
      ['Shop', 'merch.php', ['merch.php', 'product.php', 'cart.php', 'checkout.php']]
    ];

    nav = document.createElement('nav');
    nav.className = 'dr-nav';
    nav.id = 'site-navigation';
    nav.dataset.siteNav = '';
    nav.setAttribute('aria-label', 'Primary navigation');
    nav.innerHTML = items.map(([label, href, pages]) => {
      const active = pages.includes(current);
      return `<a href="${href}"${active ? ' class="is-active" aria-current="page"' : ''}>${label}</a>`;
    }).join('');

    const actions = header.querySelector('.dr-header-actions');
    header.insertBefore(nav, actions || null);
    return nav;
  }

  function buildMenuButton(header) {
    let button = header.querySelector('.dr-menu-button');
    if (!button) {
      button = document.createElement('button');
      button.className = 'dr-menu-button nav-toggle';
      button.type = 'button';
      button.innerHTML = '<span aria-hidden="true"></span><span aria-hidden="true"></span><span aria-hidden="true"></span>';
      header.appendChild(button);
    }

    const cleanButton = button.cloneNode(true);
    button.replaceWith(cleanButton);
    cleanButton.type = 'button';
    cleanButton.dataset.navToggle = '';
    cleanButton.setAttribute('aria-controls', 'site-navigation');
    cleanButton.setAttribute('aria-expanded', 'false');
    cleanButton.setAttribute('aria-label', 'Open navigation');
    return cleanButton;
  }

  function initializeDesertRioMenu() {
    if (!document.body.classList.contains('dr-theme')) return;

    const header = document.querySelector('.dr-header-inner');
    if (!header) return;

    const nav = buildPublicNavigation(header);
    const button = buildMenuButton(header);

    const setOpen = (open) => {
      nav.classList.toggle('is-open', open);
      button.classList.toggle('is-open', open);
      button.setAttribute('aria-expanded', open ? 'true' : 'false');
      button.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
      document.body.classList.toggle('dr-menu-open', open);
    };

    button.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      setOpen(!nav.classList.contains('is-open'));
    });

    nav.addEventListener('click', (event) => {
      if (event.target.closest('a')) setOpen(false);
    });

    document.addEventListener('click', (event) => {
      if (!nav.classList.contains('is-open')) return;
      if (nav.contains(event.target) || button.contains(event.target)) return;
      setOpen(false);
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') setOpen(false);
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth > 900) setOpen(false);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeDesertRioMenu, { once: true });
  } else {
    initializeDesertRioMenu();
  }
})();
