<?php
$pageTitle = 'Admin Foundation';
$pageDescription = 'Stonefellow admin foundation for storyboarding, queue/export, character management, scene actions, AI provider settings, production monitoring, incidents, system alerts, backup restore, release management, release candidate handoff, roles, permissions, security audit, automation scheduler, member messaging, lifecycle, support, membership tiers, revenue dashboard, engagement analytics, content, payments, publishing, package readiness, smoke tests, and deployment readiness.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Admin Foundation', 'Operational build control', 'Manage storyboarding, queue/export, character management, scene actions, AI provider settings, release candidate handoff, monitoring, incidents, alerts, backups, releases, package readiness, smoke tests, security, automation, member messaging, lifecycle, support, revenue, content, payments, publishing, and delivery.', 'index');
$adminSections = [
  'Launch Gate' => [
    ['RC','Final Handoff','Release candidate score, deploy ZIP, SQL target, backup/release status, and final launch gate.','admin/release-candidate.php'],
    ['QA','Production Readiness','Launch scoring, route checks, security checks, and content audit.','admin/qa.php'],
    ['Checklist','Final Launch Path','Install, migration, route, security, backup, monitoring, smoke test, and preflight sequence.','admin/launch-checklist.php'],
    ['Package','Readiness','Deployable script package checks, required file manifest, SQL target, and final handoff gates.','admin/package-readiness.php'],
    ['Smoke','Scenario Matrix','Auth, member, media, commerce, admin ops, API, monitoring, incident, backup, and release smoke tests.','admin/smoke-tests.php'],
    ['Routes','Registry v2','Public, member, admin, API, media, deployment, monitoring, incident, backup, and release routes.','admin/routes-checker.php'],
    ['Migrations','Through 021','Base schema plus migrations 001 through 021.','admin/migration-checker.php'],
  ],
  'Production Operations' => [
    ['Monitoring','Error Center','Health snapshots, failed jobs, failed email, payments, service checks, and manual error capture.','admin/monitoring.php'],
    ['Incidents','Alerts','Incident workflow, severity, alert routing, admin notifications, and event timeline.','admin/incidents.php'],
    ['Backup','Restore Manager','Backup records, readiness checks, manifests, storage coverage, and verification status.','admin/backups.php'],
    ['Release','Deploy Manager','Release records, checklist tasks, migration range, preflight link, rollback notes, and release events.','admin/releases.php'],
  ],
  'Security + Access' => [
    ['Security','Dashboard','Roles, sessions, audit events, route protection, and hardening checks.','admin/security-dashboard.php'],
    ['Roles','Permissions','Admin role matrix and user role assignments.','admin/roles.php'],
    ['Members','Lifecycle Ops','Subscriber segments, churn risk, notes, retention tasks, and support context.','admin/member-lifecycle.php'],
    ['Support','Help Desk','Member tickets, replies, priority workflow, and linked account context.','admin/support.php'],
  ],
  'Content + Revenue' => [
    ['Storyboarding','Combined System','Season, episode, scene sheet, draggable scene card, character catalog, and AI storyboard generation in one workspace.','admin/storyboards.php'],
    ['Characters','Catalog','Main character profiles, motivations, relationships, arcs, images, and scene appearance counts.','admin/story-characters.php'],
    ['AI','Provider Settings','Admin-only Claude/ChatGPT API keys, defaults, usage limits, cost tracking, and secure key status.','admin/ai-settings.php'],
    ['Media','Catalog','Albums, songs, episodes, videos, assets, publishing, and secure delivery.','admin/music.php'],
    ['Revenue','Launch Dashboard','MRR, ARR, checkout conversion, merch, churn risk, and snapshots.','admin/revenue-dashboard.php'],
    ['Engagement','Analytics','Feed saves, hides, follows, comments, reactions, and top members.','admin/engagement-analytics.php'],
    ['Messaging','Campaigns','Segmented member notices, in-app inbox, email queue, and delivery tracking.','admin/member-messaging.php'],
  ],
];
?>
<?php foreach ($adminSections as $sectionTitle => $cards): ?>
  <section class="sf-admin-dashboard-section"><div class="sf-admin-section-title"><div><span class="sf-panel-eyebrow"><?= sf_admin_h($sectionTitle) ?></span><h2><?= sf_admin_h($sectionTitle) ?></h2></div><span class="sf-admin-mini-pill">Launch v2</span></div><div class="sf-admin-card-grid"><?php foreach ($cards as $card): ?><a class="sf-admin-action-card" href="<?= sf_url($card[3]) ?>"><span><?= sf_admin_h($card[0]) ?></span><strong><?= sf_admin_h($card[1]) ?></strong><small><?= sf_admin_h($card[2]) ?></small></a><?php endforeach; ?></div></section>
