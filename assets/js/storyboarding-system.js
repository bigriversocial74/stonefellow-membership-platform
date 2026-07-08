(function(){
  function idsFor(list){
    return Array.from(list.querySelectorAll('[data-story-id]')).map((item) => item.dataset.storyId).filter(Boolean);
  }

  async function saveOrder(list){
    const url = list.dataset.saveUrl;
    const action = list.dataset.action;
    const csrf = list.dataset.csrf;
    const status = list.parentElement ? list.parentElement.querySelector('[data-story-save-status]') : null;
    if (!url || !action || !csrf) return;

    const body = new FormData();
    body.append('csrf_token', csrf);
    body.append('action', action);
    body.append('order', idsFor(list).join(','));
    if (status) status.textContent = 'Saving order…';

    try {
      const response = await fetch(url, { method: 'POST', body });
      const result = await response.json().catch(() => ({}));
      if (!response.ok || !result.ok) throw new Error(result.message || result.error || 'Order save failed');
      if (status) status.textContent = 'Order saved';
      list.querySelectorAll('[data-story-number]').forEach((label, index) => { label.textContent = String(index + 1); });
    } catch (error) {
      if (status) status.textContent = error.message || 'Order could not be saved';
    }
  }

  document.querySelectorAll('[data-story-drag-list]').forEach((list) => {
    let dragged = null;
    list.querySelectorAll('[data-story-id]').forEach((item) => {
      item.setAttribute('draggable', 'true');
      item.addEventListener('dragstart', () => { dragged = item; item.classList.add('is-dragging'); });
      item.addEventListener('dragend', () => { item.classList.remove('is-dragging'); dragged = null; saveOrder(list); });
      item.addEventListener('dragover', (event) => {
        event.preventDefault();
        if (!dragged || dragged === item) return;
        const rect = item.getBoundingClientRect();
        const before = (event.clientY - rect.top) < rect.height / 2;
        list.insertBefore(dragged, before ? item : item.nextSibling);
      });
    });
  });

  document.querySelectorAll('[data-story-modal-open]').forEach((button) => {
    button.addEventListener('click', () => {
      const id = button.getAttribute('data-story-modal-open');
      const modal = id ? document.getElementById(id) : null;
      if (!modal) return;
      if (typeof modal.showModal === 'function') modal.showModal();
      else modal.setAttribute('open', 'open');
    });
  });

  document.querySelectorAll('.sf-story-v1-modal').forEach((modal) => {
    modal.addEventListener('click', (event) => {
      const rect = modal.getBoundingClientRect();
      const inDialog = event.clientX >= rect.left && event.clientX <= rect.right && event.clientY >= rect.top && event.clientY <= rect.bottom;
      if (!inDialog) modal.close ? modal.close() : modal.removeAttribute('open');
    });
  });
})();
