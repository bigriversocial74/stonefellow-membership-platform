<?php
$pageTitle = 'Ops Scheduler';
$pageDescription = 'Stonefellow admin automation scheduler for notification dispatch, lifecycle scans, support reminders, revenue snapshots, and engagement refreshes.';
$pageClass = 'membership-page admin-catalog-page';
require __DIR__ . '/../includes/admin_catalog.php';
require_once __DIR__ . '/../includes/ops_scheduler_messaging.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  if (!sf_verify_csrf($_POST['csrf_token'] ?? null)) { sf_admin_flash('error','Security check failed.'); sf_admin_redirect(); }
  $action = (string)($_POST['action'] ?? '');
  if ($action === 'save_job') { $id=sf_sched_save_job($_POST,(int)($_POST['id']??0)); sf_admin_flash($id?'success':'error',$id?'Scheduled job saved.':'Job was not saved. Run migration 017.'); }
  if ($action === 'run_job') { $job=sf_sched_fetch_one('SELECT * FROM ops_scheduled_jobs WHERE id=? LIMIT 1',[(int)($_POST['id']??0)]); $r=$job?sf_sched_run_job($job):['ok'=>false,'summary'=>'Job not found.']; sf_admin_flash(!empty($r['ok'])?'success':'error',$r['summary'] ?? 'Job run complete.'); }
  if ($action === 'run_due') { $r=sf_sched_run_due(20); sf_admin_flash('success','Due jobs complete: '.(int)$r['processed'].' processed, '.(int)$r['success'].' success, '.(int)$r['failed'].' failed.'); }
  sf_admin_redirect(sf_url('admin/ops-scheduler.php'));
}

require __DIR__ . '/../includes/header.php';
$summary=sf_sched_summary();
$jobs=sf_sched_jobs();
$runs=sf_sched_runs(80);
sf_admin_shell_start('Ops Scheduler', 'Admin automation v1', 'Schedule and run repeatable launch operations: notification dispatch, churn-risk scans, support SLA reminders, revenue snapshots, and engagement refreshes.', 'ops-scheduler');
?>
<section class="sf-admin-card-grid">
  <div class="sf-admin-action-card"><span>Jobs</span><strong><?= (int)$summary['jobs'] ?></strong><small><?= (int)$summary['active'] ?> active.</small></div>
  <div class="sf-admin-action-card"><span>Due Now</span><strong><?= (int)$summary['due'] ?></strong><small>Ready to run.</small></div>
  <div class="sf-admin-action-card"><span>7-day Runs</span><strong><?= (int)$summary['runs'] ?></strong><small>Recent automation executions.</small></div>
  <div class="sf-admin-action-card"><span>Failures</span><strong><?= (int)$summary['failed'] ?></strong><small>Needs review.</small></div>
  <a class="sf-admin-action-card" href="<?= sf_url('api/ops-scheduler.php') ?>"><span>API</span><strong>Scheduler</strong><small>JSON due-run endpoint.</small></a>
</section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Run Controls</span><h2>Automation queue</h2></div><a href="<?= sf_url('admin/member-messaging.php') ?>">Member Messaging</a></div><form class="sf-admin-form" method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="run_due"><p class="sf-admin-copy">Run all active jobs that are due now. This is safe for cron or manual admin use because each job records a run log.</p><div class="sf-admin-form-actions"><button type="submit"<?= sf_sched_ready()?'':' disabled' ?>>Run Due Jobs</button></div></form></section>
<section class="sf-admin-two-col sf-admin-two-col-wide">
  <article class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Scheduled Jobs</span><h2><?= count($jobs) ?> jobs</h2></div></div><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Job</th><th>Type</th><th>Frequency</th><th>Status</th><th>Next</th><th>Run</th></tr></thead><tbody><?php foreach($jobs as $job): ?><tr><td><strong><?= sf_admin_h($job['title']) ?></strong><small><?= sf_admin_h($job['job_key']) ?></small></td><td><?= sf_admin_h(str_replace('_',' ',$job['job_type'])) ?></td><td><?= sf_admin_h($job['frequency']) ?></td><td><?= sf_admin_status_badge($job['status']) ?></td><td><?= sf_admin_h($job['next_run_at'] ?? 'manual') ?></td><td><form method="post" class="sf-admin-inline-form"><?= sf_csrf_field() ?><input type="hidden" name="action" value="run_job"><input type="hidden" name="id" value="<?= (int)$job['id'] ?>"><button type="submit">Run</button></form></td></tr><?php endforeach; ?><?php if(!$jobs): ?><tr><td colspan="6">No scheduled jobs yet. Run migration 017.</td></tr><?php endif; ?></tbody></table></div></article>
  <aside class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Create / Update</span><h2>Scheduled job</h2></div></div><form class="sf-admin-form" method="post"><?= sf_csrf_field() ?><input type="hidden" name="action" value="save_job"><label>Job Key<input name="job_key" placeholder="custom_daily_check" required<?= sf_admin_form_disabled_attr() ?>></label><label>Title<input name="title" placeholder="Daily launch report" required<?= sf_admin_form_disabled_attr() ?>></label><div class="sf-admin-form-grid"><label>Type<?= sf_admin_select('job_type',['dispatch_notifications'=>'Dispatch Notifications','lifecycle_churn_scan'=>'Lifecycle Scan','support_sla_scan'=>'Support SLA Scan','revenue_snapshot'=>'Revenue Snapshot','engagement_score_refresh'=>'Engagement Score Refresh','custom'=>'Custom Log'],'custom') ?></label><label>Frequency<?= sf_admin_select('frequency',['hourly'=>'Hourly','daily'=>'Daily','weekly'=>'Weekly','monthly'=>'Monthly','manual'=>'Manual'],'daily') ?></label></div><div class="sf-admin-form-grid"><label>Status<?= sf_admin_select('status',['active'=>'Active','paused'=>'Paused','archived'=>'Archived'],'active') ?></label><label>Schedule Time<input type="time" name="schedule_time"<?= sf_admin_form_disabled_attr() ?>></label></div><label>Description<textarea name="description" rows="3"<?= sf_admin_form_disabled_attr() ?>></textarea></label><div class="sf-admin-form-actions"><button type="submit"<?= sf_admin_form_disabled_attr() ?>>Save Job</button></div></form></aside>
</section>
<section class="sf-admin-panel"><div class="sf-admin-panel-head"><div><span class="sf-panel-eyebrow">Run History</span><h2>Recent automation logs</h2></div></div><div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Run</th><th>Status</th><th>Processed</th><th>Success</th><th>Failed</th><th>Finished</th></tr></thead><tbody><?php foreach($runs as $run): ?><tr><td><strong><?= sf_admin_h($run['title'] ?: $run['job_key']) ?></strong><small><?= sf_admin_h($run['result_summary'] ?? '') ?></small></td><td><?= sf_admin_status_badge($run['run_status']) ?></td><td><?= (int)$run['processed_count'] ?></td><td><?= (int)$run['success_count'] ?></td><td><?= (int)$run['failed_count'] ?></td><td><?= sf_admin_h($run['finished_at'] ?? $run['started_at']) ?></td></tr><?php endforeach; ?><?php if(!$runs): ?><tr><td colspan="6">No job runs logged yet.</td></tr><?php endif; ?></tbody></table></div></section>
<?php sf_admin_shell_end(); require __DIR__ . '/../includes/footer.php'; ?>
