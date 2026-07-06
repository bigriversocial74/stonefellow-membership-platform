<?php
$featuredCards = [
  [
    'eyebrow' => 'Pilot Episode',
    'title' => 'First to Fall',
    'text' => 'A forgotten band gets one last shot, but the past refuses to stay quiet.',
    'image' => 'images/episodes/episode-01.png',
    'url' => 'episodes.php',
  ],
  [
    'eyebrow' => 'Official Soundtrack',
    'title' => 'The Road Is Calling',
    'text' => 'Stream the songs that carry the story from the stage to the road.',
    'image' => 'images/music/soundtrack-cover.png',
    'url' => 'music.php',
  ],
  [
    'eyebrow' => 'Limited Drop',
    'title' => 'Wear the Sound',
    'text' => 'Tour-inspired gear, pirate-rock marks, posters, picks, and launch bundles.',
    'image' => 'images/merch/merch-hero.png',
    'url' => 'merch.php',
  ],
];

$episodes = [
  [
    'number' => 'S1:E1',
    'title' => 'First to Fall',
    'slug' => 'first-to-fall',
    'runtime' => '48 min',
    'description' => 'Stonefellow reunites for a coastal comeback show, but a buried betrayal threatens to break the band before the first encore.',
    'image' => 'images/episodes/episode-01.png',
    'badge' => 'Pilot',
  ],
  [
    'number' => 'S1:E2',
    'title' => 'Riptide Hearts',
    'slug' => 'riptide-hearts',
    'runtime' => '44 min',
    'description' => 'A new song unlocks an old secret, and the band must decide whether fame is worth reopening the wound.',
    'image' => 'images/episodes/episode-02.png',
    'badge' => 'Coming Soon',
  ],
  [
    'number' => 'S1:E3',
    'title' => 'The Long Road Home',
    'slug' => 'the-long-road-home',
    'runtime' => '46 min',
    'description' => 'A dangerous offer puts the band on the road, forcing every member to choose between loyalty and escape.',
    'image' => 'images/episodes/episode-03.png',
    'badge' => 'Subscriber',
  ],
];

$songs = [
  ['track' => '01', 'title' => 'Born to Burn', 'duration' => '3:42', 'episode' => 'First to Fall'],
  ['track' => '02', 'title' => 'Black Sail Mercy', 'duration' => '4:11', 'episode' => 'First to Fall'],
  ['track' => '03', 'title' => 'Riptide Hearts', 'duration' => '3:58', 'episode' => 'Riptide Hearts'],
  ['track' => '04', 'title' => 'The Long Road Home', 'duration' => '5:02', 'episode' => 'The Long Road Home'],
  ['track' => '05', 'title' => 'No Kings, No Chains', 'duration' => '3:35', 'episode' => 'Live Session'],
];

$castMembers = [
  ['name' => 'Jax Stonefellow', 'role' => 'Guitar / Singer', 'quote' => 'Every song is either a confession or a threat.'],
  ['name' => 'Cash Hawthorne', 'role' => 'Bass', 'quote' => 'The low end remembers what everyone else forgot.'],
  ['name' => 'Violet Graves', 'role' => 'Keys', 'quote' => 'Beauty sounds better when it is dangerous.'],
  ['name' => 'Sawyer Creed', 'role' => 'Lead Guitar', 'quote' => 'If the truth has a solo, I am playing it loud.'],
  ['name' => 'Luke Mercer', 'role' => 'Drums', 'quote' => 'The beat does not lie. People do.'],
];

$plans = [
  [
    'name' => 'Monthly Access',
    'price' => '$7.99',
    'period' => '/ month',
    'featured' => false,
    'features' => ['Watch all released episodes', 'Stream the soundtrack', 'Behind-the-scenes clips', 'Cancel anytime'],
  ],
  [
    'name' => 'Annual Access',
    'price' => '$79.99',
    'period' => '/ year',
    'featured' => true,
    'features' => ['Everything in Monthly', 'Early episode access', 'Live session archive', 'Subscriber merch drops'],
  ],
  [
    'name' => 'Founding Fan',
    'price' => '$149.99',
    'period' => '/ year',
    'featured' => false,
    'features' => ['Everything in Annual', 'VIP pilot bundle access', 'Name in supporter wall', 'Limited launch collectibles'],
  ],
];