<?php endforeach; ?>

<section class="sf-admin-panel">
  <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Streaming Platform Foundation</span><h2>What is now built</h2></div><span class="sf-admin-mini-pill">Phases 1–46</span></div>
  <div class="sf-admin-roadmap">
    <div><span>✓</span><strong>Production Monitoring / Error Log Center v1</strong><p>Health snapshots, service checks, runtime metrics, failed notification/job/payment counters, error records, and monitoring APIs.</p></div>
    <div><span>✓</span><strong>System Notifications + Incident Alerts v1</strong><p>Incident records, incident timeline, severity workflow, alert rules, admin alert inbox, and email/in-app alert routing.</p></div>
    <div><span>✓</span><strong>Production Backup / Restore Manager v1</strong><p>Backup profiles, run records, schema manifests, storage coverage, restore readiness checks, and verified-run tracking.</p></div>
    <div><span>✓</span><strong>Deployment Release Manager v1</strong><p>Release records, deployment checklist, migration range, backup links, preflight link, release events, and rollback notes.</p></div>
    <div><span>✓</span><strong>Storyboarding Module Shell v1</strong><p>Storyboard list, builder workspace, script prompt, creator settings, character references, and 9-scene screenplay grid.</p></div>
    <div><span>✓</span><strong>Storyboarding SQL + Admin AI Settings v1</strong><p>Migration 021 adds storyboard persistence, scene/character/reference/job tables, AI providers, usage tracking, and admin-only API key settings.</p></div>
    <div><span>✓</span><strong>Script-to-9-Scene Generation API v1</strong><p>Admin-gated generation endpoint, provider adapter, JSON parser, scene persistence, job status, and usage event logging.</p></div>
    <div><span>✓</span><strong>Scene Actions v1</strong><p>Scene edit persistence, single-scene rewrite, image regeneration, upload replacement, character consistency payloads, and retry-ready job records.</p></div>
    <div><span>✓</span><strong>Storyboard Character Management + UX Modals v1</strong><p>Add/update characters, upload references, assign/remove scene characters, modal-style panels, job badges, retry controls, and bulk image regeneration.</p></div>
    <div><span>✓</span><strong>Storyboard Queue + Export v1</strong><p>Image queue batching, process-next worker action, cancel controls, job summary, screenplay export, shot-list CSV export, JSON export, and reference gallery review.</p></div>
    <div><span>✓</span><strong>Combined Storyboarding System</strong><p>Season, episode, scene sheet, draggable scene-card order, editable scene titles, AI storyboard generation, and main character catalog foundation.</p></div>
  </div>
</section>

<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Database Mode</span><h2>Runtime notes</h2></div><a href="<?= sf_url('docs/SQL_FILE_MAP.md') ?>">SQL Map</a></div><p class="sf-admin-copy">The installer runs the base schema plus migrations 001 through 021. Migration 021 adds storyboard persistence, AI provider settings, storyboard jobs, and AI usage tracking. Existing installs should apply only missing migrations in numeric order after a database backup. Storyboarding System v1 adds an optional additive migration at <code>database/storyboarding_system_v1.sql</code>.</p></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>