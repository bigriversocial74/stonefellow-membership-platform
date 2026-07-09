<?php
$pageTitle = 'AI Script Producer';
$pageDescription = 'Chat-first script assistant for creating and updating seasons, episodes, scenes, characters, backgrounds, and series assets.';
$pageClass = 'membership-page admin-catalog-page storyboarding-system-page ai-script-producer-page';
require __DIR__ . '/../includes/storyboards.php';
require_once __DIR__ . '/../includes/storyboarding_system.php';
require_once __DIR__ . '/../includes/story_scene_backgrounds.php';
require_once __DIR__ . '/../includes/story_series_assets.php';
require_once __DIR__ . '/../includes/ai_settings.php';

function sf_ai_script_text($value, string $fallback = 'Not set'): string {
  $text = trim((string)($value ?? ''));
  return $text === '' ? $fallback : $text;
}
function sf_ai_script_snip($value, int $length = 160, string $fallback = 'Not set'): string {
  $text = sf_ai_script_text($value, $fallback);
  if (function_exists('mb_strlen') && mb_strlen($text) > $length) return rtrim(mb_substr($text, 0, $length - 1)) . '…';
  if (strlen($text) > $length) return rtrim(substr($text, 0, $length - 1)) . '…';
  return $text;
}
function sf_ai_script_first_id(array $rows): int {
  foreach ($rows as $row) {
    $id = (int)($row['id'] ?? 0);
    if ($id > 0) return $id;
  }
  return 0;
}
function sf_ai_script_find(array $rows, int $id): array {
  foreach ($rows as $row) if ((int)($row['id'] ?? 0) === $id) return $row;
  return [];
}
function sf_ai_script_action_card(string $type, string $title, string $detail, array $fields = []): array {
  return ['type'=>$type,'title'=>$title,'detail'=>$detail,'fields'=>$fields];
}
function sf_ai_script_status_badge(string $status): string {
  $map = ['draft'=>'draft','preview'=>'draft','ready'=>'active','read'=>'active','locked'=>'draft','future'=>'draft','fix'=>'canceled'];
  return sf_admin_status_badge($map[$status] ?? $status);
}

$seasons = function_exists('sf_story_v1_seasons') ? sf_story_v1_seasons() : [];
$allEpisodes = function_exists('sf_story_v1_episodes') ? sf_story_v1_episodes() : [];
$seasonId = sf_admin_int($_GET['season_id'] ?? null, 0) ?? 0;
if ($seasonId <= 0) $seasonId = (int)($allEpisodes[0]['story_season_id'] ?? sf_ai_script_first_id($seasons));
$selectedSeason = sf_ai_script_find($seasons, $seasonId) ?: ($seasons[0] ?? []);
$seasonId = (int)($selectedSeason['id'] ?? $seasonId);
$episodes = $seasonId > 0 && function_exists('sf_story_v1_episodes') ? sf_story_v1_episodes($seasonId) : $allEpisodes;
$episodeId = sf_admin_int($_GET['episode_id'] ?? null, 0) ?? 0;
if ($episodeId <= 0 || !sf_ai_script_find($episodes, $episodeId)) $episodeId = sf_ai_script_first_id($episodes ?: $allEpisodes);
$selectedEpisode = sf_ai_script_find($episodes, $episodeId) ?: sf_ai_script_find($allEpisodes, $episodeId) ?: ($episodes[0] ?? []);
$episodeId = (int)($selectedEpisode['id'] ?? $episodeId);
$scenes = $episodeId > 0 && function_exists('sf_story_v1_episode_storyboards') ? sf_story_v1_episode_storyboards($episodeId) : [];
$sceneId = sf_admin_int($_GET['scene_id'] ?? null, 0) ?? 0;
if ($sceneId <= 0 || !sf_ai_script_find($scenes, $sceneId)) $sceneId = sf_ai_script_first_id($scenes);
$selectedScene = sf_ai_script_find($scenes, $sceneId) ?: ($scenes[0] ?? []);
$characters = function_exists('sf_story_v1_characters') ? sf_story_v1_characters('active') : [];
$backgrounds = function_exists('sf_scene_backgrounds_all') ? sf_scene_backgrounds_all() : [];
$activeBackgrounds = array_values(array_filter($backgrounds, static fn($row) => (string)($row['status'] ?? 'active') === 'active'));
$assets = function_exists('sf_series_assets_all') ? sf_series_assets_all() : [];
$activeAssets = array_values(array_filter($assets, static fn($row) => (string)($row['status'] ?? 'active') === 'active'));
$providerOptions = function_exists('sf_ai_provider_options') ? sf_ai_provider_options() : ['chatgpt'=>'ChatGPT / OpenAI','claude'=>'Claude / Anthropic'];

