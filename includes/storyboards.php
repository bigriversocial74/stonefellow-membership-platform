<?php
require_once __DIR__ . '/admin_catalog.php';

function sf_storyboard_db(): ?PDO { return sf_admin_db(); }
function sf_storyboard_ready(): bool { return sf_storyboard_db() instanceof PDO && sf_admin_table_exists('storyboards'); }
function sf_storyboard_h($value): string { return sf_admin_h($value); }
function sf_storyboard_static_projects(): array {
  return [
    ['id'=>1,'title'=>'Stonefellow and the Sunrise Jam','slug'=>'sunrise-jam','status'=>'draft_shell','genre'=>'Music Comedy Drama','scene_count'=>9,'completed_scenes'=>9,'characters'=>3,'updated_at'=>date('Y-m-d H:i:s'),'prompt'=>'A songwriter stumbles into a small dive bar before sunrise and ends up trading songs with a mysterious stranger and a colorful regular. By closing time, they have created something unforgettable.'],
    ['id'=>2,'title'=>'Midnight Rehearsal','slug'=>'midnight-rehearsal','status'=>'concept','genre'=>'Backstage Drama','scene_count'=>9,'completed_scenes'=>0,'characters'=>2,'updated_at'=>date('Y-m-d H:i:s', strtotime('-2 days')),'prompt'=>'A band rehearses after hours and discovers that the locked theater is not as empty as it sounds.'],
    ['id'=>3,'title'=>'Backstage Stories','slug'=>'backstage-stories','status'=>'needs_generation','genre'=>'Documentary Hybrid','scene_count'=>9,'completed_scenes'=>3,'characters'=>4,'updated_at'=>date('Y-m-d H:i:s', strtotime('-5 days')),'prompt'=>'A touring crew shares the true stories behind one strange night on the road.'],
  ];
}
function sf_storyboard_projects(): array {
  if (!sf_storyboard_ready()) return sf_storyboard_static_projects();
  $rows = sf_admin_fetch_all("SELECT s.*, COUNT(DISTINCT sc.id) AS completed_scenes, COUNT(DISTINCT ch.id) AS characters FROM storyboards s LEFT JOIN storyboard_scenes sc ON sc.storyboard_id = s.id LEFT JOIN storyboard_characters ch ON ch.storyboard_id = s.id AND ch.status = 'active' GROUP BY s.id ORDER BY COALESCE(s.updated_at, s.created_at) DESC, s.id DESC LIMIT 100");
  if (!$rows) return sf_storyboard_static_projects();
  foreach ($rows as &$row) {
    $row['status'] = $row['storyboard_status'] ?? $row['generation_status'] ?? 'draft';
    $row['genre'] = $row['genre'] ?? 'Storyboard';
    $row['prompt'] = $row['short_prompt'] ?? $row['source_script'] ?? '';
    $row['updated_at'] = $row['updated_at'] ?? $row['created_at'] ?? '';
  }
  return $rows;
}
function sf_storyboard_project(?int $id = null): array {
  if (sf_storyboard_ready() && $id) {
    $row = sf_admin_fetch_one('SELECT * FROM storyboards WHERE id = ? LIMIT 1', [$id]);
    if ($row) {
      $row['status'] = $row['storyboard_status'] ?? $row['generation_status'] ?? 'draft';
      $row['prompt'] = $row['short_prompt'] ?? $row['source_script'] ?? '';
      $row['genre'] = $row['genre'] ?? 'Storyboard';
      return $row;
    }
  }
  $projects = sf_storyboard_projects();
  if ($id) foreach ($projects as $project) if ((int)$project['id'] === $id) return $project;
  return $projects[0];
}
function sf_storyboard_create_project(array $payload): int {
  if (!sf_storyboard_ready()) return 0;
  $title = trim((string)($payload['title'] ?? 'Untitled Storyboard')) ?: 'Untitled Storyboard';
  $slug = sf_admin_slugify($title);
  $baseSlug = $slug;
  $i = 2;
  while (sf_admin_fetch_one('SELECT id FROM storyboards WHERE slug = ? LIMIT 1', [$slug])) $slug = $baseSlug . '-' . $i++;
  $sql = 'INSERT INTO storyboards (title, slug, short_prompt, source_script, genre, tone, visual_style, aspect_ratio, scene_count, default_text_provider, default_image_provider, generation_status, storyboard_status, created_by_user_id, updated_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
  $ok = sf_admin_execute($sql, [$title, $slug, trim((string)($payload['short_prompt'] ?? '')), trim((string)($payload['source_script'] ?? '')), trim((string)($payload['genre'] ?? '')), trim((string)($payload['tone'] ?? '')), trim((string)($payload['visual_style'] ?? 'Cinematic realistic')), trim((string)($payload['aspect_ratio'] ?? '16:9')), max(1, min(12, (int)($payload['scene_count'] ?? 9))), trim((string)($payload['default_text_provider'] ?? 'chatgpt')), trim((string)($payload['default_image_provider'] ?? 'chatgpt')), 'draft', 'concept', sf_current_user_id(), sf_current_user_id()]);
  if (!$ok) return 0;
  $id = (int)(sf_storyboard_db()?->lastInsertId() ?: 0);
  sf_admin_audit('create_storyboard', 'storyboard', $id, null, ['title'=>$title,'slug'=>$slug]);
  return $id;
}
function sf_storyboard_status_label(string $status): string {
  return ['draft_shell'=>'Draft Shell','concept'=>'Concept','needs_generation'=>'Needs Generation','generating'=>'Generating','complete'=>'Complete','draft'=>'Draft','in_review'=>'In Review','ready'=>'Ready','archived'=>'Archived'][$status] ?? ucwords(str_replace('_',' ', $status));
}
function sf_storyboard_static_characters(): array {
  return [
    ['id'=>1,'name'=>'Lead Musician','role'=>'Primary','image'=>'assets/images/stonefellow-press.jpg','summary'=>'Songwriter with a worn guitar, road-weary posture, denim jacket, brown hair, and a restless heart.','notes'=>'Keep the guitar, denim jacket, medium brown hair, and thoughtful expression consistent.'],
    ['id'=>2,'name'=>'Pink Floyd Woman','role'=>'Supporting','image'=>'assets/images/uploads/placeholder-woman.jpg','summary'=>'Dreamy, curious listener in a classic rock tee who notices the song before anyone else.','notes'=>'Use soft bar lighting, dark hair, expressive eyes, and a vintage concert-shirt look.'],
    ['id'=>3,'name'=>'Tie-Dye Guy','role'=>'Supporting','image'=>'assets/images/uploads/placeholder-character.jpg','summary'=>'Bar regular with a colorful tie-dye shirt, big energy, and a heart of gold.','notes'=>'Keep bright tie-dye colors, friendly grin, and relaxed stool-at-the-bar posture.'],
  ];
}
function sf_storyboard_characters(?int $storyboardId = null): array {
  if (!sf_storyboard_ready() || !$storyboardId || !sf_admin_table_exists('storyboard_characters')) return sf_storyboard_static_characters();
  $rows = sf_admin_fetch_all('SELECT c.*, ma.file_path AS reference_path FROM storyboard_characters c LEFT JOIN media_assets ma ON ma.id = c.reference_asset_id WHERE c.storyboard_id = ? ORDER BY c.character_order ASC, c.id ASC', [$storyboardId]);
  if (!$rows) return sf_storyboard_static_characters();
  return array_map(static fn($row) => ['id'=>$row['id'],'name'=>$row['character_name'],'role'=>$row['role_label'] ?: 'Character','image'=>$row['reference_path'] ?: 'assets/images/uploads/placeholder-character.jpg','summary'=>$row['appearance_notes'] ?: $row['personality_notes'] ?: 'Character profile pending.','notes'=>$row['consistency_prompt'] ?: $row['wardrobe_notes'] ?: 'Add consistency notes.'], $rows);
}
function sf_storyboard_settings(?array $project = null): array {
  return [
    'scene_count'=>($project['scene_count'] ?? 9) . ' scenes',
    'format'=>'Screenplay + Visual Storyboard',
    'visual_style'=>$project['visual_style'] ?? 'Cinematic realistic dive-bar drama',
    'aspect_ratio'=>$project['aspect_ratio'] ?? '16:9 scene frames',
    'rewrite_mode'=>'Scene-by-scene continuity',
    'ai_provider'=>'Managed by Admin',
  ];
}
function sf_storyboard_static_scenes(): array {
  return [
    ['number'=>1,'title'=>'Early Morning Arrival','image'=>'assets/images/hero-bar-stage.jpg','prompt'=>'A weary songwriter pushes open the door to a sleepy dive bar just before sunrise, carrying a guitar case and a story he has not told yet.','dialog'=>'Long night. Long road. Perfect timing.','characters'=>['Lead Musician'],'status'=>'Visual draft'],
    ['number'=>2,'title'=>'The Empty Stage','image'=>'assets/images/episodes/episode-1.jpg','prompt'=>'He notices a small corner stage, one microphone, amber light, and a handwritten sign that says live music tonight.','dialog'=>'Looks like I found the right place.','characters'=>['Lead Musician'],'status'=>'Needs image polish'],
    ['number'=>3,'title'=>'A Mysterious Listener','image'=>'assets/images/episodes/episode-2.jpg','prompt'=>'A woman in a vintage Pink Floyd tee watches him from the end of the bar, amused by the way he studies the stage.','dialog'=>'You always play like that, or just when no one is listening?','characters'=>['Lead Musician','Pink Floyd Woman'],'status'=>'Visual draft'],
    ['number'=>4,'title'=>'Enter the Regular','image'=>'assets/images/episodes/episode-3.jpg','prompt'=>'A colorful regular slides onto a stool with a grin, a half-finished story, and the confidence of someone who knows every corner of the room.','dialog'=>'Best time for music is when the world is still asleep.','characters'=>['Lead Musician','Tie-Dye Guy'],'status'=>'Visual draft'],
    ['number'=>5,'title'=>'Stories and Songs','image'=>'assets/images/series-poster.jpg','prompt'=>'The three trade stories, riffs, and laughs as the bar slowly turns from late night to early morning.','dialog'=>'Let us see what happens if we play this together.','characters'=>['Lead Musician','Pink Floyd Woman','Tie-Dye Guy'],'status'=>'Scene ready'],
    ['number'=>6,'title'=>'The First Jam','image'=>'assets/images/video-poster.jpg','prompt'=>'They hit the stage for an impromptu jam that catches fire, pulling the bartender and early regulars toward the music.','dialog'=>'One take. No overthinking. Just feel it.','characters'=>['Lead Musician','Pink Floyd Woman','Tie-Dye Guy'],'status'=>'Scene ready'],
    ['number'=>7,'title'=>'The Bar Comes Alive','image'=>'assets/images/episodes/episode-4.jpg','prompt'=>'The sleepy bar wakes up as the crowd gathers, phones come out, and a forgotten room becomes a small sunrise concert.','dialog'=>'Turn it up. This is the good stuff.','characters'=>['Lead Musician','Pink Floyd Woman','Tie-Dye Guy'],'status'=>'Needs dialog pass'],
    ['number'=>8,'title'=>'Sunrise and Silence','image'=>'assets/images/episodes/episode-5.jpg','prompt'=>'The final note hangs in the air as sunlight breaks through the windows and the room becomes quiet for the first time all night.','dialog'=>'I think we just made something real.','characters'=>['Lead Musician','Pink Floyd Woman','Tie-Dye Guy'],'status'=>'Visual draft'],
    ['number'=>9,'title'=>'Until Next Time','image'=>'assets/images/episodes/episode-6.jpg','prompt'=>'New friends, new memories, and a promise to meet again as the city outside starts another day.','dialog'=>'Same time tomorrow? You bet.','characters'=>['Lead Musician','Pink Floyd Woman','Tie-Dye Guy'],'status'=>'Scene ready'],
  ];
}
function sf_storyboard_scenes(?int $storyboardId = null): array {
  if (!sf_storyboard_ready() || !$storyboardId || !sf_admin_table_exists('storyboard_scenes')) return sf_storyboard_static_scenes();
  $rows = sf_admin_fetch_all('SELECT s.*, COALESCE(up.file_path, gen.file_path) AS image_path FROM storyboard_scenes s LEFT JOIN media_assets up ON up.id = s.uploaded_image_asset_id LEFT JOIN media_assets gen ON gen.id = s.generated_image_asset_id WHERE s.storyboard_id = ? ORDER BY s.scene_number ASC', [$storyboardId]);
  if (!$rows) return sf_storyboard_static_scenes();
  $charactersByScene = [];
  if (sf_admin_table_exists('storyboard_scene_characters')) {
    $links = sf_admin_fetch_all('SELECT l.scene_id, c.character_name FROM storyboard_scene_characters l INNER JOIN storyboard_characters c ON c.id = l.character_id WHERE l.storyboard_id = ? ORDER BY c.character_order ASC, c.id ASC', [$storyboardId]);
    foreach ($links as $link) $charactersByScene[(int)$link['scene_id']][] = (string)$link['character_name'];
  }
  return array_map(static fn($row) => ['number'=>(int)$row['scene_number'],'title'=>$row['scene_title'],'image'=>$row['image_path'] ?: 'assets/images/series-poster.jpg','prompt'=>$row['scene_prompt'] ?: $row['scene_summary'] ?: 'Scene prompt pending.','dialog'=>$row['dialog_text'] ?: 'Dialog pending.','characters'=>$charactersByScene[(int)$row['id']] ?? [],'status'=>sf_storyboard_status_label($row['scene_status'] ?? 'draft')], $rows);
}
function sf_storyboard_scene_url(int $projectId, int $sceneNumber): string { return sf_url('admin/storyboard-builder.php?project_id=' . $projectId . '#scene-' . $sceneNumber); }
function sf_storyboard_render_character_chip(string $name): string { return '<span class="sf-admin-mini-pill">' . sf_storyboard_h($name) . '</span>'; }
?>
