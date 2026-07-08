(function(){
  async function postJson(url, payload){
    const response = await fetch(url, {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify(payload)
    });
    let result = {};
    try { result = await response.json(); } catch(error) {}
    if (!response.ok || !result.ok) {
      throw new Error(result.message || result.error || 'Request failed');
    }
    return result;
  }

  function setButtonState(button, text, disabled){
    if (!button) return;
    button.textContent = text;
    button.disabled = !!disabled;
  }

  document.querySelectorAll('[data-sf-library-save]').forEach((button) => {
    if (button.dataset.sfRuntimeBound === '1') return;
    button.dataset.sfRuntimeBound = '1';
    const originalText = button.textContent.trim() || 'Save';
    button.addEventListener('click', async () => {
      const contentId = parseInt(button.dataset.contentId || '0', 10);
      const contentType = button.dataset.contentType || '';
      const status = button.dataset.libraryStatus || 'saved';
      if (!contentId || !contentType) return;
      setButtonState(button, 'Saving...', true);
      try {
        await postJson('api/library.php', {
          action: 'save',
          content_type: contentType,
          content_id: contentId,
          library_status: status
        });
        setButtonState(button, status === 'watchlist' ? '✓ Watchlist' : '✓ Saved', false);
      } catch (error) {
        setButtonState(button, error.message || 'Unable to save', false);
        window.setTimeout(() => setButtonState(button, originalText, false), 2400);
      }
    });
  });

  document.querySelectorAll('[data-sf-save]').forEach((button) => {
    if (button.dataset.sfRuntimeBound === '1') return;
    button.dataset.sfRuntimeBound = '1';
    button.addEventListener('click', async () => {
      const app = button.closest('[data-sf-music-app]') || document.querySelector('[data-sf-music-app]');
      const audio = app ? app.querySelector('[data-sf-audio]') : null;
      let songId = audio ? parseInt(audio.dataset.songId || '0', 10) : 0;
      if (!songId && Array.isArray(window.STONEFELLOW_TRACKS) && window.STONEFELLOW_TRACKS[0]) {
        songId = parseInt(window.STONEFELLOW_TRACKS[0].id || '0', 10);
      }
      if (!songId) return;
      const original = button.textContent;
      button.textContent = '…';
      try {
        await postJson('api/library.php', {
          action: 'save',
          content_type: 'song',
          content_id: songId,
          library_status: 'liked'
        });
        button.textContent = '♥';
        button.classList.add('is-saved');
      } catch (error) {
        button.textContent = original || '♡';
      }
    });
  });

  document.querySelectorAll('[data-sf-playlist-add]').forEach((button) => {
    if (button.dataset.sfRuntimeBound === '1') return;
    button.dataset.sfRuntimeBound = '1';
    button.addEventListener('click', async (event) => {
      event.preventDefault();
      event.stopImmediatePropagation();
      const songId = parseInt(button.dataset.songId || '0', 10);
      const playlistId = parseInt(button.dataset.playlistId || '0', 10);
      if (!songId) return;
      const originalText = button.textContent.trim() || '＋ Add';
      setButtonState(button, 'Saving...', true);
      try {
        await postJson('api/playlist.php', {
          action: 'add_song',
          song_id: songId,
          playlist_id: playlistId
        });
        setButtonState(button, '✓ Added', false);
      } catch (error) {
        setButtonState(button, error.message || 'Unable to add', false);
        window.setTimeout(() => setButtonState(button, originalText, false), 2600);
      }
    }, true);
  });
})();
