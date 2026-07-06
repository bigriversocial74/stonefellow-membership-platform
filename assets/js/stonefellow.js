(function(){
  const toggle = document.querySelector('[data-nav-toggle]');
  const nav = document.querySelector('[data-site-nav]');
  if (toggle && nav) {
    toggle.addEventListener('click', () => nav.classList.toggle('is-open'));
  }

  document.querySelectorAll('[data-plan-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-plan-toggle]').forEach(b => b.classList.remove('is-active'));
      button.classList.add('is-active');
    });
  });

  const musicApp = document.querySelector('[data-music-app]');
  if (!musicApp) return;

  const audio = musicApp.querySelector('[data-main-audio]');
  const titleEl = musicApp.querySelector('[data-player-title]');
  const artistEl = musicApp.querySelector('[data-player-artist]');
  const progressEl = musicApp.querySelector('[data-player-progress]');
  const currentEl = musicApp.querySelector('[data-player-current]');
  const limitEl = musicApp.querySelector('[data-player-limit]');
  const coverEl = musicApp.querySelector('[data-player-cover]');
  let activeButton = null;
  let activeRow = null;
  let previewLimit = 30;

  function formatTime(seconds) {
    const safe = Math.max(0, Math.floor(seconds || 0));
    const m = Math.floor(safe / 60);
    const s = String(safe % 60).padStart(2, '0');
    return `${m}:${s}`;
  }

  function setButtonStates(isPlaying) {
    document.querySelectorAll('[data-play-song]').forEach(button => {
      button.textContent = '▶';
      button.setAttribute('aria-label', 'Play preview');
    });
    if (activeButton) {
      activeButton.textContent = isPlaying ? '❚❚' : '▶';
      activeButton.setAttribute('aria-label', isPlaying ? 'Pause preview' : 'Play preview');
    }
    musicApp.classList.toggle('is-playing', isPlaying);
  }

  function loadSong(button) {
    const src = button.dataset.src;
    if (!src) return;
    activeButton = button;
    activeRow = button.closest('[data-song-row]');
    document.querySelectorAll('[data-song-row]').forEach(row => row.classList.remove('is-current'));
    if (activeRow) activeRow.classList.add('is-current');

    previewLimit = parseInt(button.dataset.previewSeconds || '30', 10);
    audio.src = src;
    audio.currentTime = 0;
    if (titleEl) titleEl.textContent = button.dataset.title || 'Stonefellow Preview';
    if (artistEl) artistEl.textContent = button.dataset.artist || 'Stonefellow';
    if (coverEl && button.dataset.cover) coverEl.src = button.dataset.cover;
    if (limitEl) limitEl.textContent = formatTime(previewLimit);
    if (currentEl) currentEl.textContent = '0:00';
    if (progressEl) progressEl.style.width = '0%';
  }

  document.querySelectorAll('[data-play-song]').forEach(button => {
    button.addEventListener('click', async () => {
      const isSame = activeButton === button && audio.src;
      if (!isSame) loadSong(button);
      if (!audio.paused && isSame) {
        audio.pause();
        setButtonStates(false);
        return;
      }
      try {
        await audio.play();
        setButtonStates(true);
      } catch (error) {
        console.warn('Audio preview could not play:', error);
        setButtonStates(false);
      }
    });
  });

  audio.addEventListener('timeupdate', () => {
    if (audio.currentTime >= previewLimit) {
      audio.pause();
      audio.currentTime = previewLimit;
      setButtonStates(false);
    }
    const pct = previewLimit ? Math.min(100, (audio.currentTime / previewLimit) * 100) : 0;
    if (progressEl) progressEl.style.width = `${pct}%`;
    if (currentEl) currentEl.textContent = formatTime(audio.currentTime);
  });

  audio.addEventListener('ended', () => setButtonStates(false));
  audio.addEventListener('pause', () => setButtonStates(false));

  document.querySelectorAll('[data-save-song]').forEach(button => {
    const compact = button.classList.contains('library-btn');
    button.addEventListener('click', () => {
      button.classList.toggle('is-saved');
      const saved = button.classList.contains('is-saved');
      button.textContent = compact ? (saved ? '♥' : '♡') : (saved ? '♥ Saved' : '♡ Save');
      button.setAttribute('aria-label', saved ? 'Saved to demo library' : 'Save requires login');
    });
  });
})();