$products = [
  [
    'id' => 1,
    'name' => 'Stonefellow Crest Tee',
    'slug' => 'stonefellow-crest-tee',
    'price' => '$34',
    'price_cents' => 3400,
    'badge' => 'Featured',
    'category' => 'Apparel',
    'image' => 'images/merch/merch-logo-system.png',
    'description' => 'Soft black tour tee with the Stonefellow crest across the chest. Built for show nights, road trips, and founding fans.',
    'options' => ['S','M','L','XL','2XL'],
  ],
  [
    'id' => 2,
    'name' => 'Pirate Rock Hoodie',
    'slug' => 'pirate-rock-hoodie',
    'price' => '$72',
    'price_cents' => 7200,
    'badge' => 'Limited',
    'category' => 'Apparel',
    'image' => 'images/brand/logo-alt.png',
    'description' => 'Heavyweight hoodie with the pirate-rock skull mark, worn-in gold ink, and backstage pass attitude.',
    'options' => ['S','M','L','XL','2XL'],
  ],
  [
    'id' => 3,
    'name' => 'Live Sessions Poster',
    'slug' => 'live-sessions-poster',
    'price' => '$28',
    'price_cents' => 2800,
    'badge' => 'New',
    'category' => 'Posters',
    'image' => 'images/episodes/episode-02.png',
    'description' => 'Large-format live session poster with smoky stage lighting and Stonefellow tour-style typography.',
    'options' => ['18x24','24x36'],
  ],
  [
    'id' => 4,
    'name' => 'Official Soundtrack Vinyl',
    'slug' => 'official-soundtrack-vinyl',
    'price' => '$39',
    'price_cents' => 3900,
    'badge' => 'Preorder',
    'category' => 'Music',
    'image' => 'images/music/soundtrack-cover.png',
    'description' => 'Collector vinyl edition of The Road Is Calling with black-and-gold jacket art and digital download code.',
    'options' => ['Black Vinyl','Gold Vinyl'],
  ],
  [
    'id' => 5,
    'name' => 'SF Guitar Pick Set',
    'slug' => 'sf-guitar-pick-set',
    'price' => '$16',
    'price_cents' => 1600,
    'badge' => 'Accessory',
    'category' => 'Accessories',
    'image' => 'images/brand/logo-mark.png',
    'description' => 'Six-pick set with crest, sword, and pirate-rock marks packaged like backstage memorabilia.',
    'options' => ['Standard'],
  ],
  [
    'id' => 6,
    'name' => 'Pilot Launch Bundle',
    'slug' => 'pilot-launch-bundle',
    'price' => '$99',
    'price_cents' => 9900,
    'badge' => 'Bundle',
    'category' => 'Bundles',
    'image' => 'images/merch/merch-hero.png',
    'description' => 'Tee, poster, pick set, and soundtrack preorder bundled for the pilot launch window.',
    'options' => ['Standard Bundle'],
  ],
];

