<?php
$pageTitle = 'AI Publishing Readiness Manager';
$pageDescription = 'Inspect content readiness and create supervised AI action proposals before publishing.';
$pageClass = 'membership-page admin-catalog-page storyboarding-system-page ai-publishing-readiness-page';
require_once __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/ai_platform_execution.php';
require_once __DIR__ . '/../includes/storyboards.php';
require_once __DIR__ . '/../includes/storyboarding_system.php';

function sf_aipr_ready(): bool { return sf_admin_table_exists('ai_platform_actions'); }
function sf_aipr_text($value, string $fallback = 'Not set'): string { $text = trim((string)($value ?? '')); return $text === '' ? $fallback : $text; }
function sf_aipr_snip($value, int $length = 180, string $fallback = 'Not set'): string { $text = sf_aipr_text($value, $fallback); return strlen($text) > $length ? rtrim(substr($text, 0, $length - 1)) . '…' : $text; }
function sf_aipr_status_col(): string { foreach (['producer_scene_status','storyboard_status','generation_status'] as $col) if (sf_admin_column_exists('storyboards', $col)) return $col; return ''; }
function sf_aipr_routes(): array { return function_exists('sf_ai_exec_routes') ? sf_ai_exec_routes() : []; }
function sf_aipr_json(array $payload): string { return json_encode($payload, JSON_UNESCAPED_SLASHES); }
function sf_aipr_payload_key(array $payload): string { foreach (['source','created_from','created_at','requested_at','suggested_next_step'] as $k) unset($payload[$k]); ksort($payload); return sha1(sf_aipr_json($payload)); }
function sf_aipr_scene_title(array $row): string { return sf_aipr_text($row['title'] ?? $row['project_title'] ?? '', 'Storyboard #' . (int)($row['id'] ?? 0)); }
function sf_aipr_scene_status(array $row): string { foreach (['producer_scene_status','status','storyboard_status','generation_status'] as $key) { $value = trim((string)($row[$key] ?? '')); if ($value !== '') return $value; } return 'outline'; }
function sf_aipr_storyboards(int $limit = 80): array { if (!sf_admin_table_exists('storyboards')) return []; return sf_admin_fetch_all('SELECT * FROM storyboards ORDER BY COALESCE(updated_at, created_at) DESC, id DESC LIMIT ' . max(1, min(200, $limit))); }
function sf_aipr_episode_rows(): array { if (!sf_admin_table_exists('story_episodes')) return []; return sf_admin_fetch_all('SELECT e.*, s.title AS season_title FROM story_episodes e LEFT JOIN story_seasons s ON s.id = e.story_season_id ORDER BY COALESCE(e.updated_at, e.created_at) DESC, e.id DESC LIMIT 80'); }
function sf_aipr_prompt_map(): array {
  $map = [];
  if (!sf_admin_table_exists('story_ai_media_prompts')) return $map;
  $rows = sf_admin_fetch_all('SELECT storyboard_id, COUNT(*) total, SUM(status IN (\'approved\',\'ready_for_generation\')) approved_count, MIN(CASE WHEN status IN (\'approved\',\'ready_for_generation\') THEN id ELSE NULL END) approved_prompt_id FROM story_ai_media_prompts GROUP BY storyboard_id');
  foreach ($rows as $row) $map[(int)($row['storyboard_id'] ?? 0)] = ['total'=>(int)($row['total'] ?? 0), 'approved'=>(int)($row['approved_count'] ?? 0), 'approved_prompt_id'=>(int)($row['approved_prompt_id'] ?? 0)];
  return $map;
}
function sf_aipr_generation_map(): array {
  $map = [];
  if (!sf_admin_table_exists('story_ai_media_generation_jobs')) return $map;
  $rows = sf_admin_fetch_all('SELECT storyboard_id, COUNT(*) total, SUM(generation_status IN (\'queued\',\'needs_review\',\'generated\')) active_count FROM story_ai_media_generation_jobs GROUP BY storyboard_id');
  foreach ($rows as $row) $map[(int)($row['storyboard_id'] ?? 0)] = ['total'=>(int)($row['total'] ?? 0), 'active'=>(int)($row['active_count'] ?? 0)];
  return $map;
}
function sf_aipr_existing_action_keys(): array {
  $keys = [];
  if (!sf_aipr_ready()) return $keys;
  $rows = sf_admin_fetch_all("SELECT action_type, target_table, target_id, payload_json FROM ai_platform_actions WHERE approval_status IN ('draft','proposed','approved','ready_for_execution') ORDER BY id DESC LIMIT 600");
  foreach ($rows as $row) {
    $payload = json_decode((string)($row['payload_json'] ?? ''), true);
    $fingerprint = is_array($payload) ? sf_aipr_payload_key($payload) : 'none';
    $keys[(string)($row['action_type'] ?? '') . '|' . (string)($row['target_table'] ?? '') . '|' . (int)($row['target_id'] ?? 0) . '|' . $fingerprint] = true;
  }
  return $keys;
}
function sf_aipr_finding(string $severity, string $area, string $title, string $description, string $route, array $payload, string $targetTable = '', int $targetId = 0, string $risk = 'medium'): array {
  return compact('severity','area','title','description','route','payload','targetTable','targetId','risk');
}
function sf_aipr_findings(): array {
  $findings = [];
  $routes = sf_aipr_routes();
  $storyboards = sf_aipr_storyboards();
  $prompts = sf_aipr_prompt_map();
  $jobs = sf_aipr_generation_map();
  if (!sf_admin_table_exists('storyboards')) {
    $findings[] = sf_aipr_finding('blocked', 'system', 'Storyboard table missing', 'Publishing readiness needs the storyboards table before it can inspect scene shells.', 'review', ['scope'=>'storyboards_table_missing'], '', 0, 'medium');
    return $findings;
  }
  foreach ($storyboards as $row) {
    $id = (int)($row['id'] ?? 0);
    if ($id <= 0) continue;
    $title = sf_aipr_scene_title($row);
    $status = sf_aipr_scene_status($row);
    $seasonId = (int)($row['story_season_id'] ?? 0);
    $episodeId = (int)($row['story_episode_id'] ?? 0);
    $promptStats = $prompts[$id] ?? ['total'=>0,'approved'=>0,'approved_prompt_id'=>0];
    $jobStats = $jobs[$id] ?? ['total'=>0,'active'=>0];
    if (in_array($status, ['draft','outline','in_progress'], true) && isset($routes['mark_scene_needs_review'])) $findings[] = sf_aipr_finding('review', 'story', 'Move scene to review: ' . $title, 'Scene shell is still ' . $status . '. It should be reviewed before publishing preparation continues.', 'mark_scene_needs_review', ['storyboard_id'=>$id], 'storyboards', $id, 'medium');
    if ($status === 'needs_review' && (int)$promptStats['approved'] > 0 && isset($routes['mark_scene_ready'])) $findings[] = sf_aipr_finding('ready', 'story', 'Candidate ready scene: ' . $title, 'Scene is in review and already has approved media prompts. Mark it ready if the producer review is complete.', 'mark_scene_ready', ['storyboard_id'=>$id], 'storyboards', $id, 'medium');
    if ((int)$promptStats['total'] <= 0 && isset($routes['queue_media_prompt'])) $findings[] = sf_aipr_finding('gap', 'media', 'Missing media prompt: ' . $title, 'Scene has no saved media-prep prompt. Queue a draft prompt before generation planning.', 'queue_media_prompt', ['storyboard_id'=>$id,'story_season_id'=>$seasonId ?: null,'story_episode_id'=>$episodeId ?: null,'prompt_type'=>'still_image','prompt_title'=>'Publishing readiness still — ' . $title,'prompt_body'=>'Create a publishing-readiness still image prompt for scene: ' . $title . '. Source direction: ' . sf_aipr_snip($row['source_script'] ?? $row['short_prompt'] ?? '', 360, 'No source direction saved.'),'provider_hint'=>'image','aspect_ratio'=>'16:9','status'=>'approved'], 'storyboards', $id, 'medium');
    if ((int)$promptStats['approved'] > 0 && (int)$jobStats['active'] <= 0 && isset($routes['queue_media_generation'])) $findings[] = sf_aipr_finding('gap', 'media', 'Approved prompt not queued: ' . $title, 'Scene has an approved media prompt but no active generation request. Queue it when ready for provider review.', 'queue_media_generation', ['media_prompt_id'=>(int)$promptStats['approved_prompt_id']], 'storyboards', $id, 'high');
    if ($status === 'ready' && isset($routes['create_team_briefing_draft'])) $findings[] = sf_aipr_finding('briefing', 'ops', 'Create production briefing: ' . $title, 'Scene is ready. Create a draft team briefing for production review without sending it.', 'create_team_briefing_draft', ['title'=>'Production briefing — ' . $title,'subject'=>'Ready scene briefing: ' . $title,'body'=>'AI publishing readiness flagged this scene as ready for production review. Scene #' . $id . ': ' . $title . "\n\nDirection: " . sf_aipr_snip($row['source_script'] ?? $row['short_prompt'] ?? '', 700, 'No source direction saved.'),'action_url'=>'admin/ai-publishing-readiness.php'], 'storyboards', $id, 'medium');
  }
  foreach (sf_aipr_episode_rows() as $episode) {
    $episodeId = (int)($episode['id'] ?? 0);
    if ($episodeId <= 0 || !sf_admin_column_exists('storyboards', 'story_episode_id')) continue;
    $sceneCount = (int)(sf_admin_fetch_one('SELECT COUNT(*) total FROM storyboards WHERE story_episode_id = ?', [$episodeId])['total'] ?? 0);
    if ($sceneCount <= 0 && isset($routes['review'])) $findings[] = sf_aipr_finding('gap', 'episode', 'Episode has no scene shells: ' . sf_aipr_text($episode['title'] ?? '', 'Episode #' . $episodeId), 'Episode exists but no storyboard scene shells are attached. Review before publishing planning.', 'review', ['episode_id'=>$episodeId,'readiness_gap'=>'episode_has_no_scene_shells'], 'story_episodes', $episodeId, 'medium');
  }
  return $findings;
}
function sf_aipr_counts(array $findings): array { $out = ['total'=>count($findings),'gap'=>0,'review'=>0,'ready'=>0,'briefing'=>0,'blocked'=>0]; foreach ($findings as $f) { $k = (string)($f['severity'] ?? 'gap'); if (!isset($out[$k])) $out[$k] = 0; $out[$k]++; } return $out; }

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'create_readiness_proposal') {
  $routes = sf_aipr_routes();
  $route = trim((string)($_POST['route'] ?? ''));
  $payloadRaw = trim((string)($_POST['payload_json'] ?? ''));
  $payload = json_decode($payloadRaw, true);
  $risk = trim((string)($_POST['risk_level'] ?? 'medium'));
  if (!isset($routes[$route])) {
    sf_admin_flash('error', 'Readiness proposal route is not allowlisted.');
  } elseif (!is_array($payload)) {
    sf_admin_flash('error', 'Readiness proposal payload JSON is invalid.');
  } elseif (!sf_aipr_ready()) {
    sf_admin_flash('error', 'AI action registry is not ready. Import Phase 13 SQL first.');
  } else {
    if (!in_array($risk, ['low','medium','high','critical'], true)) $risk = 'medium';
    $targetTable = sf_aipr_text($_POST['target_table'] ?? '', '');
    $targetId = (int)($_POST['target_id'] ?? 0);
    $title = sf_aipr_text($_POST['title'] ?? '', $routes[$route]['label'] ?? 'AI readiness proposal');
    $description = sf_aipr_text($_POST['description'] ?? '', $routes[$route]['description'] ?? 'Readiness proposal created by AI Publishing Readiness Manager.');
    $payload = $payload + ['source'=>'ai_publishing_readiness','created_from'=>'readiness_finding','created_at'=>date('c')];
    $ok = sf_admin_execute('INSERT INTO ai_platform_actions (action_area, action_type, target_table, target_id, title, description, payload_json, risk_level, approval_status, execution_status, created_by_ai) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)', [sf_aipr_text($_POST['action_area'] ?? '', 'publishing'), $route, $targetTable ?: null, $targetId ?: null, $title, $description, sf_aipr_json($payload), $risk, 'proposed', 'not_ready']);
    if ($ok) sf_admin_audit('ai_publishing_readiness_proposal_created', 'ai_platform_action', null, null, ['route'=>$route,'target_table'=>$targetTable,'target_id'=>$targetId]);
    sf_admin_flash($ok ? 'success' : 'error', $ok ? 'Readiness proposal created in AI Control Center.' : 'Readiness proposal could not be created.');
  }
  sf_admin_redirect(sf_url('admin/ai-publishing-readiness.php'));
}

