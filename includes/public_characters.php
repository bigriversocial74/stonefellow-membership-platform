<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/data.php';

function sf_public_characters_table_exists(string $table): bool {
  $pdo = sf_db();
  if (!$pdo) return false;
  try { $stmt = $pdo->prepare('SHOW TABLES LIKE ?'); $stmt->execute([$table]); return (bool)$stmt->fetchColumn(); }
  catch (Throwable $e) { error_log('Stonefellow public character table check failed for ' . $table . ': ' . $e->getMessage()); return false; }
}
function sf_public_characters_db_ready(): bool { return sf_db() instanceof PDO && sf_public_characters_table_exists('story_characters'); }
function sf_public_characters_clean_path(?string $path): string {
  $path = trim((string)$path);
  if ($path === '') return '';
  if (preg_match('~^(https?:)?//|^data:~i', $path)) return $path;
  $path = ltrim($path, '/');
  if (strpos($path, 'assets/') === 0) $path = substr($path, 7);
  return $path;
}
function sf_public_character_slug(string $value): string {
  $value = strtolower(trim($value));
  $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?: '';
  $value = trim($value, '-');
  return $value !== '' ? $value : 'character';
}
function sf_public_role_label(string $role): string {
  $labels = ['lead'=>'The Frontman','supporting'=>'Supporting Cast','guest'=>'Guest Character','background'=>'Background Character','antagonist'=>'The Rival','mentor'=>'The Mentor'];
  return $labels[$role] ?? ucwords(str_replace('_', ' ', $role ?: 'Character'));
}
function sf_public_character_static_rows(): array {
  return [
    ['id'=>1,'character_name'=>'Jax Mercer','slug'=>'jax-mercer','actor_name'=>'','role_type'=>'lead','short_bio'=>'Guitar in hand, demons in tow. He writes the songs no one else can.','motivation'=>'To turn pain into music before the road takes everything from him.','personality_notes'=>'Haunted, loyal, passionate, stubborn, protective, introspective.','relationship_notes'=>'Ruby Vale (Ally / love interest); Eli Mercer (brother); Silas Kane (mentor); Cora Flint (close confidante).','season_arc'=>'Jax begins as a guarded frontman and learns whether the songs can save the people he loves.','image_path'=>'images/cast/cast-jax.png','status'=>'active','sort_order'=>10,'scene_count'=>5],
    ['id'=>2,'character_name'=>'Ruby Vale','slug'=>'ruby-vale','actor_name'=>'','role_type'=>'lead','short_bio'=>'Golden voice. Steady fire. She turns pain into power—and song.','motivation'=>'To survive the industry without losing her voice or her truth.','personality_notes'=>'Soulful, resilient, sharp, guarded, brave.','relationship_notes'=>'Jax Mercer (creative tension); Silas Kane (protector); Cora Flint (friend).','season_arc'=>'Ruby steps out of the background and becomes the sound Stonefellow cannot ignore.','image_path'=>'images/cast/cast-violet.png','status'=>'active','sort_order'=>20,'scene_count'=>4],
    ['id'=>3,'character_name'=>'Cora Flint','slug'=>'cora-flint','actor_name'=>'','role_type'=>'supporting','short_bio'=>'Sees the world in lyrics. Sharp mind, soft heart, writes the truth.','motivation'=>'To document the truth before everyone rewrites it.','personality_notes'=>'Observant, poetic, careful, loyal, quietly fearless.','relationship_notes'=>'Jax Mercer (confidante); Ruby Vale (friend); Lena Cross (creative friction).','season_arc'=>'Cora becomes the keeper of the story when the band cannot face its past.','image_path'=>'images/cast/cast-template-hero.png','status'=>'active','sort_order'=>30,'scene_count'=>3],
    ['id'=>4,'character_name'=>'Silas Kane','slug'=>'silas-kane','actor_name'=>'','role_type'=>'mentor','short_bio'=>'Old school. Hard lines. Protects the family—at any price.','motivation'=>'To protect the Stonefellow legacy, even from itself.','personality_notes'=>'Principled, severe, strategic, paternal, burdened.','relationship_notes'=>'Jax Mercer (mentor); Ruby Vale (protector); Eli Mercer (hard truth).','season_arc'=>'Silas has to choose between control and the family he claims to protect.','image_path'=>'images/cast/cast-cash.png','status'=>'active','sort_order'=>40,'scene_count'=>3],
    ['id'=>5,'character_name'=>'June Hollow','slug'=>'june-hollow','actor_name'=>'','role_type'=>'supporting','short_bio'=>'Loud. Loyal. Unapologetic. She speaks her mind and means it.','motivation'=>'To keep the band honest when fame starts lying for them.','personality_notes'=>'Firebrand, funny, direct, loyal, restless.','relationship_notes'=>'Ruby Vale (friend); Jax Mercer (foil); Wade Bishop (road ally).','season_arc'=>'June’s loyalty gets tested when the easy road would mean staying quiet.','image_path'=>'images/cast/cast-cta-stage.png','status'=>'active','sort_order'=>50,'scene_count'=>2],
    ['id'=>6,'character_name'=>'Wade Bishop','slug'=>'wade-bishop','actor_name'=>'','role_type'=>'supporting','short_bio'=>'Plays like a storm. Thrives on the road, lives for the stage.','motivation'=>'To keep the music loud enough that nobody hears the fear.','personality_notes'=>'Wild, magnetic, impulsive, sincere, road-worn.','relationship_notes'=>'Jax Mercer (bandmate); June Hollow (ally); Boone Rivers (rhythm lock).','season_arc'=>'Wade learns whether the stage can still be home after the lights go down.','image_path'=>'images/music/music-live-02.png','status'=>'active','sort_order'=>60,'scene_count'=>2],
    ['id'=>7,'character_name'=>'Lena Cross','slug'=>'lena-cross','actor_name'=>'','role_type'=>'supporting','short_bio'=>'Keeps the chaos in line. Sharp, strategic, and always three steps ahead.','motivation'=>'To make sure the comeback becomes a business, not just a memory.','personality_notes'=>'Strategic, controlled, loyal, pragmatic, private.','relationship_notes'=>'Jax Mercer (manager); Cora Flint (creative friction); Silas Kane (respectful tension).','season_arc'=>'Lena has to decide how much control the truth can survive.','image_path'=>'images/episodes/template-card-02.png','status'=>'active','sort_order'=>70,'scene_count'=>2],
    ['id'=>8,'character_name'=>'Eli Mercer','slug'=>'eli-mercer','actor_name'=>'','role_type'=>'supporting','short_bio'=>'Lost for a while. Looking for a way back to what matters.','motivation'=>'To find his way home without becoming the person everyone remembers.','personality_notes'=>'Wounded, talented, defensive, funny, searching.','relationship_notes'=>'Jax Mercer (brother); Silas Kane (hard history); Ruby Vale (unexpected ally).','season_arc'=>'Eli returns with old damage and a chance to repair what fame broke.','image_path'=>'images/cast/cast-luke.png','status'=>'active','sort_order'=>80,'scene_count'=>2],
    ['id'=>9,'character_name'=>'Boone Rivers','slug'=>'boone-rivers','actor_name'=>'','role_type'=>'supporting','short_bio'=>'The heartbeat of the band. Solid, steady, and built for the long haul.','motivation'=>'To keep everyone in time when the whole road falls apart.','personality_notes'=>'Grounded, steady, patient, dry-witted, loyal.','relationship_notes'=>'Wade Bishop (rhythm section); Jax Mercer (trusted bandmate); Ruby Vale (respect).','season_arc'=>'Boone holds the line until everyone else remembers how to listen.','image_path'=>'images/music/music-live-01.png','status'=>'active','sort_order'=>90,'scene_count'=>2],
    ['id'=>10,'character_name'=>'Preacher Judd','slug'=>'preacher-judd','actor_name'=>'','role_type'=>'guest','short_bio'=>'Lives by his own code. Faith, freedom, and a few grudges.','motivation'=>'To settle old debts before the road swallows the truth.','personality_notes'=>'Weathered, cryptic, stubborn, charming, dangerous.','relationship_notes'=>'Silas Kane (old history); Jax Mercer (warning); June Hollow (skeptical ally).','season_arc'=>'Preacher arrives with a story nobody wants told.','image_path'=>'images/episodes/template-card-05.png','status'=>'active','sort_order'=>100,'scene_count'=>1],
  ];
}
function sf_public_character_rows(string $status = 'active'): array {
  if (!sf_public_characters_db_ready()) return sf_public_character_static_rows();
  $pdo = sf_db();
  try {
    $params = [];
    $where = '';
    if ($status !== '') { $where = 'WHERE c.status = ?'; $params[] = $status; }
    $sceneJoin = sf_public_characters_table_exists('story_scene_sheet_characters') ? 'LEFT JOIN story_scene_sheet_characters l ON l.story_character_id = c.id' : '';
    $sceneCount = sf_public_characters_table_exists('story_scene_sheet_characters') ? 'COUNT(DISTINCT l.story_scene_sheet_id)' : '0';
    $stmt = $pdo->prepare("SELECT c.*, {$sceneCount} AS scene_count FROM story_characters c {$sceneJoin} {$where} GROUP BY c.id ORDER BY c.sort_order ASC, c.character_name ASC");
    $stmt->execute($params);
    $rows = $stmt->fetchAll() ?: [];
    return $rows ?: sf_public_character_static_rows();
  } catch (Throwable $e) { error_log('Stonefellow public character query failed: ' . $e->getMessage()); return sf_public_character_static_rows(); }
}
function sf_public_character_by_slug(string $slug): ?array {
  $slug = trim($slug);
  foreach (sf_public_character_rows('active') as $row) {
    $rowSlug = trim((string)($row['slug'] ?? '')) ?: sf_public_character_slug((string)($row['character_name'] ?? ''));
    if ($rowSlug === $slug) return $row + ['slug'=>$rowSlug];
  }
  return null;
}
function sf_public_character_traits(array $character): array {
  $text = trim((string)($character['personality_notes'] ?? ''));
  if ($text === '') return ['Haunted','Loyal','Passionate','Protective'];
  $parts = preg_split('/[,;\n]+/', $text) ?: [];
  $traits = [];
  foreach ($parts as $part) { $part = trim($part); if ($part !== '') $traits[] = ucwords($part); }
  return array_slice(array_unique($traits), 0, 6) ?: ['Haunted','Loyal','Passionate','Protective'];
}
function sf_public_character_image(array $character, string $fallback = 'images/cast/cast-jax.png'): string {
  $image = sf_public_characters_clean_path($character['image_path'] ?? '');
  return $image !== '' ? $image : $fallback;
}
function sf_public_character_tagline(array $character): string { return sf_public_role_label((string)($character['role_type'] ?? 'lead')); }
function sf_public_character_episodes(array $character): array {
  return [
    ['episode'=>'S1, E1','title'=>'Dust & Devotion','role'=>'Introduced as part of the Stonefellow world.','action'=>'View Episode'],
    ['episode'=>'S1, E2','title'=>'Ghosts on the Wind','role'=>'Opens up conflict, loyalty, and the weight of the road.','action'=>'View Episode'],
    ['episode'=>'S1, E3','title'=>'Broken Strings','role'=>'Questions the cost of the music and the people tied to it.','action'=>'View Scene'],
    ['episode'=>'S1, E4','title'=>'Blood on the Tracks','role'=>'Past choices force a dangerous decision.','action'=>'View Episode'],
    ['episode'=>'S1, E6','title'=>'Crossroads','role'=>'A reckoning changes the road ahead.','action'=>'View Episode'],
    ['episode'=>'S1, E8','title'=>'Carry the Fire','role'=>'Steps into the truth and accepts the cost.','action'=>'View Episode'],
  ];
}
function sf_public_related_characters(array $current, int $limit = 4): array {
  $currentId = (int)($current['id'] ?? 0);
  $rows = array_values(array_filter(sf_public_character_rows('active'), static fn($row) => (int)($row['id'] ?? 0) !== $currentId));
  return array_slice($rows, 0, $limit);
}
function sf_public_character_songs(array $character): array {
  return [
    ['title'=>'Dust & Devotion','artist'=>'Stonefellow','duration'=>'3:42'],
    ['title'=>'Ghosts On The Wind','artist'=>'Stonefellow','duration'=>'4:18'],
    ['title'=>'Carry The Fire','artist'=>'Stonefellow','duration'=>'4:55'],
  ];
}
?>
