(function () {
  'use strict';

  const navToggle = document.querySelector('[data-nav-toggle]');
  const siteNav = document.querySelector('[data-site-nav]');

  function syncNavigationState() {
    if (!navToggle || !siteNav) return;
    const open = siteNav.classList.contains('is-open');
    navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    navToggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
  }

  if (navToggle && siteNav) {
    navToggle.setAttribute('aria-controls', siteNav.id || 'site-navigation');
    if (!siteNav.id) siteNav.id = 'site-navigation';
    navToggle.setAttribute('aria-expanded', 'false');
    navToggle.addEventListener('click', function () { window.requestAnimationFrame(syncNavigationState); });
    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape' || !siteNav.classList.contains('is-open')) return;
      siteNav.classList.remove('is-open');
      syncNavigationState();
      navToggle.focus();
    });
    document.addEventListener('click', function (event) {
      if (!siteNav.classList.contains('is-open')) return;
      if (siteNav.contains(event.target) || navToggle.contains(event.target)) return;
      siteNav.classList.remove('is-open');
      syncNavigationState();
    });
    syncNavigationState();
  }

  const tabs = Array.from(document.querySelectorAll('[data-admin-nav-tab]'));
  tabs.forEach(function (tab, index) {
    const target = tab.getAttribute('data-admin-nav-tab');
    const escapedTarget = window.CSS && CSS.escape ? CSS.escape(target || '') : String(target || '').replace(/"/g, '\\"');
    const panel = document.querySelector('[data-admin-nav-panel="' + escapedTarget + '"]');
    const tabId = 'admin-nav-tab-' + index;
    const panelId = 'admin-nav-panel-' + index;
    tab.id = tabId;
    tab.setAttribute('role', 'tab');
    tab.setAttribute('aria-selected', tab.classList.contains('is-active') ? 'true' : 'false');
    tab.setAttribute('tabindex', tab.classList.contains('is-active') ? '0' : '-1');
    if (panel) {
      panel.id = panelId;
      panel.setAttribute('role', 'tabpanel');
      panel.setAttribute('aria-labelledby', tabId);
      tab.setAttribute('aria-controls', panelId);
    }
    tab.addEventListener('click', function () {
      window.requestAnimationFrame(function () {
        tabs.forEach(function (item) {
          const active = item.classList.contains('is-active');
          item.setAttribute('aria-selected', active ? 'true' : 'false');
          item.setAttribute('tabindex', active ? '0' : '-1');
        });
      });
    });
    tab.addEventListener('keydown', function (event) {
      if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
      event.preventDefault();
      let next = index;
      if (event.key === 'ArrowLeft') next = (index - 1 + tabs.length) % tabs.length;
      if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
      if (event.key === 'Home') next = 0;
      if (event.key === 'End') next = tabs.length - 1;
      tabs[next].focus();
      tabs[next].click();
    });
  });

  document.querySelectorAll('[data-admin-nav-tabs]').forEach(function (tablist) {
    tablist.setAttribute('role', 'tablist');
    tablist.setAttribute('aria-label', 'Admin navigation sections');
  });

  document.querySelectorAll('details.home-user-menu').forEach(function (menu) {
    const summary = menu.querySelector('summary');
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && menu.open) {
        menu.open = false;
        if (summary) summary.focus();
      }
    });
  });

  document.querySelectorAll('img').forEach(function (image) {
    if (!image.hasAttribute('decoding')) image.setAttribute('decoding', 'async');
    const critical = image.closest('.site-global-header, .home-hero, .home-hero-image, [data-eager-image]');
    if (!critical && !image.hasAttribute('loading')) image.setAttribute('loading', 'lazy');
  });

  document.querySelectorAll('iframe').forEach(function (frame) {
    if (!frame.hasAttribute('loading')) frame.setAttribute('loading', 'lazy');
    if (!frame.hasAttribute('title')) frame.setAttribute('title', 'Embedded media');
  });

  document.querySelectorAll('audio, video').forEach(function (media) {
    if (!media.hasAttribute('aria-label')) media.setAttribute('aria-label', media.tagName === 'VIDEO' ? 'Stonefellow video player' : 'Stonefellow audio player');
  });

  const progressSelectors = '[data-player-progress], [data-sf-progress], [data-sf-video-progress-bar], .progress-line > span, .sf-mini-progress > span, .sf-wide-progress > i';
  document.querySelectorAll(progressSelectors).forEach(function (progress) {
    progress.setAttribute('role', 'progressbar');
    progress.setAttribute('aria-label', 'Playback progress');
    progress.setAttribute('aria-valuemin', '0');
    progress.setAttribute('aria-valuemax', '100');
    progress.setAttribute('aria-valuenow', '0');
    progress.classList.add('sf-progress-accessible');
    const syncProgress = function () {
      const width = parseFloat(progress.style.width || '0');
      progress.setAttribute('aria-valuenow', String(Math.max(0, Math.min(100, Math.round(width)))));
    };
    syncProgress();
    new MutationObserver(syncProgress).observe(progress, { attributes: true, attributeFilter: ['style'] });
  });

  const symbolLabels = {
    '↺': 'Restart track',
    '◀': 'Previous track',
    '❚❚': 'Pause playback',
    'Ⅱ': 'Pause playback',
    '▶': 'Play or next track',
    '↻': 'Repeat track',
    '⌘': 'Shuffle playback',
    '♡': 'Save to library',
    '♥': 'Remove from library',
    '＋': 'Add item',
    '×': 'Close'
  };
  document.querySelectorAll('button').forEach(function (button) {
    if (button.hasAttribute('aria-label') || button.hasAttribute('aria-labelledby')) return;
    const text = (button.textContent || '').trim();
    if (symbolLabels[text]) button.setAttribute('aria-label', symbolLabels[text]);
  });

  document.querySelectorAll('input, select, textarea').forEach(function (field) {
    if (field.type === 'hidden' || field.hasAttribute('aria-label') || field.hasAttribute('aria-labelledby')) return;
    if (field.id && document.querySelector('label[for="' + field.id.replace(/"/g, '\\"') + '"]')) return;
    if (field.closest('label')) return;
    const fallback = field.getAttribute('placeholder') || field.getAttribute('name') || field.getAttribute('type') || 'Form field';
    field.setAttribute('aria-label', fallback.replace(/[_-]+/g, ' '));
  });

  document.querySelectorAll('[role="dialog"]').forEach(function (dialog) {
    dialog.addEventListener('keydown', function (event) {
      if (event.key !== 'Tab' || dialog.hidden) return;
      const focusable = Array.from(dialog.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'));
      if (!focusable.length) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });
  });

  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('invalid', function (event) {
      const field = event.target;
      if (field && typeof field.focus === 'function') field.focus();
    }, true);
  });

  document.querySelectorAll('a[target="_blank"]').forEach(function (link) {
    const rel = new Set((link.getAttribute('rel') || '').split(/\s+/).filter(Boolean));
    rel.add('noopener');
    rel.add('noreferrer');
    link.setAttribute('rel', Array.from(rel).join(' '));
  });
}());