$findings = sf_aipr_findings();
$counts = sf_aipr_counts($findings);
$existingKeys = sf_aipr_existing_action_keys();
$routeHelp = sf_aipr_routes();
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('AI Operations', 'AI Publishing Readiness', 'Inspect readiness gaps and create supervised AI action proposals before publishing.', 'ai-publishing-readiness');
?>
<style>
.ai-publishing-readiness-page .sf-pub-hero,.ai-publishing-readiness-page .sf-pub-stats,.ai-publishing-readiness-page .sf-pub-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}.ai-publishing-readiness-page .sf-pub-panel{padding:20px;border:1px solid rgba(232,198,127,.16);border-radius:22px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.025));box-shadow:0 20px 56px rgba(0,0,0,.22)}.ai-publishing-readiness-page .sf-pub-panel h2{margin:8px 0;color:#fff;font-size:clamp(30px,4vw,56px);letter-spacing:-.05em;line-height:.98}.ai-publishing-readiness-page .sf-pub-copy,.ai-publishing-readiness-page .sf-pub-panel p{color:rgba(255,255,255,.68);line-height:1.55}.ai-publishing-readiness-page .sf-pub-card{padding:16px;border:1px solid rgba(255,255,255,.08);border-radius:18px;background:rgba(0,0,0,.15)}.ai-publishing-readiness-page .sf-pub-card h3{color:#fff;margin:10px 0 8px}.ai-publishing-readiness-page .sf-pub-pill{display:inline-block;margin-top:8px;border:1px solid rgba(232,198,127,.18);border-radius:999px;padding:4px 8px;color:#f5d98d;font-size:11px;font-weight:900}.ai-publishing-readiness-page code{display:block;white-space:normal;overflow-wrap:anywhere;margin-top:8px;color:#d7e6ff;background:rgba(0,0,0,.22);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:8px;font-size:12px}@media(max-width:1080px){.ai-publishing-readiness-page .sf-pub-hero,.ai-publishing-readiness-page .sf-pub-stats,.ai-publishing-readiness-page .sf-pub-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:720px){.ai-publishing-readiness-page .sf-pub-hero,.ai-publishing-readiness-page .sf-pub-stats,.ai-publishing-readiness-page .sf-pub-grid{grid-template-columns:1fr}}
</style>
<section class="sf-pub-hero"><div class="sf-pub-panel" style="grid-column:span 3"><span class="sf-panel-eyebrow">Phase 16</span><h2>Publishing readiness</h2><p>AI inspects production gaps and creates supervised action proposals. Publishing still requires separate human approval and no route publishes content.</p></div><div class="sf-pub-panel"><span class="sf-panel-eyebrow">Guardrail</span><h2>No publish</h2><p>This manager proposes fixes only. It does not publish, delete, generate media, send campaigns, or execute routes.</p></div></section>
<?php if (!sf_aipr_ready()): ?><section class="sf-story-v1-warning"><strong>SQL required:</strong> Import Phase 13 <code>database/ai_platform_actions.sql</code> before creating proposals.</section><?php endif; ?>
<section class="sf-pub-stats"><div class="sf-pub-panel"><span class="sf-panel-eyebrow">Findings</span><h2><?= (int)$counts['total'] ?></h2></div><div class="sf-pub-panel"><span class="sf-panel-eyebrow">Gaps</span><h2><?= (int)$counts['gap'] ?></h2></div><div class="sf-pub-panel"><span class="sf-panel-eyebrow">Needs Review</span><h2><?= (int)$counts['review'] ?></h2></div><div class="sf-pub-panel"><span class="sf-panel-eyebrow">Ready Candidates</span><h2><?= (int)$counts['ready'] ?></h2></div></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Route Help</span><h2>Publishing-safe proposal routes</h2></div></div><div class="sf-pub-grid"><?php foreach (['review','mark_scene_needs_review','mark_scene_ready','queue_media_prompt','queue_media_generation','create_team_briefing_draft'] as $routeKey): if (empty($routeHelp[$routeKey])) continue; $route = $routeHelp[$routeKey]; ?><article class="sf-pub-card"><span class="sf-pub-pill"><?= sf_admin_h($routeKey) ?> · <?= sf_admin_h($route['side_effect'] ?? 'route') ?></span><h3><?= sf_admin_h($route['label'] ?? $routeKey) ?></h3><p class="sf-pub-copy"><?= sf_admin_h($route['description'] ?? '') ?></p><?php if (!empty($route['payload_hint'])): ?><code><?= sf_admin_h($route['payload_hint']) ?></code><?php endif; ?></article><?php endforeach; ?></div></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Readiness Findings</span><h2><?= count($findings) ?> proposal candidate(s)</h2></div><div><a href="<?= sf_url('admin/ai-platform-control.php') ?>">AI Control Center</a> · <a href="<?= sf_url('admin/ai-execution-router.php') ?>">Execution Router</a></div></div><?php if (!$findings): ?><p class="sf-pub-copy">No publishing-readiness findings were detected in the inspected content window.</p><?php else: ?><div class="sf-pub-grid"><?php foreach ($findings as $finding): $key = (string)$finding['route'] . '|' . (string)$finding['targetTable'] . '|' . (int)$finding['targetId'] . '|' . sf_aipr_payload_key($finding['payload']); $exists = !empty($existingKeys[$key]); ?><article class="sf-pub-card"><span class="sf-pub-pill"><?= sf_admin_h($finding['severity']) ?> · <?= sf_admin_h($finding['route']) ?></span><h3><?= sf_admin_h($finding['title']) ?></h3><p class="sf-pub-copy"><strong><?= sf_admin_h($finding['area']) ?></strong> · Risk <?= sf_admin_h($finding['risk']) ?><?php if ($exists): ?><br>Existing active proposal already found.<?php endif; ?></p><p class="sf-pub-copy"><?= sf_admin_h($finding['description']) ?></p><code><?= sf_admin_h(sf_aipr_json($finding['payload'])) ?></code><form method="post" class="sf-admin-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="create_readiness_proposal"><input type="hidden" name="route" value="<?= sf_admin_h($finding['route']) ?>"><input type="hidden" name="action_area" value="<?= sf_admin_h($finding['area']) ?>"><input type="hidden" name="risk_level" value="<?= sf_admin_h($finding['risk']) ?>"><input type="hidden" name="target_table" value="<?= sf_admin_h($finding['targetTable']) ?>"><input type="hidden" name="target_id" value="<?= (int)$finding['targetId'] ?>"><input type="hidden" name="title" value="<?= sf_admin_h($finding['title']) ?>"><input type="hidden" name="description" value="<?= sf_admin_h($finding['description']) ?>"><input type="hidden" name="payload_json" value="<?= sf_admin_h(sf_aipr_json($finding['payload'])) ?>"><div class="sf-admin-form-actions"><button type="submit"<?= (sf_aipr_ready() && !$exists) ? '' : ' disabled' ?>><?= $exists ? 'Proposal Exists' : 'Create Proposal' ?></button></div></form></article><?php endforeach; ?></div><?php endif; ?></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Safety</span><h2>Judgment before control</h2></div></div><p class="sf-pub-copy">Phase 16 gives AI production judgment: it identifies gaps and creates proposal cards. Phase 13–15 still control approval and execution, and no publishing action exists in this manager.</p></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
