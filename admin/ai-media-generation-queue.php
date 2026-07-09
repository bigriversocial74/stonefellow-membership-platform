<?php
$pageTitle = 'AI Media Generation Queue';
$pageDescription = 'Queue approved media prompts for later generation without calling providers.';
$pageClass = 'membership-page admin-catalog-page storyboarding-system-page ai-media-generation-queue-page';
require __DIR__ . '/../includes/storyboards.php';
require_once __DIR__ . '/../includes/storyboarding_system.php';
require_once __DIR__ . '/../includes/admin_catalog.php';

function sf_aimg_text($value, string $fallback = 'Not set'): string { $text = trim((string)($value ?? '')); return $text === '' ? $fallback : $text; }
function sf_aimg_snip($value, int $length = 220, string $fallback = 'Not set'): string { $text = sf_aimg_text($value, $fallback); return strlen($text) > $length ? rtrim(substr($text, 0, $length - 1)) . '…' : $text; }
function sf_aimg_ready(): bool { return sf_admin_table_exists('story_ai_media_generation_jobs'); }
function sf_aimg_prompt_ready(): bool { return sf_admin_table_exists('story_ai_media_prompts'); }
function sf_aimg_statuses(): array { return ['queued'=>'Queued','blocked'=>'Blocked','needs_review'=>'Needs Review','generated'=>'Generated','failed'=>'Failed','cancelled'=>'Cancelled']; }
function sf_aimg_prompt_statuses(): array { return ['ready_for_generation','approved']; }
function sf_aimg_prompts(): array {
  if (!sf_aimg_prompt_ready()) return [];
  return sf_admin_fetch_all("SELECT p.*, s.title AS storyboard_title FROM story_ai_media_prompts p LEFT JOIN storyboards s ON s.id = p.storyboard_id WHERE p.status IN ('ready_for_generation','approved') ORDER BY FIELD(p.status,'ready_for_generation','approved'), p.updated_at DESC, p.id DESC LIMIT 120");
}
function sf_aimg_jobs(): array {
  if (!sf_aimg_ready()) return [];
  return sf_admin_fetch_all('SELECT j.*, p.status AS prompt_status FROM story_ai_media_generation_jobs j LEFT JOIN story_ai_media_prompts p ON p.id = j.media_prompt_id ORDER BY j.created_at DESC, j.id DESC LIMIT 120');
}
function sf_aimg_counts(): array {
  $counts = ['queued'=>0,'blocked'=>0,'needs_review'=>0,'generated'=>0,'failed'=>0,'cancelled'=>0,'all'=>0];
  if (!sf_aimg_ready()) return $counts;
  foreach (sf_admin_fetch_all('SELECT generation_status status, COUNT(*) total FROM story_ai_media_generation_jobs GROUP BY generation_status') as $row) { $key = (string)($row['status'] ?? ''); if (isset($counts[$key])) $counts[$key] = (int)($row['total'] ?? 0); $counts['all'] += (int)($row['total'] ?? 0); }
  return $counts;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'queue_generation_request') {
  $promptId = sf_admin_int($_POST['prompt_id'] ?? null, 0) ?? 0;
  $notes = sf_admin_nullable_string($_POST['request_notes'] ?? '');
  if (!sf_aimg_ready() || !sf_aimg_prompt_ready()) {
    sf_admin_flash('error', 'Generation queue SQL is not ready. Import the required SQL migrations first.');
  } elseif ($promptId <= 0) {
    sf_admin_flash('error', 'Choose a media prompt before queuing a generation request.');
  } else {
    $prompt = sf_admin_fetch_one("SELECT * FROM story_ai_media_prompts WHERE id = ? AND status IN ('ready_for_generation','approved') LIMIT 1", [$promptId]);
    if (!$prompt) {
      sf_admin_flash('error', 'Only approved or ready-for-generation prompts can be queued.');
    } else {
      $duplicate = sf_admin_fetch_one("SELECT id FROM story_ai_media_generation_jobs WHERE media_prompt_id = ? AND generation_status IN ('queued','blocked','needs_review') LIMIT 1", [$promptId]);
      if ($duplicate) {
        sf_admin_flash('error', 'This prompt already has an active generation request.');
      } else {
        $userId = function_exists('sf_current_user_id') ? sf_current_user_id() : null;
        $ok = sf_admin_execute('INSERT INTO story_ai_media_generation_jobs (media_prompt_id, storyboard_id, story_season_id, story_episode_id, prompt_type, prompt_title, prompt_body, provider_hint, aspect_ratio, generation_status, request_notes, requested_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [(int)$prompt['id'], (int)$prompt['storyboard_id'], $prompt['story_season_id'] ?: null, $prompt['story_episode_id'] ?: null, $prompt['prompt_type'], $prompt['prompt_title'], $prompt['prompt_body'], $prompt['provider_hint'], $prompt['aspect_ratio'], 'queued', $notes, $userId]);
        if ($ok) sf_admin_audit('ai_media_generation_request_queued', 'story_ai_media_prompt', (int)$prompt['id'], null, ['storyboard_id'=>(int)$prompt['storyboard_id'],'prompt_type'=>$prompt['prompt_type']]);
        sf_admin_flash($ok ? 'success' : 'error', $ok ? 'Generation request queued. No provider was called.' : 'Generation request could not be queued.');
      }
    }
  }
  sf_admin_redirect(sf_url('admin/ai-media-generation-queue.php'));
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'update_job_status') {
  $jobId = sf_admin_int($_POST['job_id'] ?? null, 0) ?? 0;
  $status = trim((string)($_POST['generation_status'] ?? ''));
  if (!sf_aimg_ready() || $jobId <= 0 || !array_key_exists($status, sf_aimg_statuses())) {
    sf_admin_flash('error', 'Invalid generation request status action.');
  } else {
    $before = sf_admin_fetch_one('SELECT * FROM story_ai_media_generation_jobs WHERE id = ? LIMIT 1', [$jobId]);
    $userId = function_exists('sf_current_user_id') ? sf_current_user_id() : null;
    $ok = sf_admin_execute('UPDATE story_ai_media_generation_jobs SET generation_status = ?, reviewed_by_user_id = ?, reviewed_at = NOW() WHERE id = ?', [$status, $userId, $jobId]);
    $after = sf_admin_fetch_one('SELECT * FROM story_ai_media_generation_jobs WHERE id = ? LIMIT 1', [$jobId]);
    if ($ok) sf_admin_audit('ai_media_generation_request_status_update', 'story_ai_media_generation_job', $jobId, $before, $after);
    sf_admin_flash($ok ? 'success' : 'error', $ok ? 'Generation request status updated.' : 'Generation request status could not be updated.');
  }
  sf_admin_redirect(sf_url('admin/ai-media-generation-queue.php'));
}

$prompts = sf_aimg_prompts();
$jobs = sf_aimg_jobs();
$counts = sf_aimg_counts();
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('AI Script Producer', 'Media generation queue', 'Queue approved media prompts for later generation without calling providers.', 'ai-media-generation-queue');
?>
<style>
.ai-media-generation-queue-page .sf-gen-hero,.ai-media-generation-queue-page .sf-gen-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}.ai-media-generation-queue-page .sf-gen-panel{padding:20px;border:1px solid rgba(232,198,127,.16);border-radius:22px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.025));box-shadow:0 20px 56px rgba(0,0,0,.22)}.ai-media-generation-queue-page .sf-gen-panel h2{margin:8px 0;color:#fff;font-size:clamp(30px,4vw,56px);letter-spacing:-.05em;line-height:.98}.ai-media-generation-queue-page .sf-gen-copy,.ai-media-generation-queue-page .sf-gen-panel p{color:rgba(255,255,255,.68);line-height:1.55}.ai-media-generation-queue-page .sf-gen-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.ai-media-generation-queue-page .sf-gen-card{padding:16px;border:1px solid rgba(255,255,255,.08);border-radius:18px;background:rgba(0,0,0,.15)}.ai-media-generation-queue-page .sf-gen-card h3{color:#fff;margin:10px 0 8px}.ai-media-generation-queue-page textarea{width:100%;min-height:90px;resize:vertical}.ai-media-generation-queue-page .sf-gen-pill{display:inline-block;margin-top:8px;border:1px solid rgba(232,198,127,.18);border-radius:999px;padding:4px 8px;color:#f5d98d;font-size:11px;font-weight:900}@media(max-width:1080px){.ai-media-generation-queue-page .sf-gen-hero,.ai-media-generation-queue-page .sf-gen-stats,.ai-media-generation-queue-page .sf-gen-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:720px){.ai-media-generation-queue-page .sf-gen-hero,.ai-media-generation-queue-page .sf-gen-stats,.ai-media-generation-queue-page .sf-gen-grid{grid-template-columns:1fr}}
</style>
<section class="sf-gen-hero"><div class="sf-gen-panel" style="grid-column:span 3"><span class="sf-panel-eyebrow">Phase 12</span><h2>Generation request queue</h2><p>Move approved media prompts into a controlled queue for future generation review. This is the last gate before provider integration.</p></div><div class="sf-gen-panel"><span class="sf-panel-eyebrow">Guardrail</span><h2>No provider calls</h2><p>Queue records only. No image, video, audio, upload, or publish action runs here.</p></div></section>
<?php if (!sf_aimg_prompt_ready()): ?><section class="sf-story-v1-warning"><strong>SQL required:</strong> Import <code>database/story_ai_media_prompts.sql</code> first.</section><?php endif; ?>
<?php if (!sf_aimg_ready()): ?><section class="sf-story-v1-warning"><strong>SQL required:</strong> Import <code>database/story_ai_media_generation_jobs.sql</code> before queuing generation requests.</section><?php endif; ?>
<section class="sf-gen-stats"><div class="sf-gen-panel"><span class="sf-panel-eyebrow">Queued</span><h2><?= (int)$counts['queued'] ?></h2></div><div class="sf-gen-panel"><span class="sf-panel-eyebrow">Needs Review</span><h2><?= (int)$counts['needs_review'] ?></h2></div><div class="sf-gen-panel"><span class="sf-panel-eyebrow">Generated</span><h2><?= (int)$counts['generated'] ?></h2></div><div class="sf-gen-panel"><span class="sf-panel-eyebrow">Tracked</span><h2><?= (int)$counts['all'] ?></h2></div></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Eligible Prompts</span><h2><?= count($prompts) ?> approved prompt(s)</h2></div><div><a href="<?= sf_url('admin/ai-script-media-prep.php') ?>">Media Prep Queue</a> · <a href="<?= sf_url('admin/ai-script-shot-list.php') ?>">Shot List</a></div></div><?php if (!$prompts): ?><p class="sf-gen-copy">No approved or ready-for-generation media prompts are available yet.</p><?php else: ?><div class="sf-gen-grid"><?php foreach ($prompts as $prompt): ?><article class="sf-gen-card"><?= sf_admin_status_badge((string)($prompt['status'] ?? 'approved')) ?><h3><?= sf_admin_h($prompt['prompt_title'] ?? 'Prompt') ?></h3><p class="sf-gen-copy"><strong><?= sf_admin_h($prompt['prompt_type'] ?? 'prompt') ?></strong> · <?= sf_admin_h($prompt['provider_hint'] ?? 'provider') ?> · <?= sf_admin_h($prompt['aspect_ratio'] ?? 'ratio') ?><br><?= sf_admin_h(sf_aimg_text($prompt['storyboard_title'] ?? '', 'Storyboard #' . (int)($prompt['storyboard_id'] ?? 0))) ?></p><p class="sf-gen-copy"><?= sf_admin_h(sf_aimg_snip($prompt['prompt_body'] ?? '', 200)) ?></p><form method="post" class="sf-admin-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="queue_generation_request"><input type="hidden" name="prompt_id" value="<?= (int)($prompt['id'] ?? 0) ?>"><label>Request notes<textarea name="request_notes" placeholder="Optional generation notes for the future provider step."></textarea></label><div class="sf-admin-form-actions"><button type="submit"<?= (sf_aimg_ready() && sf_aimg_prompt_ready()) ? '' : ' disabled' ?>>Queue Request</button></div></form></article><?php endforeach; ?></div><?php endif; ?></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Generation Queue</span><h2><?= count($jobs) ?> request(s)</h2></div></div><?php if (!$jobs): ?><p class="sf-gen-copy">No generation requests have been queued yet.</p><?php else: ?><div class="sf-gen-grid"><?php foreach ($jobs as $job): ?><article class="sf-gen-card"><?= sf_admin_status_badge((string)($job['generation_status'] ?? 'queued')) ?><h3><?= sf_admin_h($job['prompt_title'] ?? 'Generation Request') ?></h3><p class="sf-gen-copy"><strong><?= sf_admin_h($job['prompt_type'] ?? 'prompt') ?></strong> · <?= sf_admin_h($job['provider_hint'] ?? 'provider') ?> · <?= sf_admin_h($job['aspect_ratio'] ?? 'ratio') ?><br>Prompt #<?= (int)($job['media_prompt_id'] ?? 0) ?> · Scene #<?= (int)($job['storyboard_id'] ?? 0) ?></p><p class="sf-gen-copy"><?= sf_admin_h(sf_aimg_snip($job['prompt_body'] ?? '', 190)) ?></p><?php if (!empty($job['request_notes'])): ?><p class="sf-gen-copy"><strong>Notes:</strong> <?= sf_admin_h(sf_aimg_snip($job['request_notes'], 150)) ?></p><?php endif; ?><form method="post" class="sf-admin-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="update_job_status"><input type="hidden" name="job_id" value="<?= (int)($job['id'] ?? 0) ?>"><label>Status<select name="generation_status"><?php foreach (sf_aimg_statuses() as $key => $label): ?><option value="<?= sf_admin_h($key) ?>"<?= (string)($job['generation_status'] ?? '') === $key ? ' selected' : '' ?>><?= sf_admin_h($label) ?></option><?php endforeach; ?></select></label><div class="sf-admin-form-actions"><button type="submit">Update Status</button></div></form></article><?php endforeach; ?></div><?php endif; ?></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Safety</span><h2>Queue-only controls</h2></div></div><p class="sf-gen-copy">This phase creates and manages generation request records only. Future phases can add provider-specific execution, generated asset review, and publishing approval after this queue is stable.</p></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
