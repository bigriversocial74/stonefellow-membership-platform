<?php
$pageTitle = 'Admin Roles';
$pageDescription = 'Manage Stonefellow admin roles, permission matrix, and admin user role assignments.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/admin_security.php';
sf_sec_require('admin.security.manage');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $action=(string)($_POST['action']??'');
  if($action==='save_role'){ $id=sf_sec_save_role($_POST,(int)($_POST['role_id']??0)); sf_admin_flash($id?'success':'error',$id?'Role saved.':'Role was not saved. Run migration 018.'); }
  if($action==='assign_role'){ sf_sec_assign_role((int)($_POST['user_id']??0),(int)($_POST['role_id']??0)); sf_admin_flash('success','Role assigned.'); }
  if($action==='remove_role'){ sf_sec_remove_role((int)($_POST['user_id']??0),(int)($_POST['role_id']??0)); sf_admin_flash('success','Role removed.'); }
  sf_admin_redirect(sf_url('admin/roles.php'));
}
require __DIR__ . '/../includes/header.php';
$summary=sf_sec_dashboard_summary();
$roles=sf_sec_roles();
$admins=sf_sec_admin_users();
$editId=(int)($_GET['role_id']??0);
$edit=$editId?sf_sec_fetch_one('SELECT * FROM admin_roles WHERE id=? LIMIT 1',[$editId]):null;
$editPerms=$editId?sf_sec_role_permissions($editId):[];
sf_admin_shell_start('Admin Roles', 'Permissions matrix v1', 'Manage admin roles, scoped module permissions, sensitive access boundaries, and admin user role assignments.', 'roles');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>Roles</span><strong><?= (int)$summary['roles'] ?></strong><small>Configured admin roles.</small></div>
  <div class="sf-admin-action-card"><span>Permissions</span><strong><?= (int)$summary['permissions'] ?></strong><small>Module-level capabilities.</small></div>
  <div class="sf-admin-action-card"><span>Admins</span><strong><?= (int)$summary['admin_users'] ?></strong><small>Users with admin role.</small></div>
  <a class="sf-admin-action-card" href="<?= sf_url('admin/audit-log.php') ?>"><span>Audit</span><strong><?= (int)$summary['audit_events'] ?></strong><small>Security events.</small></a>
</section>
<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Roles</span><h2><?= count($roles) ?> roles</h2></div><a href="<?= sf_url('admin/roles.php') ?>">New</a></div><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Role</th><th>Status</th><th>System</th><th>Permissions</th><th>Action</th></tr></thead><tbody><?php foreach($roles as $role): $perms=sf_sec_role_permissions((int)$role['id']); ?><tr><td><strong><?= sf_admin_h($role['role_label']) ?></strong><small><?= sf_admin_h($role['role_key']) ?></small></td><td><?= sf_admin_status_badge($role['status']) ?></td><td><?= !empty($role['is_system'])?'Yes':'No' ?></td><td><?= count($perms) ?></td><td><a href="<?= sf_url('admin/roles.php?role_id='.(int)$role['id']) ?>">Edit</a></td></tr><?php endforeach; ?><?php if(!$roles): ?><tr><td colspan="5">No roles found. Run migration 018.</td></tr><?php endif; ?></tbody></table></div></article>
  <aside class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow"><?= $edit?'Edit':'Create' ?></span><h2><?= $edit?sf_admin_h($edit['role_label']):'Admin role' ?></h2></div></div><form class="sf-admin-form" method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="save_role"><input type="hidden" name="role_id" value="<?= (int)($edit['id']??0) ?>"><label>Role Label<input name="role_label" required value="<?= sf_admin_h($edit['role_label']??'') ?>"<?= sf_admin_form_disabled_attr() ?>></label><label>Role Key<input name="role_key" value="<?= sf_admin_h($edit['role_key']??'') ?>" placeholder="content_admin"<?= sf_admin_form_disabled_attr() ?>></label><label>Description<textarea name="description" rows="3"<?= sf_admin_form_disabled_attr() ?>><?= sf_admin_h($edit['description']??'') ?></textarea></label><label>Status<?= sf_admin_select('status',['active'=>'Active','inactive'=>'Inactive'],$edit['status']??'active') ?></label><div class="sf-admin-roadmap"><?php foreach(sf_sec_permissions() as $key=>$label): ?><label class="sf-admin-check"><input type="checkbox" name="permissions[]" value="<?= sf_admin_h($key) ?>"<?= in_array($key,$editPerms,true)?' checked':'' ?><?= sf_admin_form_disabled_attr() ?>> <strong><?= sf_admin_h($label) ?></strong><p><?= sf_admin_h($key) ?></p></label><?php endforeach; ?></div><div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>>Save Role</button></div></form></aside>
</section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Admin Users</span><h2>Role assignment</h2></div><a href="<?= sf_url('admin/security-dashboard.php') ?>">Security Dashboard</a></div><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Admin</th><th>Status</th><th>Assigned Roles</th><th>Assign</th></tr></thead><tbody><?php foreach($admins as $admin): ?><tr><td><strong><?= sf_admin_h($admin['display_name'] ?: $admin['email']) ?></strong><small><?= sf_admin_h($admin['email']) ?></small></td><td><?= sf_admin_status_badge($admin['status']) ?></td><td><?= sf_admin_h($admin['role_labels'] ?: 'Fallback admin') ?></td><td><form method="post" class="sf-admin-inline-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="assign_role"><input type="hidden" name="user_id" value="<?= (int)$admin['id'] ?>"><select name="role_id"><?php foreach($roles as $role): ?><option value="<?= (int)$role['id'] ?>"><?= sf_admin_h($role['role_label']) ?></option><?php endforeach; ?></select><button type="submit">Assign</button></form></td></tr><?php endforeach; ?><?php if(!$admins): ?><tr><td colspan="4">No admin users found.</td></tr><?php endif; ?></tbody></table></div></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
