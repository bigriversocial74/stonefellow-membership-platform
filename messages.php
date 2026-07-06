<?php
require __DIR__ . '/includes/ops_scheduler_messaging.php';
$user = sf_require_login();
$userId = (int)$user['id'];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) { sf_auth_flash('error','Security check failed.'); sf_redirect(sf_url('messages.php')); }
  $id = (int)($_POST['message_id'] ?? 0);
  $status = (string)($_POST['status'] ?? 'read');
  sf_msg_update_member_message($userId,$id,$status);
  sf_auth_flash('success','Message updated.');
  sf_redirect(sf_url('messages.php'));
}
$status = trim((string)($_GET['status'] ?? ''));
$messages = sf_msg_member_messages($userId,$status,150);
$unread = count(sf_msg_member_messages($userId,'unread',200));
$pageTitle = 'Messages';
$pageDescription = 'Stonefellow member message inbox for official updates, launch notices, retention follow-ups, and support messages.';
$pageClass = 'member-dashboard-page membership-page messages-page';
require __DIR__ . '/includes/header.php';
?>
<section class="sf-membership-shell">
  <section class="sf-member-hero">
    <div><span class="sf-panel-eyebrow">Member Inbox</span><h1>Your Stonefellow messages.</h1><p>Official member updates, launch notices, access reminders, and admin messages appear here.</p><div class="sf-episode-action-row"><a class="sf-primary-action" href="<?= sf_url('member.php') ?>">Member Dashboard</a><a class="sf-secondary-action" href="<?= sf_url('notifications.php') ?>">Notifications</a><a class="sf-secondary-action" href="<?= sf_url('support.php') ?>">Support</a></div></div>
    <article class="sf-member-status-card"><span>Unread</span><strong><?= (int)$unread ?></strong><small><?= count($messages) ?> messages in this view.</small><a href="<?= sf_url('api/member-messages.php') ?>">Messages API</a></article>
  </section>
  <section class="sf-member-grid"><?php foreach([''=>'All','unread'=>'Unread','read'=>'Read','archived'=>'Archived','dismissed'=>'Dismissed'] as $key=>$label): ?><a class="sf-member-panel" href="<?= sf_url('messages.php'.($key!==''?'?status='.urlencode($key):'')) ?>"><span class="sf-panel-eyebrow"><?= htmlspecialchars($label) ?></span><h2><?= $key===''?count(sf_msg_member_messages($userId,'',200)):count(sf_msg_member_messages($userId,$key,200)) ?></h2><p>Filter your message inbox.</p></a><?php endforeach; ?></section>
  <section class="sf-member-section"><div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Inbox</span><h2><?= count($messages) ?> messages</h2></div></div><div class="sf-admin-list"><?php foreach($messages as $message): ?><article class="sf-admin-list-row"><strong><?= htmlspecialchars($message['subject']) ?></strong><span><?= htmlspecialchars(ucfirst((string)$message['message_type'])) ?> · <?= htmlspecialchars($message['created_at']) ?> · <?= htmlspecialchars($message['status']) ?></span><p><?= nl2br(htmlspecialchars($message['body'])) ?></p><div class="sf-episode-action-row"><?php if(!empty($message['action_url'])): ?><a class="sf-secondary-action" href="<?= htmlspecialchars($message['action_url']) ?>">Open Link</a><?php endif; ?><form method="post"><?= sf_csrf_field() ?><input type="hidden" name="message_id" value="<?= (int)$message['id'] ?>"><button class="sf-secondary-action" name="status" value="read" type="submit">Mark Read</button><button class="sf-secondary-action" name="status" value="archived" type="submit">Archive</button><button class="sf-secondary-action" name="status" value="dismissed" type="submit">Dismiss</button></form></div></article><?php endforeach; ?><?php if(!$messages): ?><article class="sf-admin-list-row"><strong>No messages yet.</strong><p>Official member messages will appear here.</p></article><?php endif; ?></div></section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
