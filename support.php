<?php
require __DIR__ . '/includes/member_lifecycle_support.php';
$user = sf_require_login();
$userId = (int)$user['id'];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) { sf_auth_flash('error','Security check failed.'); sf_redirect(sf_url('support.php')); }
  $action = (string)($_POST['action'] ?? 'create_ticket');
  if ($action === 'reply_ticket') {
    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $ticket = sf_support_ticket($ticketId,$userId);
    if ($ticket) { sf_support_add_message($ticketId,$userId,(string)($_POST['message'] ?? ''),'member',false); sf_auth_flash('success','Reply added.'); sf_redirect(sf_url('support.php?ticket_id=' . $ticketId)); }
  } else {
    $id = sf_support_create_ticket($userId,(string)($_POST['subject'] ?? ''),(string)($_POST['body'] ?? ''),(string)($_POST['category'] ?? 'other'),(string)($_POST['priority'] ?? 'medium'),'member',$_POST);
    sf_auth_flash($id ? 'success' : 'error', $id ? 'Support ticket submitted.' : 'Ticket could not be submitted.');
    sf_redirect(sf_url('support.php' . ($id ? '?ticket_id=' . $id : '')));
  }
}
$tickets = sf_support_tickets($userId,'',100);
$ticketId = (int)($_GET['ticket_id'] ?? ($tickets[0]['id'] ?? 0));
$ticket = $ticketId ? sf_support_ticket($ticketId,$userId) : null;
$messages = $ticketId ? sf_support_messages($ticketId) : [];
$pageTitle = 'Support';
$pageDescription = 'Stonefellow member support center for account, billing, content access, merch, and technical help.';
$pageClass = 'member-dashboard-page membership-page support-page';
require __DIR__ . '/includes/header.php';
?>
<section class="sf-membership-shell">
  <section class="sf-member-hero">
    <div><span class="sf-panel-eyebrow">Support Center</span><h1>Need help with Stonefellow?</h1><p>Submit account, billing, content access, merch, or technical support requests. Your tickets stay linked to your member account.</p><div class="sf-episode-action-row"><a class="sf-primary-action" href="#new-ticket">New Ticket</a><a class="sf-secondary-action" href="<?= sf_url('member.php') ?>">Member Dashboard</a><a class="sf-secondary-action" href="<?= sf_url('account-billing.php') ?>">Billing</a></div></div>
    <article class="sf-member-status-card"><span>My Tickets</span><strong><?= count($tickets) ?></strong><small><?= count(array_filter($tickets,fn($t)=>!in_array($t['status'],['resolved','closed'],true))) ?> open support items.</small><a href="<?= sf_url('api/support-tickets.php') ?>">Support API</a></article>
  </section>
  <section class="sf-admin-two-col sf-admin-two-col-wide">
    <article class="sf-member-section"><div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Ticket History</span><h2>Your support requests</h2></div></div><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Ticket</th><th>Category</th><th>Priority</th><th>Status</th><th>Updated</th></tr></thead><tbody><?php foreach($tickets as $t): ?><tr><td><strong><a href="<?= sf_url('support.php?ticket_id=' . (int)$t['id']) ?>"><?= sf_ops_h($t['ticket_number']) ?></a></strong><small><?= sf_ops_h($t['subject']) ?></small></td><td><?= sf_ops_h($t['category']) ?></td><td><?= sf_ops_h($t['priority']) ?></td><td><?= sf_ops_h(ucfirst(str_replace('_',' ',$t['status']))) ?></td><td><?= sf_ops_h($t['last_message_at'] ?: $t['created_at']) ?></td></tr><?php endforeach; ?><?php if(!$tickets): ?><tr><td colspan="5">No tickets yet.</td></tr><?php endif; ?></tbody></table></div></article>
    <aside class="sf-member-section"><div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">Selected Thread</span><h2><?= $ticket ? sf_ops_h($ticket['ticket_number']) : 'No ticket selected' ?></h2></div></div><?php if($ticket): ?><div class="sf-admin-detail-list"><div><span>Subject</span><strong><?= sf_ops_h($ticket['subject']) ?></strong></div><div><span>Status</span><strong><?= sf_ops_h(ucfirst(str_replace('_',' ',$ticket['status']))) ?></strong></div><div><span>Category</span><strong><?= sf_ops_h($ticket['category']) ?></strong></div></div><div class="sf-admin-list"><?php foreach($messages as $m): if(!empty($m['is_internal']))continue; ?><article class="sf-admin-list-row"><strong><?= sf_ops_h(ucfirst($m['sender_type'])) ?></strong><span><?= sf_ops_h($m['created_at']) ?></span><p><?= nl2br(sf_ops_h($m['message'])) ?></p></article><?php endforeach; ?></div><?php if(!in_array($ticket['status'],['resolved','closed'],true)): ?><form class="sf-admin-form" method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="reply_ticket"><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>"><label>Reply<textarea name="message" rows="4" required></textarea></label><div class="sf-admin-form-actions"><button type="submit">Send Reply</button></div></form><?php endif; ?><?php else: ?><p>Select a ticket to view replies.</p><?php endif; ?></aside>
  </section>
  <section id="new-ticket" class="sf-member-section"><div class="sf-member-section-head"><div><span class="sf-panel-eyebrow">New Ticket</span><h2>Create a support request</h2></div></div><form class="sf-admin-form" method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="create_ticket"><div class="sf-admin-form-grid"><label>Category<select name="category"><option value="account">Account</option><option value="billing">Billing</option><option value="technical">Technical</option><option value="content">Content Access</option><option value="merch">Merch</option><option value="feedback">Feedback</option><option value="other">Other</option></select></label><label>Priority<select name="priority"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select></label></div><label>Subject<input name="subject" required></label><label>What can we help with?<textarea name="body" rows="6" required></textarea></label><div class="sf-admin-form-actions"><button type="submit">Submit Ticket</button></div></form></section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
