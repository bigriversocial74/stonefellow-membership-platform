<?php
$pageTitle = 'Security Audit Log';
$pageDescription = 'Review Stonefellow security audit events, permission denials, role changes, sensitive admin actions, and session activity.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/admin_security.php';
sf_sec_require('admin.audit.view');
$eventType=trim((string)($_GET['event_type']??''));
$severity=trim((string)($_GET['severity']??''));
$where=[];$params=[];
if($eventType!==''){$where[]='event_type=?';$params[]=$eventType;}
if($severity!==''){$where[]='severity=?';$params[]=$severity;}
$sql='SELECT * FROM security_audit_events'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY created_at DESC, id DESC LIMIT 500';
$events=sf_sec_table_exists('security_audit_events')?sf_sec_fetch_all($sql,$params):[];
$summary=sf_sec_dashboard_summary();
require __DIR__ . '/../includes/header.php';
sf_admin_shell_start('Audit Log', 'Security event history', 'Review permission denials, role changes, login/security events, sensitive admin actions, route access, IPs, user agents, and metadata.', 'audit-log');
?>
<section class="sf-admin-card-grid"><div class="sf-admin-action-card"><span>Audit Events</span><strong><?= (int)$summary['audit_events'] ?></strong><small>Total security events.</small></div><div class="sf-admin-action-card"><span>Denied</span><strong><?= (int)$summary['denied'] ?></strong><small>Permission denials.</small></div><div class="sf-admin-action-card"><span>Sessions</span><strong><?= (int)$summary['sessions'] ?></strong><small>Active admin sessions.</small></div><a class="sf-admin-action-card" href="<?= sf_url('api/security-audit.php') ?>"><span>API</span><strong>Audit JSON</strong><small>Security audit endpoint.</small></a></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Filters</span><h2>Audit search</h2></div><a href="<?= sf_url('admin/roles.php') ?>">Roles</a></div><form class="sf-admin-form" method="get"><div class="sf-admin-form-grid"><label>Event Type<input name="event_type" value="<?= sf_admin_h($eventType) ?>" placeholder="permission_denied"></label><label>Severity<select name="severity"><option value="">All</option><?php foreach(['info','notice','warning','critical'] as $s): ?><option value="<?= $s ?>"<?= $severity===$s?' selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></label></div><div class="sf-admin-form-actions"><button type="submit">Filter</button><a href="<?= sf_url('admin/audit-log.php') ?>">Clear</a></div></form></section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Events</span><h2><?= count($events) ?> recent records</h2></div></div><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Event</th><th>Actor</th><th>Severity</th><th>Route</th><th>Entity</th><th>IP</th><th>Created</th></tr></thead><tbody><?php foreach($events as $event): ?><tr><td><strong><?= sf_admin_h($event['event_type']) ?></strong><small><?= sf_admin_h(substr((string)($event['metadata_json']??''),0,120)) ?></small></td><td><?= sf_admin_h($event['actor_email'] ?: ('User #'.($event['actor_user_id']??''))) ?></td><td><?= sf_admin_status_badge($event['severity']) ?></td><td><?= sf_admin_h($event['request_method'].' '.$event['route_path']) ?></td><td><?= sf_admin_h(trim(($event['entity_type']??'').' #'.($event['entity_id']??''))) ?></td><td><?= sf_admin_h($event['ip_address']??'') ?></td><td><?= sf_admin_h($event['created_at']) ?></td></tr><?php endforeach; ?><?php if(!$events): ?><tr><td colspan="7">No audit events found yet. Run migration 018 and perform a security-sensitive action.</td></tr><?php endif; ?></tbody></table></div></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
