<?php
$pageTitle = 'Security Dashboard';
$pageDescription = 'Stonefellow security hardening dashboard for admin permissions, sessions, audit events, route protection, and sensitive actions.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/admin_security.php';
sf_sec_require('admin.audit.view');
$summary=sf_sec_dashboard_summary();
$sessions=sf_sec_table_exists('admin_security_sessions')?sf_sec_fetch_all('SELECT s.*, u.email, u.display_name FROM admin_security_sessions s LEFT JOIN users u ON u.id=s.user_id ORDER BY s.last_seen_at DESC LIMIT 100'):[];
$events=sf_sec_audit_events(25);
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Security Dashboard', 'Hardening and monitoring v1', 'Monitor roles, permissions, admin sessions, sensitive actions, route protection, and audit activity before production multi-admin use.', 'security-dashboard');
?>
<section class="sf-admin-card-grid">
  <a class="sf-admin-action-card" href="<?= sf_url('admin/roles.php') ?>"><span>Roles</span><strong><?= (int)$summary['roles'] ?></strong><small>Admin role definitions.</small></a>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/audit-log.php') ?>"><span>Audit Events</span><strong><?= (int)$summary['audit_events'] ?></strong><small><?= (int)$summary['denied'] ?> denials logged.</small></a>
  <div class="sf-admin-action-card"><span>Sessions</span><strong><?= (int)$summary['sessions'] ?></strong><small>Active admin sessions.</small></div>
  <div class="sf-admin-action-card"><span>Admins</span><strong><?= (int)$summary['admin_users'] ?></strong><small>Admin user accounts.</small></div>
</section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Security Hardening</span><h2>Production controls</h2></div><a href="<?= sf_url('admin/security-check.php') ?>">QA Security Check</a></div><div class="sf-admin-roadmap"><div><span>01</span><strong>Role matrix</strong><p>Roles map to module-level permissions for security, billing, content, members, ops, analytics, and settings.</p></div><div><span>02</span><strong>Audit log</strong><p>Sensitive security actions, role assignments, permission denials, and session activity are logged.</p></div><div><span>03</span><strong>Session tracking</strong><p>Admin sessions record hashed session id, route, IP, user agent, first seen, and last seen.</p></div><div><span>04</span><strong>Route review</strong><p>Security dashboard exposes module boundaries for production admin hardening.</p></div></div></section>
<section class="sf-admin-two-col sf-admin-two-col-wide"><article class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Admin Sessions</span><h2><?= count($sessions) ?> recent sessions</h2></div></div><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Admin</th><th>Status</th><th>Last Route</th><th>IP</th><th>Last Seen</th></tr></thead><tbody><?php foreach($sessions as $s): ?><tr><td><strong><?= sf_admin_h($s['display_name'] ?: $s['email']) ?></strong><small><?= sf_admin_h($s['email']) ?></small></td><td><?= sf_admin_status_badge($s['status']) ?></td><td><?= sf_admin_h($s['last_route']??'') ?></td><td><?= sf_admin_h($s['ip_address']??'') ?></td><td><?= sf_admin_h($s['last_seen_at']??'') ?></td></tr><?php endforeach; ?><?php if(!$sessions): ?><tr><td colspan="5">No tracked sessions yet.</td></tr><?php endif; ?></tbody></table></div></article><aside class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Recent Audit</span><h2>Security events</h2></div><a href="<?= sf_url('admin/audit-log.php') ?>">View All</a></div><div class="sf-admin-list"><?php foreach($events as $e): ?><article class="sf-admin-list-row"><strong><?= sf_admin_h($e['event_type']) ?></strong><span><?= sf_admin_h($e['severity']) ?> · <?= sf_admin_h($e['created_at']) ?></span><p><?= sf_admin_h($e['actor_email'] ?: 'System') ?> · <?= sf_admin_h($e['route_path']??'') ?></p></article><?php endforeach; ?><?php if(!$events): ?><article class="sf-admin-list-row"><strong>No audit events yet.</strong><p>Security-sensitive actions will appear here.</p></article><?php endif; ?></div></aside></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