$catalogSongs = [
  [
    'id' => 1,
    'track' => '01',
    'title' => 'Born to Burn',
    'slug' => 'born-to-burn',
    'artist' => 'Stonefellow',
    'duration' => '3:48',
    'duration_seconds' => 228,
    'episode' => 'Episode 1 — First to Fall',
    'episode_short' => 'Ep 1 · Pilot',
    'cover' => 'images/music/soundtrack-cover.png',
    'preview_src' => 'audio/previews/born-to-burn-preview.wav',
    'full_src' => 'audio/full/born-to-burn.wav',
    'access' => 'subscriber',
    'preview_seconds' => 30,
    'is_featured' => true,
  ],
  [
    'id' => 2,
    'track' => '02',
    'title' => 'Blackout in the Rearview',
    'slug' => 'blackout-in-the-rearview',
    'artist' => 'Stonefellow',
    'duration' => '3:35',
    'duration_seconds' => 215,
    'episode' => 'Episode 1 — First to Fall',
    'episode_short' => 'Ep 1 · Pilot',
    'cover' => 'images/music/music-episode-01.png',
    'preview_src' => 'audio/previews/blackout-in-the-rearview-preview.wav',
    'full_src' => 'audio/full/blackout-in-the-rearview.wav',
    'access' => 'subscriber',
    'preview_seconds' => 30,
    'is_featured' => false,
  ],
  [
    'id' => 3,
    'track' => '03',
    'title' => 'Tearing Down the Walls',
    'slug' => 'tearing-down-the-walls',
    'artist' => 'Stonefellow',
    'duration' => '4:02',
    'duration_seconds' => 242,
    'episode' => 'Episode 2 — Blackout',
    'episode_short' => 'Ep 2 · Blackout',
    'cover' => 'images/music/music-episode-02.png',
    'preview_src' => 'audio/previews/tearing-down-the-walls-preview.wav',
    'full_src' => 'audio/full/tearing-down-the-walls.wav',
    'access' => 'subscriber',
    'preview_seconds' => 30,
    'is_featured' => false,
  ],
  [
    'id' => 4,
    'track' => '04',
    'title' => 'Heart of a Loaded Gun',
    'slug' => 'heart-of-a-loaded-gun',
    'artist' => 'Stonefellow',
    'duration' => '3:57',
    'duration_seconds' => 237,
    'episode' => 'Episode 3 — Ghosts on the Coast',
    'episode_short' => 'Ep 3 · Ghosts',
    'cover' => 'images/music/music-episode-03.png',
    'preview_src' => 'audio/previews/heart-of-a-loaded-gun-preview.wav',
    'full_src' => 'audio/full/heart-of-a-loaded-gun.wav',
    'access' => 'subscriber',
    'preview_seconds' => 30,
    'is_featured' => false,
  ],
  [
    'id' => 5,
    'track' => '05',
    'title' => 'Saint or Sinner',
    'slug' => 'saint-or-sinner',
    'artist' => 'Stonefellow',
    'duration' => '3:41',
    'duration_seconds' => 221,
    'episode' => 'Episode 4 — Burn',
    'episode_short' => 'Ep 4 · Burn',
    'cover' => 'images/music/music-episode-04.png',
    'preview_src' => 'audio/previews/saint-or-sinner-preview.wav',
    'full_src' => 'audio/full/saint-or-sinner.wav',
    'access' => 'subscriber',
    'preview_seconds' => 30,
    'is_featured' => false,
  ],
  [
    'id' => 6,
    'track' => '06',
    'title' => 'Riptide',
    'slug' => 'riptide',
    'artist' => 'Stonefellow',
    'duration' => '4:12',
    'duration_seconds' => 252,
    'episode' => 'Episode 5 — Nothing Left',
    'episode_short' => 'Ep 5 · Nothing Left',
    'cover' => 'images/music/music-episode-05.png',
    'preview_src' => 'audio/previews/riptide-preview.wav',
    'full_src' => 'audio/full/riptide.wav',
    'access' => 'subscriber',
    'preview_seconds' => 30,
    'is_featured' => false,
  ],
  [
    'id' => 7,
    'track' => '07',
    'title' => 'Long Road Home',
    'slug' => 'long-road-home',
    'artist' => 'Stonefellow',
    'duration' => '3:56',
    'duration_seconds' => 236,
    'episode' => 'Live Session',
    'episode_short' => 'Live from the Saloon',
    'cover' => 'images/music/music-live-02.png',
    'preview_src' => 'audio/previews/long-road-home-preview.wav',
    'full_src' => 'audio/full/long-road-home.wav',
    'access' => 'subscriber',
    'preview_seconds' => 30,
    'is_featured' => false,
  ],
  [
    'id' => 8,
    'track' => '08',
    'title' => 'Burn It Down',
    'slug' => 'burn-it-down',
    'artist' => 'Stonefellow',
    'duration' => '4:25',
    'duration_seconds' => 265,
    'episode' => 'Live Session',
    'episode_short' => 'Live from the Saloon',
    'cover' => 'images/music/music-live-04.png',
    'preview_src' => 'audio/previews/burn-it-down-preview.wav',
    'full_src' => 'audio/full/burn-it-down.wav',
    'access' => 'subscriber',
    'preview_seconds' => 30,
    'is_featured' => false,
  ],
  [
    'id' => 9,
    'track' => '09',
    'title' => 'Nothing Left',
    'slug' => 'nothing-left',
    'artist' => 'Stonefellow',
    'duration' => '3:44',
    'duration_seconds' => 224,
    'episode' => 'Episode 5 — Nothing Left',
    'episode_short' => 'Ep 5 · Nothing Left',
    'cover' => 'images/music/music-live-05.png',
    'preview_src' => 'audio/previews/nothing-left-preview.wav',
    'full_src' => 'audio/full/nothing-left.wav',
    'access' => 'subscriber',
    'preview_seconds' => 30,
    'is_featured' => false,
  ],
  [
    'id' => 10,
    'track' => '10',
    'title' => 'The Road Is Calling',
    'slug' => 'the-road-is-calling',
    'artist' => 'Stonefellow',
    'duration' => '3:49',
    'duration_seconds' => 229,
    'episode' => 'Album Title Track',
    'episode_short' => 'Title Track',
    'cover' => 'images/music/soundtrack-cover.png',
    'preview_src' => 'audio/previews/the-road-is-calling-preview.wav',
    'full_src' => 'audio/full/the-road-is-calling.wav',
    'access' => 'subscriber',
    'preview_seconds' => 30,
    'is_featured' => false,
  ],
];

