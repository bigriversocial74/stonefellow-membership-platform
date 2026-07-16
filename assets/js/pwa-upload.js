(function(){
  const pwaConfig = window.STONEFELLOW_PWA || {};
  const brandName = pwaConfig.brandName || 'DesertRio';
  const publicTheme = () => document.body && document.body.classList.contains('dr-theme');
  let deferredPrompt = null;

  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register(pwaConfig.serviceWorker || 'service-worker.js').catch((error) => console.warn(`${brandName} service worker registration failed:`, error));
    });
  }

  function currentPublicPage(){
    const page = (window.location.pathname.split('/').pop() || 'index.php').toLowerCase();
    return page === '' ? 'index.php' : page;
  }

  function ensurePublicNavigation(){
    if (!publicTheme()) return;
    const headerInner = document.querySelector('.dr-header-inner');
    if (!headerInner || headerInner.querySelector('.dr-nav')) return;

    const page = currentPublicPage();
    const items = [
      ['Home', 'index.php', ['index.php']],
      ['About', 'series.php', ['series.php']],
      ['Cast', 'cast.php', ['cast.php', 'series-characters.php']],
      ['Episodes', 'episodes.php', ['episodes.php', 'episode.php', 'watch.php']],
      ['Gallery', 'episodes.php#gallery', []],
      ['News', 'series.php#news', []],
      ['Shop', 'merch.php', ['merch.php', 'product.php', 'cart.php', 'checkout.php']]
    ];

    const nav = document.createElement('nav');
    nav.className = 'dr-nav';
    nav.id = 'site-navigation';
    nav.setAttribute('aria-label', 'Primary navigation');
    nav.dataset.siteNav = '';
    nav.innerHTML = items.map(([label, href, pages]) => {
      const active = pages.includes(page);
      return `<a${active ? ' class="is-active" aria-current="page"' : ''} href="${href}">${label}</a>`;
    }).join('');

    const actions = headerInner.querySelector('.dr-header-actions');
    headerInner.insertBefore(nav, actions || null);

    let toggle = headerInner.querySelector('.dr-menu-button');
    if (!toggle) {
      toggle = document.createElement('button');
      toggle.className = 'dr-menu-button nav-toggle';
      toggle.type = 'button';
      toggle.setAttribute('aria-label', 'Open navigation');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.setAttribute('aria-controls', 'site-navigation');
      toggle.dataset.navToggle = '';
      toggle.innerHTML = '<span aria-hidden="true"></span><span aria-hidden="true"></span><span aria-hidden="true"></span>';
      headerInner.appendChild(toggle);
    }

    toggle.addEventListener('click', () => {
      const open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
    });
    nav.addEventListener('click', (event) => {
      if (!event.target.closest('a')) return;
      nav.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.setAttribute('aria-label', 'Open navigation');
    });
    document.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape') return;
      nav.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.setAttribute('aria-label', 'Open navigation');
    });
  }

  const banner = document.createElement('div');
  banner.className = 'sf-pwa-install-banner';
  banner.setAttribute('role', 'region');
  banner.setAttribute('aria-label', `Install ${brandName}`);
  banner.innerHTML = `<div><strong>Install ${brandName}</strong><small>Open the show platform like an app on mobile or desktop.</small></div><div><button type="button" data-sf-pwa-install>Install</button> <button type="button" data-sf-pwa-dismiss>Later</button></div>`;

  document.addEventListener('DOMContentLoaded', () => {
    ensurePublicNavigation();
    if (publicTheme()) document.body.appendChild(banner);
  });

  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredPrompt = event;
    if (!publicTheme()) return;
    const dismissedAt = Number(localStorage.getItem('desertrio_pwa_dismissed_at') || 0);
    const sevenDays = 7 * 24 * 60 * 60 * 1000;
    if (dismissedAt && Date.now() - dismissedAt < sevenDays) return;
    banner.classList.add('is-visible');
  });

  banner.addEventListener('click', async (event) => {
    if (event.target.matches('[data-sf-pwa-dismiss]')) {
      localStorage.setItem('desertrio_pwa_dismissed_at', String(Date.now()));
      banner.classList.remove('is-visible');
    }
    if (event.target.matches('[data-sf-pwa-install]') && deferredPrompt) {
      deferredPrompt.prompt();
      await deferredPrompt.userChoice.catch(() => null);
      deferredPrompt = null;
      banner.classList.remove('is-visible');
    }
  });

  function formatBytes(bytes){
    const safe = Number(bytes || 0);
    if (!safe) return '0 B';
    const units = ['B','KB','MB','GB'];
    let value = safe;
    let index = 0;
    while (value >= 1024 && index < units.length - 1) { value = value / 1024; index++; }
    return `${value.toFixed(index ? 1 : 0)} ${units[index]}`;
  }

  document.querySelectorAll('[data-sf-upload-zone]').forEach((zone) => {
    const input = zone.querySelector('input[type="file"]');
    const preview = document.querySelector(zone.dataset.previewTarget || '[data-sf-upload-preview]');
    const titleInput = document.querySelector('[name="upload_title"]');
    if (!input) return;
    function showFile(file){
      if (!file || !preview) return;
      preview.classList.add('is-visible');
      const safeName = file.name.replace(/[<>]/g, '');
      preview.innerHTML = `<strong>${safeName}</strong><small>${file.type || 'unknown type'} · ${formatBytes(file.size)}</small>`;
      if (titleInput && !titleInput.value) titleInput.value = file.name.replace(/\.[^.]+$/, '');
      const url = URL.createObjectURL(file);
      if (file.type.startsWith('image/')) preview.insertAdjacentHTML('afterbegin', `<img src="${url}" alt="${safeName} preview">`);
      else if (file.type.startsWith('audio/')) preview.insertAdjacentHTML('beforeend', `<audio controls preload="metadata" src="${url}"></audio>`);
      else if (file.type.startsWith('video/')) preview.insertAdjacentHTML('afterbegin', `<video controls preload="metadata" src="${url}"></video>`);
    }
    zone.addEventListener('click', () => input.click());
    zone.addEventListener('keydown', (event) => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); input.click(); } });
    ['dragenter','dragover'].forEach((name) => zone.addEventListener(name, (event) => { event.preventDefault(); zone.classList.add('is-dragover'); }));
    ['dragleave','drop'].forEach((name) => zone.addEventListener(name, (event) => { event.preventDefault(); zone.classList.remove('is-dragover'); }));
    zone.addEventListener('drop', (event) => {
      const file = event.dataTransfer && event.dataTransfer.files ? event.dataTransfer.files[0] : null;
      if (!file) return;
      const transfer = new DataTransfer();
      transfer.items.add(file);
      input.files = transfer.files;
      showFile(file);
    });
    input.addEventListener('change', () => showFile(input.files && input.files[0]));
  });

  const mini = document.createElement('div');
  mini.className = 'sf-mobile-mini-player';
  mini.innerHTML = `<div class="sf-mobile-mini-mark" aria-hidden="true">DR</div><div><strong data-sf-mini-title>${brandName}</strong><span data-sf-mini-status>Ready to stream</span></div><button type="button" data-sf-mini-open aria-label="Open music player">♪</button>`;
  document.addEventListener('DOMContentLoaded', () => { if (document.querySelector('[data-sf-music-app]')) document.body.appendChild(mini); });
  mini.addEventListener('click', () => { const player = document.querySelector('[data-sf-player]'); if (player) player.scrollIntoView({behavior:'smooth', block:'center'}); });
})();
