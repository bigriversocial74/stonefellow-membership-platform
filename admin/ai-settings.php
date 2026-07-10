<?php
$pageTitle = 'AI Settings';
$pageDescription = 'Admin-only AI provider settings for storyboarding generation, scene rewriting, image generation, usage limits, and secure API key storage.';
$pageClass = 'membership-page admin-catalog-page ai-settings-page';
require __DIR__ . '/../includes/ai_settings.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) {
    sf_admin_flash('error', 'Security check failed. Refresh and try again.');
    sf_admin_redirect();
  }
  if (!sf_ai_ready()) {
    sf_admin_flash('warning', 'Run migration 021 before saving AI settings. Static defaults are still being used.');
    sf_admin_redirect();
  }
  if (sf_ai_save_provider($_POST)) sf_admin_flash('success', 'AI provider settings saved.');
  else sf_admin_flash('error', 'AI provider settings could not be saved.');
  sf_admin_redirect();
}

$providers = sf_ai_providers();
$usage = sf_ai_usage_summary();
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('AI Settings', 'Admin AI provider settings', 'Manage Claude and ChatGPT provider settings for storyboarding. API keys are admin-only and never appear in the creator storyboard workspace.', 'ai-settings');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>Providers</span><strong><?= count($providers) ?></strong><small>Claude and ChatGPT provider records.</small></div>
  <div class="sf-admin-action-card"><span>Security</span><strong><?= sf_ai_crypto_ready() ? 'Encryption Ready' : 'Needs OpenSSL' ?></strong><small>API keys require AES-256-GCM support before saving.</small></div>
  <div class="sf-admin-action-card"><span>This Month</span><strong><?= (int)$usage['events'] ?></strong><small><?= (int)$usage['tokens'] ?> tokens · <?= (int)$usage['images'] ?> images · <?= sf_ai_format_cents($usage['cost_cents']) ?> est.</small></div>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/ai-staging-certification.php') ?>"><span>Staging Gate</span><strong>Certification</strong><small>Test providers, limits, locks, rollback, permissions, and costs.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/storyboards.php') ?>"><span>Storyboards</span><strong>Workspace</strong><small>Creator-facing module stays free of API key fields.</small></a>
</section>

<?php if (!sf_ai_ready()): ?>
  <div class="sf-admin-alert sf-admin-alert-warning">Migration 021 is required before AI provider settings can be saved. The page is displaying static provider defaults.</div>
<?php endif; ?>
<?php if (!sf_ai_crypto_ready()): ?>
  <div class="sf-admin-alert sf-admin-alert-error">OpenSSL with AES-256-GCM support and a dedicated 32+ character <code>SF_AI_SETTINGS_SECRET</code> are required before API keys can be saved securely.</div>