$musicAlbum = [
  'title' => 'The Road Is Calling',
  'slug' => 'the-road-is-calling',
  'artist' => 'Stonefellow',
  'year' => '2024',
  'cover' => 'images/music/soundtrack-cover.png',
  'description' => 'A cinematic desert-rock soundtrack built for road scenes, bad decisions, redemption arcs, and the Stonefellow series universe.',
];

$musicPlaylists = [
  [
    'title' => 'Rock & Roll Forever',
    'songs' => '26 songs',
    'cover' => 'images/cast/band-portraits.png',
    'url' => 'album.php?slug=the-road-is-calling',
  ],
  [
    'title' => 'Late Night Drives',
    'songs' => '18 songs',
    'cover' => 'images/music/soundtrack-cover.png',
    'url' => 'album.php?slug=the-road-is-calling',
  ],
  [
    'title' => 'Stonefellow Live',
    'songs' => '18 songs',
    'cover' => 'images/music/music-live-02.png',
    'url' => 'album.php?slug=the-road-is-calling',
  ],
  [
    'title' => 'My Favorites',
    'songs' => '89 songs',
    'cover' => 'images/music/music-page.png',
    'url' => 'album.php?slug=the-road-is-calling',
  ],
];

if (!function_exists('sf_song_by_slug')) {
  function sf_song_by_slug(array $songs, string $slug): ?array {
    foreach ($songs as $song) {
      if (($song['slug'] ?? '') === $slug) {
        return $song;
      }
    }
    return $songs[0] ?? null;
  }
}

if (!function_exists('sf_music_minutes')) {
  function sf_music_minutes(array $songs): int {
    $seconds = 0;
    foreach ($songs as $song) {
      $seconds += (int)($song['duration_seconds'] ?? 0);
    }
    return max(1, (int)round($seconds / 60));
  }
}


$videoCatalog = [
  [
    'id' => 1,
    'episode_id' => 1,
    'episode_slug' => 'first-to-fall',
    'season' => 1,
    'episode_number' => 1,
    'title' => 'First to Fall',
    'slug' => 'first-to-fall-full-episode',
    'video_type' => 'episode',
    'runtime' => '48 min',
    'runtime_seconds' => 2880,
    'description' => 'Stonefellow reunites for a comeback show, but the first night back exposes every debt, rivalry, and secret they tried to bury.',
    'poster' => 'images/episodes/template-pilot-feature.png',
    'hero' => 'images/episodes/template-hero-band.png',
    'stream_src' => 'video/episodes/first-to-fall.mp4',
    'preview_src' => 'video/previews/first-to-fall-preview.mp4',
    'access_level' => 'subscriber',
    'status' => 'published',
    'resume_percent' => 0,
  ],
  [
    'id' => 2,
    'episode_id' => 1,
    'episode_slug' => 'first-to-fall',
    'season' => 1,
    'episode_number' => 1,
    'title' => 'First to Fall Trailer',
    'slug' => 'first-to-fall-trailer',
    'video_type' => 'trailer',
    'runtime' => '1 min 30 sec',
    'runtime_seconds' => 90,
    'description' => 'The official public trailer for the Stonefellow pilot.',
    'poster' => 'images/episodes/template-pilot-feature.png',
    'hero' => 'images/episodes/template-hero-band.png',
    'stream_src' => 'video/trailers/first-to-fall-trailer.mp4',
    'preview_src' => '',
    'access_level' => 'public',
    'status' => 'published',
    'resume_percent' => 0,
  ],
  [
    'id' => 3,
    'episode_id' => 2,
    'episode_slug' => 'riptide-hearts',
    'season' => 1,
    'episode_number' => 2,
    'title' => 'Riptide Hearts',
    'slug' => 'riptide-hearts-full-episode',
    'video_type' => 'episode',
    'runtime' => '44 min',
    'runtime_seconds' => 2640,
    'description' => 'A new song unlocks an old secret, and the band must decide whether fame is worth reopening the wound.',
    'poster' => 'images/episodes/template-card-02.png',
    'hero' => 'images/episodes/episode-02.png',
    'stream_src' => 'video/episodes/riptide-hearts.mp4',
    'preview_src' => 'video/previews/riptide-hearts-preview.mp4',
    'access_level' => 'subscriber',
    'status' => 'coming_soon',
    'resume_percent' => 0,
  ],
  [
    'id' => 4,
    'episode_id' => 3,
    'episode_slug' => 'the-long-road-home',
    'season' => 1,
    'episode_number' => 3,
    'title' => 'The Long Road Home',
    'slug' => 'the-long-road-home-full-episode',
    'video_type' => 'episode',
    'runtime' => '46 min',
    'runtime_seconds' => 2760,
    'description' => 'A dangerous offer puts the band on the road, forcing every member to choose between loyalty and escape.',
    'poster' => 'images/episodes/template-card-03.png',
    'hero' => 'images/episodes/episode-03.png',
    'stream_src' => 'video/episodes/the-long-road-home.mp4',
    'preview_src' => 'video/previews/the-long-road-home-preview.mp4',
    'access_level' => 'premium',
    'status' => 'coming_soon',
    'resume_percent' => 0,
  ],
  [
    'id' => 5,
    'episode_id' => null,
    'episode_slug' => null,
    'season' => 1,
    'episode_number' => null,
    'title' => 'Long Road Home Live Session',
    'slug' => 'long-road-home-live-session',
    'video_type' => 'live_session',
    'runtime' => '6 min',
    'runtime_seconds' => 360,
    'description' => 'Subscriber live session archive from the Stonefellow soundstage.',
    'poster' => 'images/music/music-live-02.png',
    'hero' => 'images/cast/cast-feature-music-panel.png',
    'stream_src' => 'video/live/long-road-home-session.mp4',
    'preview_src' => 'video/previews/long-road-home-session-preview.mp4',
    'access_level' => 'premium',
    'status' => 'draft',
    'resume_percent' => 0,
  ],
];

