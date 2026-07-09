<?php
$pageTitle = 'AI Script Review Queue';
$pageDescription = 'Review AI-created and AI-updated storyboard scene shells before production moves forward.';
$pageClass = 'membership-page admin-catalog-page storyboarding-system-page ai-script-review-queue-page';
require __DIR__ . '/../includes/storyboards.php';
require_once __DIR__ . '/../includes/storyboarding_system.php';
require_once __DIR__ . '/../includes/admin_catalog.php';

function sf_aisrq_text($value, string $fallback = 'Not set'): string { $text = trim((string)($value ?? '')); return $text === '' ? $fallback : $text; }
function sf_aisrq_snip($value, int $length = 180, string $fallback = 'Not set'): string { $text = sf_aisrq_text($value, $fallback); return strlen($text) > $length ? rtrim(substr($text, 0, $length - 1)) . '…' : $text; }
function sf_aisrq_statuses(): array { return ['outline'=>'Outline','needs_review'=>'Needs Review','ready'=>'Ready for Production']; }
function sf_aisrq_ready(): bool { return sf_admin_table_exists('storyboards') && sf_admin_column_exists('storyboards', 'producer_scene_status'); }
function sf_aisrq_seasons(): array { return function_exists('sf_story_v1_seasons') ? sf_story_v1_seasons() : []; }
function sf_aisrq_episodes(int $seasonId = 0): array { return function_exists('sf_story_v1_episodes') ? sf_story_v1_episodes($seasonId ?: 0) : []; }
function sf_aisrq_scene_rows(int $seasonId, int $episodeId, string $status): array {
  if (!sf_admin_table_exists('storyboards')) return [];
  $where = []; $params = [];
  if (sf_admin_column_exists('storyboards', 'story_season_id') && $seasonId > 0) { $where[] = 'story_season_id = ?'; $params[] = $seasonId; }
  if (sf_admin_column_exists('storyboards', 'story_episode_id') && $episodeId > 0) { $where[] = 'story_episode_id = ?'; $params[] = $episodeId; }
  if (sf_admin_column_exists('storyboards', 'producer_scene_status') && $status !== '') { $where[] = 'producer_scene_status = ?'; $params[] = $status; }
  elseif (sf_admin_column_exists('storyboards', 'producer_scene_status')) { $where[] = "producer_scene_status IN ('outline','needs_review','ready')"; }
  $sql = 'SELECT * FROM storyboards' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY COALESCE(updated_at, created_at) DESC, id DESC LIMIT 120';
  return sf_admin_fetch_all($sql, $params);
}
function sf_aisrq_counts(): array {
  $counts = ['outline'=>0,'needs_review'=>0,'ready'=>0,'all'=>0];
  if (!sf_admin_table_exists('storyboards') || !sf_admin_column_exists('storyboards','producer_scene_status')) return $counts;
  foreach (sf_admin_fetch_all('SELECT producer_scene_status status, COUNT(*) total FROM storyboards GROUP BY producer_scene_status') as $row) {
    $key = (string)($row['status'] ?? '');
    if (isset($counts[$key])) $counts[$key] = (int)($row['total'] ?? 0);
    $counts['all'] += (int)($row['total'] ?? 0);
  }
  return $counts;
}
function sf_aisrq_row_title(array $row): string { return sf_aisrq_text($row['title'] ?? $row['project_title'] ?? '', 'Storyboard #' . (int)($row['id'] ?? 0)); }
function sf_aisrq_status_badge(string $status): string { return sf_admin_status_badge($status === 'ready' ? 'active' : ($status ?: 'draft')); }