<?php endif; ?>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Provider Configuration</span><h2>Claude + ChatGPT keys</h2></div><span class="sf-admin-mini-pill">Admin Only</span></div>
  <div class="sf-admin-card-grid">
    <?php foreach ($providers as $provider): ?>
      <article class="sf-admin-action-card">
        <span><?= sf_ai_h($provider['provider_type'] ?? 'text') ?></span>
        <strong><?= sf_ai_h($provider['provider_label'] ?? $provider['provider_key'] ?? '') ?></strong>
        <small>Key: <?= sf_ai_h(sf_ai_mask_key($provider['api_key_last4'] ?? '', $provider['api_key_hint'] ?? '')) ?></small>
        <small>Status: <?= sf_ai_status_badge((string)($provider['key_status'] ?? 'missing')) ?> <?= sf_ai_status_badge((string)($provider['status'] ?? 'inactive')) ?></small>
        <small>Model: <?= sf_ai_h($provider['default_model'] ?? '—') ?><?= !empty($provider['image_model']) ? ' · Image: ' . sf_ai_h($provider['image_model']) : '' ?></small>
        <small>Last test: <?= sf_ai_h($provider['test_status'] ?? 'not tested') ?><?= !empty($provider['tested_at']) ? ' · ' . sf_ai_h($provider['tested_at']) : '' ?></small>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<?php foreach ($providers as $provider): ?>
  <section class="sf-admin-panel">
    <div class="sf-admin-panel-head">
      <div><span class="sf-panel-eyebrow">Provider</span><h2><?= sf_ai_h($provider['provider_label'] ?? $provider['provider_key']) ?></h2></div>
      <span class="sf-admin-mini-pill"><?= sf_ai_h($provider['provider_key'] ?? '') ?></span>
    </div>
    <form class="sf-admin-form" method="post">
      <?= sf_csrf_field() ?>
      <input type="hidden" name="provider_key" value="<?= sf_ai_h($provider['provider_key'] ?? '') ?>">
      <div class="sf-admin-form-grid">
        <label>Provider Label<input name="provider_label" value="<?= sf_ai_h($provider['provider_label'] ?? '') ?>"<?= sf_admin_form_disabled_attr() ?>></label>
        <label>Provider Type<?= sf_admin_select('provider_type', ['text'=>'Text','image'=>'Image','multimodal'=>'Multimodal'], $provider['provider_type'] ?? 'text') ?></label>
        <label>Status<?= sf_admin_select('status', ['active'=>'Active','inactive'=>'Inactive','disabled'=>'Disabled'], $provider['status'] ?? 'inactive') ?></label>
      </div>
      <div class="sf-admin-form-grid">
        <label>Default Text Model<input name="default_model" value="<?= sf_ai_h($provider['default_model'] ?? '') ?>" placeholder="gpt-4.1 or claude-3-5-sonnet-latest"<?= sf_admin_form_disabled_attr() ?>></label>
        <label>Image Model<input name="image_model" value="<?= sf_ai_h($provider['image_model'] ?? '') ?>" placeholder="gpt-image-1"<?= sf_admin_form_disabled_attr() ?>></label>
        <label>New API Key<input type="password" name="api_key" value="" placeholder="Leave blank to keep existing key"<?= sf_admin_form_disabled_attr() ?>></label>
      </div>
      <div class="sf-admin-form-grid">
        <label>Monthly Budget Cents<input type="number" min="0" name="monthly_budget_cents" value="<?= (int)($provider['monthly_budget_cents'] ?? 0) ?>"<?= sf_admin_form_disabled_attr() ?>></label>
        <label>Monthly Token Limit<input type="number" min="0" name="monthly_token_limit" value="<?= (int)($provider['monthly_token_limit'] ?? 0) ?>"<?= sf_admin_form_disabled_attr() ?>></label>
        <label>Monthly Image Limit<input type="number" min="0" name="monthly_image_limit" value="<?= (int)($provider['monthly_image_limit'] ?? 0) ?>"<?= sf_admin_form_disabled_attr() ?>></label>
      </div>
      <div class="sf-admin-form-grid">
        <label>Timeout Seconds<input type="number" min="10" max="300" name="timeout_seconds" value="<?= (int)($provider['timeout_seconds'] ?? 90) ?>"<?= sf_admin_form_disabled_attr() ?>></label>
        <label>Max Retries<input type="number" min="0" max="5" name="max_retries" value="<?= (int)($provider['max_retries'] ?? 2) ?>"<?= sf_admin_form_disabled_attr() ?>></label>
        <label>Temperature<input type="number" min="0" max="2" step="0.01" name="temperature" value="<?= sf_ai_h($provider['temperature'] ?? '0.70') ?>"<?= sf_admin_form_disabled_attr() ?>></label>
      </div>
      <label class="sf-admin-check"><input type="checkbox" name="is_default_text" value="1" <?= !empty($provider['is_default_text']) ? 'checked' : '' ?><?= sf_admin_form_disabled_attr() ?>> Default text/story provider</label>
      <label class="sf-admin-check"><input type="checkbox" name="is_default_image" value="1" <?= !empty($provider['is_default_image']) ? 'checked' : '' ?><?= sf_admin_form_disabled_attr() ?>> Default image provider</label>
      <div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>>Save <?= sf_ai_h($provider['provider_label'] ?? 'Provider') ?></button><a href="<?= sf_url('admin/ai-staging-certification.php') ?>">Open staging certification</a></div>
    </form>
  </section>
<?php endforeach; ?>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Storyboarding Boundary</span><h2>Where these settings are used</h2></div><a href="<?= sf_url('docs/PHASE_41_STORYBOARDING_SQL_AI_SETTINGS.md') ?>">Phase Docs</a></div>
  <div class="sf-admin-roadmap">
    <div><span>Admin</span><strong>Keys stay here</strong><p>Claude and ChatGPT API keys are managed only in this admin screen.</p></div>
    <div><span>Creator</span><strong>No credentials</strong><p>The storyboard builder only shows “AI Provider: Admin Managed”.</p></div>
    <div><span>Certification</span><strong>Staging first</strong><p>Provider connections, locks, rollback, and cost controls must pass staging certification.</p></div>
    <div><span>Usage</span><strong>Cost tracking</strong><p>Usage events track tokens, images, request status, and conservative cost reservations.</p></div>
  </div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