$memberPlaylists = [
  [
    'id' => 1,
    'title' => 'My Road Songs',
    'description' => 'Private member playlist for the songs that carry the series arc.',
    'song_count' => 5,
    'cover' => 'images/music/soundtrack-cover.png',
    'visibility' => 'private',
  ],
  [
    'id' => 2,
    'title' => 'Live Sessions Watchlist',
    'description' => 'Subscriber queue for live sessions, bonus clips, and behind-the-song videos.',
    'song_count' => 3,
    'cover' => 'images/music/music-live-02.png',
    'visibility' => 'private',
  ],
];

if (!function_exists('sf_episode_by_slug')) {
  function sf_episode_by_slug(array $episodes, string $slug): ?array {
    foreach ($episodes as $index => $episode) {
      if (($episode['slug'] ?? '') === $slug) {
        $episode['id'] = $index + 1;
        return $episode;
      }
    }
    $fallback = $episodes[0] ?? null;
    if ($fallback) {
      $fallback['id'] = 1;
    }
    return $fallback;
  }
}

if (!function_exists('sf_video_by_slug')) {
  function sf_video_by_slug(array $videos, string $slug): ?array {
    foreach ($videos as $video) {
      if (($video['slug'] ?? '') === $slug) {
        return $video;
      }
    }
    return $videos[0] ?? null;
  }
}

if (!function_exists('sf_video_by_episode_slug')) {
  function sf_video_by_episode_slug(array $videos, string $episodeSlug, string $type = 'episode'): ?array {
    foreach ($videos as $video) {
      if (($video['episode_slug'] ?? '') === $episodeSlug && ($video['video_type'] ?? '') === $type) {
        return $video;
      }
    }
    return null;
  }
}

if (!function_exists('sf_songs_for_episode')) {
  function sf_songs_for_episode(array $songs, string $episodeTitle): array {
    $matches = [];
    foreach ($songs as $song) {
      if (stripos((string)($song['episode'] ?? ''), $episodeTitle) !== false || stripos((string)($song['episode_short'] ?? ''), $episodeTitle) !== false) {
        $matches[] = $song;
      }
    }
    return $matches ?: array_slice($songs, 0, 3);
  }
}


if (!function_exists('sf_song_audio_src')) {
  function sf_song_audio_src(array $song, bool $allowFull = false): string {
    $preview = (string)($song['preview_src'] ?? '');
    $full = (string)($song['full_src'] ?? '');
    if ($allowFull && $full !== '') {
      $fullPath = dirname(__DIR__) . '/assets/' . ltrim($full, '/');
      if (is_file($fullPath)) {
        return $full;
      }
    }
    return $preview;
  }
}