$seasons = sf_aisrq_seasons();
$seasonId = sf_admin_int($_GET['season_id'] ?? null, 0) ?? 0;
$episodes = sf_aisrq_episodes($seasonId);
$episodeId = sf_admin_int($_GET['episode_id'] ?? null, 0) ?? 0;
$status = trim((string)($_GET['status'] ?? 'needs_review'));
if (!array_key_exists($status, sf_aisrq_statuses()) && $status !== '') $status = 'needs_review';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'update_scene_status') {
  $storyboardId = sf_admin_int($_POST['storyboard_id'] ?? null, 0) ?? 0;
  $newStatus = trim((string)($_POST['new_status'] ?? ''));
  if (!sf_aisrq_ready()) {
    sf_admin_flash('error', 'Storyboard review status field is not available.');
  } elseif ($storyboardId <= 0 || !array_key_exists($newStatus, sf_aisrq_statuses())) {
    sf_admin_flash('error', 'Invalid review queue action.');
  } else {
    $before = sf_admin_fetch_one('SELECT * FROM storyboards WHERE id = ? LIMIT 1', [$storyboardId]);
    $ok = sf_admin_execute('UPDATE storyboards SET producer_scene_status = ?, updated_at = NOW() WHERE id = ?', [$newStatus, $storyboardId]);
    $after = sf_admin_fetch_one('SELECT * FROM storyboards WHERE id = ? LIMIT 1', [$storyboardId]);
    if ($ok) sf_admin_audit('ai_script_review_status_update', 'storyboard', $storyboardId, $before, $after);
    sf_admin_flash($ok ? 'success' : 'error', $ok ? 'Scene review status updated.' : 'Scene review status could not be updated.');
  }
  $qs = http_build_query(['season_id'=>sf_admin_int($_POST['season_id'] ?? null, 0) ?? 0, 'episode_id'=>sf_admin_int($_POST['episode_id'] ?? null, 0) ?? 0, 'status'=>$status]);
  sf_admin_redirect(sf_url('admin/ai-script-review-queue.php?' . $qs));
}