(function(){
  const app = document.querySelector('[data-sf-music-app]');
  if (!app) return;

  const audio = app.querySelector('[data-sf-audio]');
  const player = app.querySelector('[data-sf-player]');
  const titleEls = app.querySelectorAll('[data-sf-player-title]');
  const artistEls = app.querySelectorAll('[data-sf-player-artist]');
  const coverEls = app.querySelectorAll('[data-sf-player-cover]');
  const linkEls = app.querySelectorAll('[data-sf-player-link]');
  const currentEls = app.querySelectorAll('[data-sf-current]');
  const durationEls = app.querySelectorAll('[data-sf-duration]');
  const progressEls = app.querySelectorAll('[data-sf-progress]');
  const playButtons = app.querySelectorAll('[data-sf-play-song]');
  const toggleButtons = app.querySelectorAll('[data-sf-player-toggle]');
  const tracks = Array.isArray(window.STONEFELLOW_TRACKS) ? window.STONEFELLOW_TRACKS : [];
  let activeTrack = tracks[0] || null;
  let activeButton = null;
  let activeIndex = 0;
  let limit = 30;

  function formatTime(seconds){
    const safe = Math.max(0, Math.floor(seconds || 0));
    const minutes = Math.floor(safe / 60);
    const secs = String(safe % 60).padStart(2,'0');
    return `${minutes}:${secs}`;
  }

  function updateMeta(track){
    if (!track) return;
    activeTrack = track;
    titleEls.forEach(el => el.textContent = track.title || 'Stonefellow');
    artistEls.forEach(el => el.textContent = track.artist || 'Stonefellow');
    coverEls.forEach(el => { if (track.cover) el.src = track.cover; });
    linkEls.forEach(el => { if (track.url) el.href = track.url; });
    durationEls.forEach(el => el.textContent = track.duration || '0:30');
  }

  function setPlayingState(isPlaying){
    app.classList.toggle('is-sf-playing', isPlaying);
    playButtons.forEach(button => {
      button.classList.remove('is-playing');
      if (button.classList.contains('sf-row-play')) {
        const row = button.closest('[data-sf-track-row]');
        if (!row || !row.classList.contains('is-current')) {
          button.textContent = button.dataset.trackNumber || button.textContent.replace('▮▮','') || '▶';
        }
      } else if (!button.classList.contains('sf-album-play') && !button.classList.contains('sf-track-play')) {
        button.textContent = '▶';
      }
    });
    toggleButtons.forEach(button => button.textContent = isPlaying ? 'Ⅱ' : '▶');
    if (activeButton) {
      activeButton.classList.add('is-playing');
      if (activeButton.classList.contains('sf-album-play')) activeButton.textContent = isPlaying ? 'Ⅱ Playing' : '▶ Play';
      else if (activeButton.classList.contains('sf-track-play')) activeButton.textContent = isPlaying ? 'Ⅱ' : '▶';
      else if (activeButton.classList.contains('sf-row-play')) activeButton.textContent = isPlaying ? '▮▮' : '▶';
      else activeButton.textContent = isPlaying ? 'Ⅱ' : '▶';
    }
  }

  function trackFromButton(button){
    return {
      title: button.dataset.title || 'Stonefellow',
      artist: button.dataset.artist || 'Stonefellow',
      src: button.dataset.src || '',
      cover: button.dataset.cover || '',
      url: button.dataset.url || '#',
      duration: button.dataset.duration || '0:30',
      id: button.dataset.songId || button.dataset.id || ''
    };
  }

  function markCurrent(button){
    app.querySelectorAll('[data-sf-track-row]').forEach(row => row.classList.remove('is-current'));
    const row = button ? button.closest('[data-sf-track-row]') : null;
    if (row) row.classList.add('is-current');
  }

  function loadTrack(track, button){
    if (!track || !track.src || !audio) return false;
    activeButton = button || activeButton;
    activeIndex = Math.max(0, tracks.findIndex(item => item.src === track.src));
    updateMeta(track);
    markCurrent(button);
    audio.src = track.src;
    audio.dataset.songId = track.id || '';
    audio.currentTime = 0;
    progressEls.forEach(el => el.style.width = '0%');
    currentEls.forEach(el => el.textContent = '0:00');
    return true;
  }

  async function playTrack(track, button){
    if (!loadTrack(track, button)) return;
    try {
      await audio.play();
      setPlayingState(true);
    } catch (error) {
      console.warn('Stonefellow audio could not play:', error);
      setPlayingState(false);
    }
  }

  playButtons.forEach((button, index) => {
    if (button.classList.contains('sf-row-play')) button.dataset.trackNumber = button.textContent.trim();
    button.addEventListener('click', async () => {
      const track = trackFromButton(button);
      const same = activeTrack && activeTrack.src === track.src && audio && audio.src;
      if (same && !audio.paused) {
        audio.pause();
        setPlayingState(false);
        return;
      }
      activeIndex = index;
      await playTrack(track, button);
    });
  });

  toggleButtons.forEach(button => {
    button.addEventListener('click', async () => {
      if (!audio) return;
      if (!audio.src) {
        const firstButton = playButtons[0];
        const firstTrack = firstButton ? trackFromButton(firstButton) : activeTrack;
        await playTrack(firstTrack, firstButton);
        return;
      }
      if (audio.paused) {
        try { await audio.play(); setPlayingState(true); } catch(error) { setPlayingState(false); }
      } else {
        audio.pause();
        setPlayingState(false);
      }
    });
  });

  function playByOffset(offset){
    if (!tracks.length) return;
    activeIndex = (activeIndex + offset + tracks.length) % tracks.length;
    const track = tracks[activeIndex];
    const matchingButton = Array.from(playButtons).find(button => button.dataset.src === track.src) || playButtons[0];
    playTrack(track, matchingButton);
  }

  app.querySelectorAll('[data-sf-prev]').forEach(button => button.addEventListener('click', () => playByOffset(-1)));
  app.querySelectorAll('[data-sf-next]').forEach(button => button.addEventListener('click', () => playByOffset(1)));

  app.querySelectorAll('[data-sf-save]').forEach(button => {
    button.addEventListener('click', () => {
      button.classList.toggle('is-saved');
      button.textContent = button.classList.contains('is-saved') ? '♥' : '♡';
    });
  });

  if (audio) {
    audio.addEventListener('timeupdate', () => {
      const duration = Math.min(audio.duration || limit, limit);
      if (audio.currentTime >= limit) {
        audio.pause();
        audio.currentTime = limit;
        setPlayingState(false);
      }
      const pct = duration ? Math.min(100, (audio.currentTime / duration) * 100) : 0;
      progressEls.forEach(el => el.style.width = `${pct}%`);
      currentEls.forEach(el => el.textContent = formatTime(audio.currentTime));
    });
    audio.addEventListener('pause', () => setPlayingState(false));
    audio.addEventListener('ended', () => setPlayingState(false));
  }

  if (tracks[0]) updateMeta(tracks[0]);
})();


