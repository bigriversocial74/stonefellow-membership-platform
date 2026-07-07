<?php
$pageTitle = 'Storyboards';
$pageDescription = 'Storyboard project list and visual screenplay workspace entry point.';
$pageClass = 'membership-page admin-catalog-page storyboards-page';
require __DIR__ . '/../includes/storyboards.php';
$projects = sf_storyboard_projects();
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Storyboarding', 'Storyboard projects', 'Create and manage AI-assisted 9-scene visual screenplay projects. API keys are managed in admin settings and never shown in this workspace.', 'storyboards');
?>
<section class="sf-admin-card-grid">
  <a class="sf-admin-action-card" href="<?= sf_url('admin/storyboard-builder.php') ?>"><span>Create</span><strong>New Storyboard</strong><small>Start from a script prompt and generate a 9-scene screenplay plan.</small></a>
  <div class="sf-admin-action-card"><span>Projects</span><strong><?= count($projects) ?></strong><small>Draft shell projects in this starter module.</small></div>
  <div class="sf-admin-action-card"><span>AI Provider</span><strong>Admin Managed</strong><small>Claude/ChatGPT keys belong in the admin AI settings phase.</small></div>
  <div class="sf-admin-action-card"><span>Phase</span><strong>40</strong><small>UI shell only. No live AI calls or SQL migration yet.</small></div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head">
    <div><span class="sf-panel-eyebrow">Project List</span><h2>Storyboard workspace</h2></div>
    <a href="<?= sf_url('docs/PHASE_40_STORYBOARDING_MODULE.md') ?>">Phase Docs</a>
  </div>
  <div class="sf-admin-table-wrap">
    <table class="sf-admin-table">
      <thead><tr><th>Storyboard</th><th>Status</th><th>Scenes</th><th>Characters</th><th>Updated</th><th>Open</th></tr></thead>
      <tbody>
        <?php foreach ($projects as $project): ?>
          <tr>
            <td><strong><?= sf_storyboard_h($project['title']) ?></strong><small><?= sf_storyboard_h($project['genre']) ?></small></td>
            <td><?= sf_admin_status_badge(sf_storyboard_status_label($project['status'])) ?></td>
            <td><?= (int)$project['completed_scenes'] ?> / <?= (int)$project['scene_count'] ?></td>
            <td><?= (int)$project['characters'] ?></td>
            <td><?= sf_storyboard_h($project['updated_at']) ?></td>
            <td><a href="<?= sf_url('admin/storyboard-builder.php?project_id=' . (int)$project['id']) ?>">Open Builder</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Build Order</span><h2>Production path</h2></div><span class="sf-admin-mini-pill">No SQL in Phase 40</span></div>
  <div class="sf-admin-roadmap">
    <div><span>40</span><strong>Module Shell</strong><p>Storyboard list, builder layout, prompt, settings, characters, and 9-scene grid.</p></div>
    <div><span>41</span><strong>SQL + Admin AI Settings</strong><p>Persist storyboards, scenes, characters, references, jobs, provider settings, and usage limits.</p></div>
    <div><span>42</span><strong>Script Generation API</strong><p>Generate structured 9-scene screenplay data from a basic user prompt.</p></div>
    <div><span>43</span><strong>Images + Scene Actions</strong><p>Generate, upload, rewrite, and regenerate scene visuals with character reference guidance.</p></div>
  </div>
</section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
