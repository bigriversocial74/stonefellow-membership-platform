<?php
require_once __DIR__ . '/admin_catalog.php';

function sf_storyboard_projects(): array {
  return [
    [
      'id'=>1,
      'title'=>'Stonefellow and the Sunrise Jam',
      'slug'=>'sunrise-jam',
      'status'=>'draft_shell',
      'genre'=>'Music Comedy Drama',
      'scene_count'=>9,
      'completed_scenes'=>9,
      'characters'=>3,
      'updated_at'=>date('Y-m-d H:i:s'),
      'prompt'=>'A songwriter stumbles into a small dive bar before sunrise and ends up trading songs with a mysterious stranger and a colorful regular. By closing time, they have created something unforgettable.',
    ],
    [
      'id'=>2,
      'title'=>'Midnight Rehearsal',
      'slug'=>'midnight-rehearsal',
      'status'=>'concept',
      'genre'=>'Backstage Drama',
      'scene_count'=>9,
      'completed_scenes'=>0,
      'characters'=>2,
      'updated_at'=>date('Y-m-d H:i:s', strtotime('-2 days')),
      'prompt'=>'A band rehearses after hours and discovers that the locked theater is not as empty as it sounds.',
    ],
    [
      'id'=>3,
      'title'=>'Backstage Stories',
      'slug'=>'backstage-stories',
      'status'=>'needs_generation',
      'genre'=>'Documentary Hybrid',
      'scene_count'=>9,
      'completed_scenes'=>3,
      'characters'=>4,
      'updated_at'=>date('Y-m-d H:i:s', strtotime('-5 days')),
      'prompt'=>'A touring crew shares the true stories behind one strange night on the road.',
    ],
  ];
}
function sf_storyboard_project(?int $id = null): array {
  $projects = sf_storyboard_projects();
  if ($id) foreach ($projects as $project) if ((int)$project['id'] === $id) return $project;
  return $projects[0];
}
function sf_storyboard_status_label(string $status): string {
  return [
    'draft_shell'=>'Draft Shell',
    'concept'=>'Concept',
    'needs_generation'=>'Needs Generation',
    'generating'=>'Generating',
    'complete'=>'Complete',
  ][$status] ?? ucwords(str_replace('_',' ', $status));
}
function sf_storyboard_characters(): array {
  return [
    ['id'=>1,'name'=>'Lead Musician','role'=>'Primary','image'=>'assets/images/stonefellow-press.jpg','summary'=>'Songwriter with a worn guitar, road-weary posture, denim jacket, brown hair, and a restless heart.','notes'=>'Keep the guitar, denim jacket, medium brown hair, and thoughtful expression consistent.'],
    ['id'=>2,'name'=>'Pink Floyd Woman','role'=>'Supporting','image'=>'assets/images/uploads/placeholder-woman.jpg','summary'=>'Dreamy, curious listener in a classic rock tee who notices the song before anyone else.','notes'=>'Use soft bar lighting, dark hair, expressive eyes, and a vintage concert-shirt look.'],
    ['id'=>3,'name'=>'Tie-Dye Guy','role'=>'Supporting','image'=>'assets/images/uploads/placeholder-character.jpg','summary'=>'Bar regular with a colorful tie-dye shirt, big energy, and a heart of gold.','notes'=>'Keep bright tie-dye colors, friendly grin, and relaxed stool-at-the-bar posture.'],
  ];
}
function sf_storyboard_settings(): array {
  return [
    'scene_count'=>'9 scenes',
    'format'=>'Screenplay + Visual Storyboard',
    'visual_style'=>'Cinematic realistic dive-bar drama',
    'aspect_ratio'=>'16:9 scene frames',
    'rewrite_mode'=>'Scene-by-scene continuity',
    'ai_provider'=>'Managed by Admin',
  ];
}
function sf_storyboard_scenes(): array {
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
function sf_storyboard_h($value): string { return sf_admin_h($value); }
function sf_storyboard_scene_url(int $projectId, int $sceneNumber): string { return sf_url('admin/storyboard-builder.php?project_id=' . $projectId . '#scene-' . $sceneNumber); }
function sf_storyboard_render_character_chip(string $name): string { return '<span class="sf-admin-mini-pill">' . sf_storyboard_h($name) . '</span>'; }
?>
