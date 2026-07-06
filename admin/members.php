<?php
$pageTitle = 'Manage Members';
$pageDescription = 'Manage Stonefellow members, roles, status, subscriptions, and access levels.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/notifications.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_admin_db_ready() || !sf_admin_table_exists('users')) {
    sf_admin_flash('warning', 'Users table is not available. Configure the database and run the base SQL first.');
    sf_admin_redirect();
  }
  $action = $_POST['action'] ?? '';
  $id = sf_admin_int($_POST['id'] ?? null, 0) ?? 0;

  if ($action === 'save_member' && $id > 0) {
    $before = sf_admin_fetch_one('SELECT id, email, display_name, role, status FROM users WHERE id = ?', [$id]);
    $displayName = trim((string)($_POST['display_name'] ?? ''));
    $role = $_POST['role'] ?? 'user';
    $status = $_POST['status'] ?? 'active';
    if (!in_array($role, ['user','admin'], true)) { $role = 'user'; }
    if (!in_array($status, ['active','inactive','banned'], true)) { $status = 'active'; }
    sf_admin_execute('UPDATE users SET display_name=?, role=?, status=?, updated_at=NOW() WHERE id=?', [$displayName, $role, $status, $id]);
    sf_admin_audit('update_member', 'user', $id, $before, ['display_name' => $displayName, 'role' => $role, 'status' => $status]);
    sf_admin_flash('success', 'Member updated.');
    sf_admin_redirect(sf_url('admin/members.php?edit=' . $id));
  }

  if ($action === 'assign_plan' && $id > 0) {
    if (!sf_admin_table_exists('user_subscriptions') || !sf_admin_table_exists('subscription_plans')) {
      sf_admin_flash('warning', 'Subscription tables are not available.');
      sf_admin_redirect(sf_url('admin/members.php?edit=' . $id));
    }
    $planId = sf_admin_int($_POST['plan_id'] ?? null, 0) ?? 0;
    $plan = sf_admin_fetch_one("SELECT id, name, billing_interval FROM subscription_plans WHERE id = ? AND status = 'active'", [$planId]);
    if (!$plan) {
      sf_admin_flash('error', 'Select an active plan.');
      sf_admin_redirect(sf_url('admin/members.php?edit=' . $id));
    }
    $periodEnd = (new DateTimeImmutable(($plan['billing_interval'] ?? 'month') === 'year' ? '+1 year' : '+1 month'))->format('Y-m-d H:i:s');
    sf_admin_execute("UPDATE user_subscriptions SET status='canceled', updated_at=NOW() WHERE user_id=? AND status IN ('active','trialing')", [$id]);
    sf_admin_execute("INSERT INTO user_subscriptions (user_id, plan_id, status, current_period_start, current_period_end, external_subscription_id) VALUES (?, ?, 'active', NOW(), ?, 'manual-admin')", [$id, (int)$plan['id'], $periodEnd]);
    sf_admin_audit('assign_subscription', 'user', $id, null, ['plan_id' => (int)$plan['id'], 'period_end' => $periodEnd]);
    $recipient = sf_notify_user_recipient($id);
    if ($recipient) {
      sf_notify_send_template('subscription_started', $recipient, [
        'plan_name' => (string)$plan['name'],
        'period_end' => $periodEnd,
        'member_url' => sf_notify_absolute_url('member.php'),
      ], ['notification_type' => 'billing', 'metadata' => ['event' => 'admin_assigned_subscription', 'plan_id' => (int)$plan['id']], 'dispatch' => true]);
    }
    sf_admin_flash('success', 'Plan assigned: ' . $plan['name']);
    sf_admin_redirect(sf_url('admin/members.php?edit=' . $id));
  }

  if ($action === 'cancel_plan' && $id > 0) {
    sf_admin_execute("UPDATE user_subscriptions SET status='canceled', updated_at=NOW() WHERE user_id=? AND status IN ('active','trialing')", [$id]);
    sf_admin_audit('cancel_subscription', 'user', $id, null, null);
    $recipient = sf_notify_user_recipient($id);
    if ($recipient) {
      sf_notify_send_template('subscription_canceled', $recipient, [
        'subscription_status' => 'canceled by admin',
        'period_end' => date('Y-m-d H:i:s'),
      ], ['notification_type' => 'billing', 'metadata' => ['event' => 'admin_canceled_subscription'], 'dispatch' => true]);
    }
    sf_admin_flash('success', 'Active subscription cancelled.');
    sf_admin_redirect(sf_url('admin/members.php?edit=' . $id));
  }
}

require __DIR__ . '/../includes/header.php';

$editId = sf_admin_int($_GET['edit'] ?? null, 0) ?? 0;
$members = sf_admin_table_exists('users') ? sf_admin_fetch_all("\n  SELECT u.id, u.email, u.display_name, u.role, u.status, u.created_at, u.last_login_at,\n         sp.name AS plan_name, us.status AS subscription_status, us.current_period_end\n  FROM users u\n  LEFT JOIN user_subscriptions us ON us.id = (\n    SELECT us2.id FROM user_subscriptions us2\n    WHERE us2.user_id = u.id\n    ORDER BY FIELD(us2.status,'active','trialing','past_due','canceled','expired'), us2.current_period_end DESC, us2.id DESC\n    LIMIT 1\n  )\n  LEFT JOIN subscription_plans sp ON sp.id = us.plan_id\n  ORDER BY u.created_at DESC, u.id DESC\n  LIMIT 300\n") : [];
$editing = $editId > 0 ? sf_admin_fetch_one('SELECT id, email, display_name, role, status, created_at, last_login_at, email_verified_at FROM users WHERE id = ?', [$editId]) : ($members[0] ?? null);
$editingId = (int)($editing['id'] ?? 0);
$plans = sf_admin_table_exists('subscription_plans') ? sf_admin_fetch_all("SELECT id, name, price_cents, billing_interval FROM subscription_plans WHERE status='active' ORDER BY is_featured DESC, price_cents ASC") : [];
$activeSubscription = $editingId && sf_admin_table_exists('user_subscriptions') ? sf_admin_fetch_one("\n  SELECT us.*, sp.name AS plan_name, sp.slug AS plan_slug\n  FROM user_subscriptions us\n  LEFT JOIN subscription_plans sp ON sp.id = us.plan_id\n  WHERE us.user_id = ?\n  ORDER BY FIELD(us.status,'active','trialing','past_due','canceled','expired'), us.current_period_end DESC, us.id DESC\n  LIMIT 1\n", [$editingId]) : null;

