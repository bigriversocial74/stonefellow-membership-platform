(function(){
  const pwaConfig = window.STONEFELLOW_PWA || {};
  let deferredPrompt = null;
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register(pwaConfig.serviceWorker || 'service-worker.js').catch((error) => console.warn('Stonefellow service worker registration failed:', error));
    });
  }

  const banner = document.createElement('div');
  banner.className = 'sf-pwa-install-banner';
  banner.innerHTML = '<div><strong>Install Stonefellow</strong><small>Open the streaming platform like an app on mobile or desktop.</small></div><div><button type="button" data-sf-pwa-install>Install</button> <button type="button" data-sf-pwa-dismiss>Later</button></div>';
  document.addEventListener('DOMContentLoaded', () => document.body.appendChild(banner));
  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredPrompt = event;
    banner.classList.add('is-visible');
  });
  banner.addEventListener('click', async (event) => {
    if (event.target.matches('[data-sf-pwa-dismiss]')) banner.classList.remove('is-visible');
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
  mini.innerHTML = '<img src="assets/images/brand/logo-mark.png" alt="Stonefellow"><div><strong data-sf-mini-title>Stonefellow</strong><span data-sf-mini-status>Ready to stream</span></div><button type="button" data-sf-mini-open>♪</button>';
  document.addEventListener('DOMContentLoaded', () => { if (document.querySelector('[data-sf-music-app]')) document.body.appendChild(mini); });
  mini.addEventListener('click', () => { const player = document.querySelector('[data-sf-player]'); if (player) player.scrollIntoView({behavior:'smooth', block:'center'}); });
})();