(function(){
  const app = document.querySelector('[data-sf-music-app]');
  if (!app) return;
  const audio = app.querySelector('[data-sf-audio]');
  if (!audio) return;

  let lastProgressSent = 0;
  let lastPosition = 0;

  function percentComplete(){
    const duration = Math.min(audio.duration || 30, 30);
    return duration ? Math.min(100, (audio.currentTime / duration) * 100) : 0;
  }

  function sendAudioEvent(eventType){
    const songId = parseInt(audio.dataset.songId || '0', 10);
    if (!songId || !window.fetch) return;
    const position = Math.max(0, Math.floor(audio.currentTime || 0));
    const secondsPlayed = Math.max(0, position - lastPosition);
    lastPosition = position;
    fetch('api/audio-track.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({
        song_id: songId,
        event_type: eventType,
        position_seconds: position,
        seconds_played: secondsPlayed,
        percent_complete: percentComplete(),
        source_page: window.location.pathname.split('/').pop() || 'player.php'
      })
    }).catch(() => {});
  }

  audio.addEventListener('play', () => sendAudioEvent('play'));
  audio.addEventListener('pause', () => {
    if ((audio.currentTime || 0) >= 29.5) sendAudioEvent('complete');
    else sendAudioEvent('pause');
  });
  audio.addEventListener('seeked', () => sendAudioEvent('seek'));
  audio.addEventListener('ended', () => sendAudioEvent('complete'));
  audio.addEventListener('timeupdate', () => {
    const now = Date.now();
    if (now - lastProgressSent > 15000) {
      lastProgressSent = now;
      sendAudioEvent('progress');
    }
  });
})();