sf_admin_shell_start('Membership Admin', 'Members + subscriptions', 'Manage users, admin roles, account status, and manual subscription access for the Stonefellow membership platform.', 'members');
?>
<section class="sf-admin-two-col sf-admin-catalog-layout">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head">
      <div><span class="sf-panel-eyebrow">Members</span><h2>Account list</h2></div>
      <a href="<?= sf_url('signup.php') ?>">Create User</a>
    </div>
    <div class="sf-admin-list">
      <?php foreach ($members as $member): ?>
        <a class="sf-admin-list-row <?= (int)$member['id'] === $editingId ? 'is-selected' : '' ?>" href="<?= sf_url('admin/members.php?edit=' . (int)$member['id']) ?>">
          <strong><?= sf_admin_h($member['display_name'] ?: $member['email']) ?></strong>
          <span><?= sf_admin_h($member['role']) ?> · <?= sf_admin_h($member['plan_name'] ?: 'Free') ?></span>
        </a>
      <?php endforeach; ?>
      <?php if (!$members): ?><p class="sf-admin-copy">No database users yet. The first signup becomes admin.</p><?php endif; ?>
    </div>
  </article>

  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Edit</span><h2><?= $editing ? sf_admin_h($editing['email']) : 'No member selected' ?></h2></div></div>
    <?php if ($editing): ?>
      <form class="sf-admin-form" method="post">
      <?= sf_csrf_field() ?>
        <input type="hidden" name="action" value="save_member">
        <input type="hidden" name="id" value="<?= $editingId ?>">
        <label>Display name<input type="text" name="display_name" value="<?= sf_admin_h($editing['display_name'] ?? '') ?>"></label>
        <label>Role<select name="role"><option value="user" <?= ($editing['role'] ?? '') === 'user' ? 'selected' : '' ?>>User</option><option value="admin" <?= ($editing['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option></select></label>
        <label>Status<select name="status"><option value="active" <?= ($editing['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= ($editing['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option><option value="banned" <?= ($editing['status'] ?? '') === 'banned' ? 'selected' : '' ?>>Banned</option></select></label>
        <button type="submit"<?= sf_admin_form_disabled_attr() ?>>Save Member</button>
      </form>
    <?php endif; ?>
  </article>
</section>

<section class="sf-admin-two-col">
  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Plan</span><h2>Subscription access</h2></div></div>
    <?php if ($editing): ?>
      <div class="sf-admin-detail-list">
        <div><span>Current Plan</span><strong><?= sf_admin_h($activeSubscription['plan_name'] ?? 'Free Account') ?></strong></div>
        <div><span>Status</span><strong><?= sf_admin_h($activeSubscription['status'] ?? 'none') ?></strong></div>
        <div><span>Period End</span><strong><?= sf_admin_h($activeSubscription['current_period_end'] ?? '—') ?></strong></div>
      </div>
      <form class="sf-admin-form" method="post">
      <?= sf_csrf_field() ?>
        <input type="hidden" name="action" value="assign_plan">
        <input type="hidden" name="id" value="<?= $editingId ?>">
        <label>Assign active plan
          <select name="plan_id" required>
            <option value="">Choose plan</option>
            <?php foreach ($plans as $plan): ?>
              <option value="<?= (int)$plan['id'] ?>"><?= sf_admin_h($plan['name']) ?> — $<?= number_format(((int)$plan['price_cents']) / 100, 2) ?>/<?= sf_admin_h($plan['billing_interval']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <button type="submit"<?= sf_admin_form_disabled_attr() ?>>Assign Plan</button>
      </form>
      <form class="sf-admin-inline-form" method="post">
      <?= sf_csrf_field() ?>
        <input type="hidden" name="action" value="cancel_plan">
        <input type="hidden" name="id" value="<?= $editingId ?>">
        <button class="sf-admin-danger" type="submit"<?= sf_admin_form_disabled_attr() ?>>Cancel Active Plan</button>
      </form>
    <?php endif; ?>
  </article>

  <article class="sf-admin-panel">
    <div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Activity</span><h2>Account timestamps</h2></div><a href="<?= sf_url('account.php') ?>">Member View</a></div>
    <?php if ($editing): ?>
      <div class="sf-admin-detail-list">
        <div><span>Created</span><strong><?= sf_admin_h($editing['created_at'] ?? '—') ?></strong></div>
        <div><span>Last Login</span><strong><?= sf_admin_h($editing['last_login_at'] ?? '—') ?></strong></div>
        <div><span>Email Verified</span><strong><?= sf_admin_h($editing['email_verified_at'] ?? 'Not verified') ?></strong></div>
      </div>
    <?php endif; ?>
  </article>
</section>
<?php
sf_admin_shell_end();
require __DIR__ . '/../includes/footer.php';
?>