$rows = sf_aisrq_scene_rows($seasonId, $episodeId, $status);
$counts = sf_aisrq_counts();
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('AI Script Producer', 'Review queue', 'Review AI-created and AI-updated scene shells before production moves forward.', 'ai-script-review-queue');
?>
<style>
.ai-script-review-queue-page .sf-rq-hero,.ai-script-review-queue-page .sf-rq-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}.ai-script-review-queue-page .sf-rq-panel{padding:18px;border:1px solid rgba(232,198,127,.16);border-radius:20px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.025));box-shadow:0 18px 48px rgba(0,0,0,.18)}.ai-script-review-queue-page .sf-rq-panel h2{margin:8px 0;color:#fff;font-size:clamp(26px,4vw,48px);letter-spacing:-.045em;line-height:1}.ai-script-review-queue-page .sf-rq-copy,.ai-script-review-queue-page .sf-rq-panel p{color:rgba(255,255,255,.68);line-height:1.55}.ai-script-review-queue-page .sf-rq-context-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.ai-script-review-queue-page .sf-rq-card-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.ai-script-review-queue-page .sf-rq-card{padding:16px;border:1px solid rgba(255,255,255,.08);border-radius:18px;background:rgba(0,0,0,.15)}.ai-script-review-queue-page .sf-rq-card h3{color:#fff;margin:10px 0 8px}.ai-script-review-queue-page .sf-rq-meta{display:flex;flex-wrap:wrap;gap:6px;margin:10px 0}.ai-script-review-queue-page .sf-rq-meta small{border:1px solid rgba(232,198,127,.18);border-radius:999px;padding:4px 8px;color:#f5d98d;font-size:11px;font-weight:900}@media(max-width:1080px){.ai-script-review-queue-page .sf-rq-hero,.ai-script-review-queue-page .sf-rq-stats,.ai-script-review-queue-page .sf-rq-card-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:720px){.ai-script-review-queue-page .sf-rq-hero,.ai-script-review-queue-page .sf-rq-stats,.ai-script-review-queue-page .sf-rq-card-grid,.ai-script-review-queue-page .sf-rq-context-grid{grid-template-columns:1fr}}
</style>
<section class="sf-rq-hero"><div class="sf-rq-panel" style="grid-column:span 3"><span class="sf-panel-eyebrow">Phase 8</span><h2>AI scene review queue</h2><p>Review AI-created, AI-updated, and batch-created scene shells before moving them forward in production.</p></div><div class="sf-rq-panel"><span class="sf-panel-eyebrow">Guardrail</span><h2>No publish</h2><p>Status review only. No deletes, no publishing, no media generation, no messages.</p></div></section>
<?php if (!sf_aisrq_ready()): ?><section class="sf-story-v1-warning"><strong>Review status unavailable:</strong> Storyboards must include <code>producer_scene_status</code> for this review queue to update statuses.</section><?php endif; ?>
<section class="sf-rq-stats"><div class="sf-rq-panel"><span class="sf-panel-eyebrow">Needs Review</span><h2><?= (int)$counts['needs_review'] ?></h2></div><div class="sf-rq-panel"><span class="sf-panel-eyebrow">Outline</span><h2><?= (int)$counts['outline'] ?></h2></div><div class="sf-rq-panel"><span class="sf-panel-eyebrow">Ready</span><h2><?= (int)$counts['ready'] ?></h2></div><div class="sf-rq-panel"><span class="sf-panel-eyebrow">Tracked</span><h2><?= (int)$counts['all'] ?></h2></div></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Filters</span><h2>Review scope</h2></div><div><a href="<?= sf_url('admin/ai-script-assistant.php') ?>">AI Script Producer</a> · <a href="<?= sf_url('admin/ai-script-batch-scenes.php') ?>">Batch Scenes</a></div></div><form method="get" class="sf-admin-form"><div class="sf-rq-context-grid"><label>Season<select name="season_id" onchange="this.form.submit()"><option value="0">All seasons</option><?php foreach ($seasons as $season): ?><option value="<?= (int)$season['id'] ?>"<?= (int)$season['id'] === $seasonId ? ' selected' : '' ?>><?= sf_admin_h($season['title'] ?? 'Season') ?></option><?php endforeach; ?></select></label><label>Episode<select name="episode_id" onchange="this.form.submit()"><option value="0">All episodes</option><?php foreach ($episodes as $episode): ?><option value="<?= (int)$episode['id'] ?>"<?= (int)$episode['id'] === $episodeId ? ' selected' : '' ?>>Episode <?= (int)($episode['episode_number'] ?? 1) ?> — <?= sf_admin_h($episode['title'] ?? 'Episode') ?></option><?php endforeach; ?></select></label><label>Status<select name="status" onchange="this.form.submit()"><option value="">All review statuses</option><?php foreach (sf_aisrq_statuses() as $key => $label): ?><option value="<?= sf_admin_h($key) ?>"<?= $status === $key ? ' selected' : '' ?>><?= sf_admin_h($label) ?></option><?php endforeach; ?></select></label></div></form></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Scene Cards</span><h2><?= count($rows) ?> scene(s)</h2></div></div><?php if (!$rows): ?><p class="sf-rq-copy">No scenes match this review scope.</p><?php else: ?><div class="sf-rq-card-grid"><?php foreach ($rows as $row): $rowStatus = (string)($row['producer_scene_status'] ?? 'outline'); ?><article class="sf-rq-card"><?= sf_aisrq_status_badge($rowStatus) ?><h3><?= sf_admin_h(sf_aisrq_row_title($row)) ?></h3><p class="sf-rq-copy"><?= sf_admin_h(sf_aisrq_snip($row['short_prompt'] ?? $row['source_script'] ?? $row['tone'] ?? '', 220, 'No prompt summary saved.')) ?></p><div class="sf-rq-meta"><small>ID: <?= (int)($row['id'] ?? 0) ?></small><?php if (!empty($row['story_episode_id'])): ?><small>Episode ID: <?= (int)$row['story_episode_id'] ?></small><?php endif; ?><?php if (!empty($row['updated_at'])): ?><small>Updated: <?= sf_admin_h($row['updated_at']) ?></small><?php endif; ?></div><form method="post" class="sf-admin-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="update_scene_status"><input type="hidden" name="storyboard_id" value="<?= (int)($row['id'] ?? 0) ?>"><input type="hidden" name="season_id" value="<?= (int)$seasonId ?>"><input type="hidden" name="episode_id" value="<?= (int)$episodeId ?>"><label>Move to<select name="new_status"><?php foreach (sf_aisrq_statuses() as $key => $label): ?><option value="<?= sf_admin_h($key) ?>"<?= $rowStatus === $key ? ' selected' : '' ?>><?= sf_admin_h($label) ?></option><?php endforeach; ?></select></label><div class="sf-admin-form-actions"><button type="submit"<?= sf_aisrq_ready() ? '' : ' disabled' ?>>Update Status</button><a href="<?= sf_url('admin/ai-script-assistant.php?season_id=' . (int)($row['story_season_id'] ?? 0) . '&episode_id=' . (int)($row['story_episode_id'] ?? 0) . '&scene_id=' . (int)($row['id'] ?? 0)) ?>">Open in AI Producer</a></div></form></article><?php endforeach; ?></div><?php endif; ?></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Safety</span><h2>Review-only controls</h2></div></div><p class="sf-rq-copy">This phase only changes the producer review status on selected storyboard scene shells. It does not publish, delete, generate media, queue messages, or overwrite scene content.</p></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