$draftActions = [
  sf_ai_script_action_card('Create', 'Create a new season', 'Draft a season container with title, logline, theme notes, arc notes, status, and sort order.', ['Target'=>'story_seasons','Approval'=>'Required before save']),
  sf_ai_script_action_card('Create', 'Create a new episode', 'Draft an episode inside the selected season with logline, synopsis, runtime, setting, outline, and character assignments.', ['Season'=>sf_ai_script_text($selectedSeason['title'] ?? '', 'Select a season'),'Target'=>'story_episodes']),
  sf_ai_script_action_card('Create / Update', 'Create or update a scene card', 'Draft a new scene or update the selected scene with beats, dialogue, conflict, cliffhanger, characters, background, assets, and continuity notes.', ['Episode'=>sf_ai_script_text($selectedEpisode['title'] ?? '', 'Select an episode'),'Scene'=>sf_ai_script_text($selectedScene['title'] ?? '', 'New scene')]),
  sf_ai_script_action_card('Create', 'Create a character', 'Draft a reusable character profile with bio, motivation, personality, relationships, season arc, image path, and assets.', ['Target'=>'story_characters','Approval'=>'Required before save']),
  sf_ai_script_action_card('Create', 'Create background or asset', 'Draft reusable scene backgrounds, props, vehicles, wardrobe, instruments, or other continuity assets.', ['Backgrounds'=>count($activeBackgrounds),'Assets'=>count($activeAssets)]),
  sf_ai_script_action_card('Assign', 'Assign story continuity', 'Prepare character, background, and asset assignments for an episode or scene without applying them until approved.', ['Characters'=>count($characters),'Mode'=>'Draft only']),
];

$examplePrompts = [
  'Create a new episode in this season where Rio lies about the record deal and Jax finds out at the pool.',
  'Update this scene card so it happens at night, add Mia and Angel, and end with a cliffhanger text message.',
  'Create a new character named Luna. She is funny, dangerous, and knows everyone’s secrets.',
  'Make five 30-second microdrama scene cards for this episode with stronger comedy and more tension.',
  'Create a background called Desert Pool House at Night and assign it to every pool scene.',
];