(function(){
  const page = document.querySelector('[data-sf-video-page]');
  if (!page) return;
  const video = page.querySelector('[data-sf-video-player]');
  if (!video) return;

  const videoId = parseInt(page.dataset.videoId || '0', 10);
  const episodeId = parseInt(page.dataset.episodeId || '0', 10);
  const storageKey = `stonefellow.video.${videoId}.position`;
  const resumeLabel = page.querySelector('[data-sf-resume-label]');
  const progressBar = page.querySelector('[data-sf-video-progress-bar]');
  const saveState = page.querySelector('[data-sf-video-save-state]');
  let lastProgressSent = 0;
  let lastPosition = 0;
  let resumeApplied = false;

  function formatTime(seconds){
    const safe = Math.max(0, Math.floor(seconds || 0));
    const m = Math.floor(safe / 60);
    const s = String(safe % 60).padStart(2,'0');
    return `${m}:${s}`;
  }

  function percentComplete(){
    return video.duration ? Math.min(100, (video.currentTime / video.duration) * 100) : 0;
  }

  function updateLocalProgress(){
    const position = Math.max(0, Math.floor(video.currentTime || 0));
    const pct = percentComplete();
    try { localStorage.setItem(storageKey, String(position)); } catch(e) {}
    if (resumeLabel) resumeLabel.textContent = `${formatTime(position)} saved`;
    if (progressBar) progressBar.style.width = `${pct}%`;
    if (saveState) saveState.textContent = `Saved ${formatTime(position)}`;
  }

  function sendVideoEvent(eventType){
    if (!videoId || !window.fetch) return;
    const position = Math.max(0, Math.floor(video.currentTime || 0));
    const secondsWatched = Math.max(0, position - lastPosition);
    lastPosition = position;
    fetch('api/video-track.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({
        video_id: videoId,
        episode_id: episodeId,
        event_type: eventType,
        position_seconds: position,
        seconds_watched: secondsWatched,
        percent_complete: percentComplete(),
        source_page: window.location.pathname.split('/').pop() || 'watch.php'
      })
    }).catch(() => {});
  }

  video.addEventListener('loadedmetadata', () => {
    if (resumeApplied) return;
    resumeApplied = true;
    let stored = 0;
    try { stored = parseInt(localStorage.getItem(storageKey) || '0', 10); } catch(e) {}
    if (stored > 5 && stored < (video.duration || stored + 1) - 5) {
      video.currentTime = stored;
    }
    updateLocalProgress();
  });

  video.addEventListener('play', () => sendVideoEvent('play'));
  video.addEventListener('pause', () => sendVideoEvent('pause'));
  video.addEventListener('seeked', () => sendVideoEvent('seek'));
  video.addEventListener('ended', () => {
    updateLocalProgress();
    sendVideoEvent('complete');
  });
  video.addEventListener('timeupdate', () => {
    updateLocalProgress();
    const now = Date.now();
    if (now - lastProgressSent > 15000) {
      lastProgressSent = now;
      sendVideoEvent('progress');
    }
  });
})();

(function(){
  const form = document.querySelector('[data-sf-playlist-form]');
  const message = document.querySelector('[data-sf-playlist-message]');
  if (form) {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const data = new FormData(form);
      if (message) message.textContent = 'Saving playlist...';
      try {
        const response = await fetch('api/playlist.php', {
          method: 'POST',
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify({
            action: 'create',
            title: data.get('title'),
            description: data.get('description')
          })
        });
        const result = await response.json();
        if (message) message.textContent = result.ok ? `Created: ${result.title}` : (result.message || result.error || 'Unable to save playlist');
        if (result.ok) form.reset();
      } catch (error) {
        if (message) message.textContent = 'Unable to reach playlist API.';
      }
    });
  }

  document.querySelectorAll('[data-sf-playlist-add]').forEach((button) => {
    button.addEventListener('click', async () => {
      button.textContent = 'Sign in required';
      button.disabled = true;
    });
  });
})();