require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('AI Script Producer', 'AI Script Producer', 'Chat-first control for seasons, episodes, scenes, characters, backgrounds, and story assets.', 'ai-script-assistant');
?>
<style>
.ai-script-producer-page .sf-script-hero{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(280px,.75fr);gap:16px;margin-bottom:18px}.ai-script-producer-page .sf-script-panel{padding:20px;border:1px solid rgba(232,198,127,.16);border-radius:22px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.025));box-shadow:0 20px 56px rgba(0,0,0,.22)}.ai-script-producer-page .sf-script-hero h2{margin:8px 0;color:#fff;font-size:clamp(34px,5vw,62px);letter-spacing:-.055em;line-height:.95}.ai-script-producer-page .sf-script-panel p,.ai-script-producer-page .sf-script-copy{color:rgba(255,255,255,.68);line-height:1.55}.ai-script-producer-page .sf-script-layout{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(320px,.65fr);gap:16px;margin-bottom:18px}.ai-script-producer-page .sf-script-chat{display:grid;gap:12px}.ai-script-producer-page .sf-script-msg{padding:15px;border:1px solid rgba(255,255,255,.08);border-radius:18px;background:rgba(0,0,0,.16)}.ai-script-producer-page .sf-script-msg strong{display:block;color:#fff;margin-bottom:5px}.ai-script-producer-page .sf-script-msg span{color:rgba(232,198,127,.86);font-size:11px;font-weight:950;letter-spacing:.1em;text-transform:uppercase}.ai-script-producer-page .sf-script-composer textarea{width:100%;min-height:126px;resize:vertical}.ai-script-producer-page .sf-script-context-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:18px}.ai-script-producer-page .sf-script-context-grid select{width:100%}.ai-script-producer-page .sf-script-card-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:18px}.ai-script-producer-page .sf-script-action-card{min-height:190px;padding:16px;border:1px solid rgba(232,198,127,.14);border-radius:18px;background:rgba(255,255,255,.04)}.ai-script-producer-page .sf-script-action-card h3{color:#fff;margin:12px 0 8px;font-size:18px}.ai-script-producer-page .sf-script-action-card span,.ai-script-producer-page .sf-script-stat span{display:block;color:rgba(232,198,127,.84);font-size:11px;font-weight:950;letter-spacing:.1em;text-transform:uppercase}.ai-script-producer-page .sf-script-fields{display:flex;flex-wrap:wrap;gap:6px;margin-top:12px}.ai-script-producer-page .sf-script-fields small{border:1px solid rgba(232,198,127,.18);border-radius:999px;padding:4px 8px;color:#f5d98d;font-size:11px;font-weight:900}.ai-script-producer-page .sf-script-stats{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin-bottom:18px}.ai-script-producer-page .sf-script-stat strong{display:block;margin-top:8px;color:#fff;font-size:30px}.ai-script-producer-page .sf-script-capability-list{display:grid;gap:10px}.ai-script-producer-page .sf-script-capability-list div{padding:12px;border:1px solid rgba(255,255,255,.08);border-radius:14px;background:rgba(0,0,0,.13)}.ai-script-producer-page .sf-script-capability-list strong{color:#fff}.ai-script-producer-page .sf-script-capability-list small{display:block;margin-top:4px;color:rgba(255,255,255,.58);line-height:1.45}@media(max-width:1180px){.ai-script-producer-page .sf-script-layout,.ai-script-producer-page .sf-script-hero{grid-template-columns:1fr}.ai-script-producer-page .sf-script-card-grid,.ai-script-producer-page .sf-script-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:720px){.ai-script-producer-page .sf-script-context-grid,.ai-script-producer-page .sf-script-card-grid,.ai-script-producer-page .sf-script-stats{grid-template-columns:1fr}}
</style>

<section class="sf-script-hero">
  <div class="sf-script-panel"><span class="sf-panel-eyebrow">Script Control</span><h2>Chat-first producer</h2><p>Start by talking to the assistant. It can understand requests for seasons, episodes, scenes, characters, backgrounds, and continuity assets, then prepare reviewable draft actions.</p></div>
  <div class="sf-script-panel"><span class="sf-panel-eyebrow">Phase 1 Guardrail</span><h2>Draft Only</h2><p>This foundation reads story context and previews actions. It does not save records, publish scenes, upload files, or update the database until approval workflows are added.</p></div>
</section>

<?php if (!function_exists('sf_story_v1_ready') || !sf_story_v1_ready()): ?><section class="sf-story-v1-warning"><strong>SQL required:</strong> Import the storyboarding SQL before the AI Script Producer can read and save live story data.</section><?php endif; ?>

<section class="sf-script-stats">
  <div class="sf-script-panel sf-script-stat"><span>Seasons</span><strong><?= count($seasons) ?></strong><p class="sf-script-copy">Story containers.</p></div>
  <div class="sf-script-panel sf-script-stat"><span>Episodes</span><strong><?= count($allEpisodes) ?></strong><p class="sf-script-copy">Script outlines.</p></div>
  <div class="sf-script-panel sf-script-stat"><span>Scenes</span><strong><?= count($scenes) ?></strong><p class="sf-script-copy">Selected episode.</p></div>
  <div class="sf-script-panel sf-script-stat"><span>Characters</span><strong><?= count($characters) ?></strong><p class="sf-script-copy">Active catalog.</p></div>
  <div class="sf-script-panel sf-script-stat"><span>Assets</span><strong><?= count($activeAssets) ?></strong><p class="sf-script-copy">Active props/items.</p></div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Context</span><h2>Choose where the assistant is working</h2></div></div>
  <form method="get" class="sf-admin-form">
    <div class="sf-script-context-grid">
      <label>Season<select name="season_id" onchange="this.form.submit()"><option value="0">Select season</option><?php foreach ($seasons as $season): ?><option value="<?= (int)$season['id'] ?>"<?= (int)$season['id'] === $seasonId ? ' selected' : '' ?>><?= sf_admin_h($season['title'] ?? 'Season') ?></option><?php endforeach; ?></select></label>
      <label>Episode<select name="episode_id" onchange="this.form.submit()"><option value="0">Select episode</option><?php foreach ($episodes as $episode): ?><option value="<?= (int)$episode['id'] ?>"<?= (int)$episode['id'] === $episodeId ? ' selected' : '' ?>>Episode <?= (int)($episode['episode_number'] ?? 1) ?> — <?= sf_admin_h($episode['title'] ?? 'Episode') ?></option><?php endforeach; ?></select></label>
      <label>Scene<select name="scene_id" onchange="this.form.submit()"><option value="0">New scene</option><?php foreach ($scenes as $scene): ?><option value="<?= (int)$scene['id'] ?>"<?= (int)$scene['id'] === $sceneId ? ' selected' : '' ?>><?= sf_admin_h($scene['title'] ?? ('Scene #' . (int)$scene['id'])) ?></option><?php endforeach; ?></select></label>
    </div>
  </form>
</section>

<section class="sf-script-layout">
  <div class="sf-script-panel">
    <span class="sf-panel-eyebrow">Chat Window</span>
    <div class="sf-script-chat">
      <div class="sf-script-msg"><span>Assistant</span><strong>I’m ready to help build the script.</strong><p class="sf-script-copy">Selected context: <?= sf_admin_h(sf_ai_script_text($selectedSeason['title'] ?? '', 'No season')) ?> → <?= sf_admin_h(sf_ai_script_text($selectedEpisode['title'] ?? '', 'No episode')) ?> → <?= sf_admin_h(sf_ai_script_text($selectedScene['title'] ?? '', 'New scene')) ?>.</p></div>
      <div class="sf-script-msg"><span>System</span><strong>Draft actions are preview-only in Phase 1.</strong><p class="sf-script-copy">Next phase will turn these previews into approval cards that can save seasons, episodes, characters, backgrounds, assets, and scene cards.</p></div>
      <div class="sf-script-composer"><label>Ask the AI Script Producer<textarea placeholder="Example: Create a new scene where Rio and Mia argue by the pool at night, assign the Desert Pool House background, add Jax watching from the balcony, and end with a cliffhanger text message."></textarea></label><div class="sf-admin-form-actions"><button type="button" disabled>Generate Draft Actions — Phase 2</button><button type="button" disabled>Save Approved Changes — Phase 2</button></div></div>
    </div>
  </div>
  <aside class="sf-script-panel"><span class="sf-panel-eyebrow">Current Story Context</span><h3><?= sf_admin_h(sf_ai_script_text($selectedEpisode['title'] ?? '', 'No episode selected')) ?></h3><p class="sf-script-copy"><strong>Season:</strong> <?= sf_admin_h(sf_ai_script_text($selectedSeason['title'] ?? '')) ?><br><strong>Episode:</strong> <?= sf_admin_h(sf_ai_script_text($selectedEpisode['title'] ?? '')) ?><br><strong>Scene:</strong> <?= sf_admin_h(sf_ai_script_text($selectedScene['title'] ?? '', 'New scene')) ?></p><p class="sf-script-copy"><strong>Episode outline:</strong><br><?= sf_admin_h(sf_ai_script_snip($selectedEpisode['episode_outline'] ?? $selectedEpisode['synopsis'] ?? $selectedEpisode['logline'] ?? '', 260, 'Episode outline pending.')) ?></p><p class="sf-script-copy"><strong>Model options:</strong><br><?= sf_admin_h(implode(', ', array_values($providerOptions))) ?></p></aside>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Draft Actions</span><h2>What the assistant will be able to prepare</h2></div></div>
  <div class="sf-script-card-grid">
    <?php foreach ($draftActions as $card): ?>
      <article class="sf-script-action-card">
        <?= sf_ai_script_status_badge('draft') ?>
        <span><?= sf_admin_h($card['type']) ?></span>
        <h3><?= sf_admin_h($card['title']) ?></h3>
        <p class="sf-script-copy"><?= sf_admin_h($card['detail']) ?></p>
        <div class="sf-script-fields"><?php foreach ($card['fields'] as $key => $value): ?><small><?= sf_admin_h($key) ?>: <?= sf_admin_h($value) ?></small><?php endforeach; ?></div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="sf-script-layout">
  <div class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Prompt Starters</span><h2>Useful commands</h2></div></div><div class="sf-script-capability-list"><?php foreach ($examplePrompts as $prompt): ?><div><strong><?= sf_admin_h($prompt) ?></strong><small>Copy into the chat window once Phase 2 action generation is connected.</small></div><?php endforeach; ?></div></div>
  <div class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Approval Path</span><h2>Safe control ladder</h2></div></div><div class="sf-script-capability-list"><div><strong>1. Observe</strong><small>Read story context and identify missing work.</small></div><div><strong>2. Draft</strong><small>Generate structured season, episode, scene, character, background, and asset actions.</small></div><div><strong>3. Approve</strong><small>Human approves each action card before save.</small></div><div><strong>4. Execute</strong><small>Approved writes are saved with audit history and rollback metadata.</small></div></div></div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
