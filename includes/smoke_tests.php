<?php
require_once __DIR__ . '/package_readiness.php';

function sf_smoke_scenarios(): array {
  return [
    ['group'=>'Install + launch gate','scenario'=>'Installer opens and can lock after setup','path'=>'install.php','method'=>'GET','persona'=>'Owner/Admin','type'=>'route','priority'=>'critical','expected'=>'Installer loads, server checks render, database setup can complete, and install.lock prevents reruns.'],
    ['group'=>'Install + launch gate','scenario'=>'Package readiness page loads','path'=>'admin/package-readiness.php','method'=>'GET','persona'=>'Admin','type'=>'route','priority'=>'critical','expected'=>'Package manifest, SHA-256 fingerprints, package score, and handoff gates render.'],
    ['group'=>'Install + launch gate','scenario'=>'Production smoke-test matrix loads','path'=>'admin/smoke-tests.php','method'=>'GET','persona'=>'Admin','type'=>'route','priority'=>'critical','expected'=>'Scenario matrix, grouped scores, and launch-blocking failures render.'],
    ['group'=>'Install + launch gate','scenario'=>'Deployment preflight reports launch gate','path'=>'deploy/preflight.php','method'=>'GET/CLI','persona'=>'Admin/CLI','type'=>'route','priority'=>'critical','expected'=>'Preflight prints QA, package, smoke-test, failure, warning, and launch gate summaries.'],
    ['group'=>'Install + launch gate','scenario'=>'Migration checker covers target schema','path'=>'admin/migration-checker.php','method'=>'GET','persona'=>'Admin','type'=>'route','priority'=>'critical','expected'=>'Base schema plus migrations 001 through 020 are visible and checked.'],
    ['group'=>'Install + launch gate','scenario'=>'Route registry has no missing routes','path'=>'admin/routes-checker.php','method'=>'GET','persona'=>'Admin','type'=>'route','priority'=>'critical','expected'=>'Public, member, admin, utility, and API routes render in the registry.'],

    ['group'=>'Auth + account','scenario'=>'Signup page loads','path'=>'signup.php','method'=>'GET/POST','persona'=>'Visitor','type'=>'route','priority'=>'critical','expected'=>'Visitor can create an account or receive validation messages.'],
    ['group'=>'Auth + account','scenario'=>'Signin page loads','path'=>'signin.php','method'=>'GET/POST','persona'=>'Visitor','type'=>'route','priority'=>'critical','expected'=>'Visitor can sign in and protected pages redirect unauthenticated users.'],
    ['group'=>'Auth + account','scenario'=>'Forgot password page loads','path'=>'forgot-password.php','method'=>'GET/POST','persona'=>'Visitor','type'=>'route','priority'=>'high','expected'=>'Password reset request form renders safely.'],
    ['group'=>'Auth + account','scenario'=>'Reset password page loads','path'=>'reset-password.php','method'=>'GET/POST','persona'=>'Visitor','type'=>'route','priority'=>'high','expected'=>'Reset token flow renders and rejects invalid tokens safely.'],
    ['group'=>'Auth + account','scenario'=>'Account dashboard loads','path'=>'account.php','method'=>'GET','persona'=>'Member','type'=>'route','priority'=>'high','expected'=>'Signed-in member can view profile/account management.'],
    ['group'=>'Auth + account','scenario'=>'Billing account page loads','path'=>'account-billing.php','method'=>'GET','persona'=>'Member','type'=>'route','priority'=>'high','expected'=>'Signed-in member can review plan and billing state.'],
    ['group'=>'Auth + account','scenario'=>'Logout endpoint loads','path'=>'logout.php','method'=>'GET/POST','persona'=>'Member','type'=>'route','priority'=>'high','expected'=>'Member session can be cleared safely.'],

    ['group'=>'Member runtime','scenario'=>'Member home loads','path'=>'member.php','method'=>'GET','persona'=>'Member','type'=>'route','priority'=>'critical','expected'=>'Member dashboard loads with account-aware state.'],
    ['group'=>'Member runtime','scenario'=>'Library loads','path'=>'library.php','method'=>'GET','persona'=>'Member','type'=>'route','priority'=>'critical','expected'=>'Member library renders saved/unlocked media.'],
    ['group'=>'Member runtime','scenario'=>'Watchlist loads','path'=>'watchlist.php','method'=>'GET','persona'=>'Member','type'=>'route','priority'=>'high','expected'=>'Watchlist page renders saved video content.'],
    ['group'=>'Member runtime','scenario'=>'Playlists page loads','path'=>'playlists.php','method'=>'GET','persona'=>'Member','type'=>'route','priority'=>'high','expected'=>'Member playlists render and can be managed.'],
    ['group'=>'Member runtime','scenario'=>'Feed loads','path'=>'feed.php','method'=>'GET','persona'=>'Member/Visitor','type'=>'route','priority'=>'high','expected'=>'Feed renders creator posts, personalized feed items, and fallback content.'],
    ['group'=>'Member runtime','scenario'=>'Notifications page loads','path'=>'notifications.php','method'=>'GET','persona'=>'Member','type'=>'route','priority'=>'high','expected'=>'Member notifications render with read/unread state.'],
    ['group'=>'Member runtime','scenario'=>'Messages page loads','path'=>'messages.php','method'=>'GET','persona'=>'Member','type'=>'route','priority'=>'high','expected'=>'Member message inbox renders threads/campaign notices.'],
    ['group'=>'Member runtime','scenario'=>'Comments center loads','path'=>'comments.php','method'=>'GET','persona'=>'Member','type'=>'route','priority'=>'medium','expected'=>'Member comments and reactions surface renders.'],
    ['group'=>'Member runtime','scenario'=>'Support center loads','path'=>'support.php','method'=>'GET/POST','persona'=>'Member','type'=>'route','priority'=>'medium','expected'=>'Member can open/review support tickets.'],

    ['group'=>'Media playback','scenario'=>'Music landing loads','path'=>'music.php','method'=>'GET','persona'=>'Visitor/Member','type'=>'route','priority'=>'critical','expected'=>'Music landing renders albums/songs and player entry points.'],
    ['group'=>'Media playback','scenario'=>'Player page loads','path'=>'player.php','method'=>'GET','persona'=>'Member','type'=>'route','priority'=>'critical','expected'=>'Spotify-style player shell loads with queue/state controls.'],
    ['group'=>'Media playback','scenario'=>'Album detail loads','path'=>'album.php','method'=>'GET','persona'=>'Visitor/Member','type'=>'route','priority'=>'high','expected'=>'Album detail page renders as a full page.'],
    ['group'=>'Media playback','scenario'=>'Song detail loads','path'=>'song.php','method'=>'GET','persona'=>'Visitor/Member','type'=>'route','priority'=>'high','expected'=>'Song detail page renders as a full page.'],
    ['group'=>'Media playback','scenario'=>'Series page loads','path'=>'series.php','method'=>'GET','persona'=>'Visitor/Member','type'=>'route','priority'=>'high','expected'=>'Series landing renders public show details.'],
    ['group'=>'Media playback','scenario'=>'Episodes page loads','path'=>'episodes.php','method'=>'GET','persona'=>'Visitor/Member','type'=>'route','priority'=>'high','expected'=>'Full-width episode listing renders.'],
    ['group'=>'Media playback','scenario'=>'Episode detail loads','path'=>'episode.php','method'=>'GET','persona'=>'Visitor/Member','type'=>'route','priority'=>'high','expected'=>'Episode detail page renders upsell/member state.'],
    ['group'=>'Media playback','scenario'=>'Watch page loads','path'=>'watch.php','method'=>'GET','persona'=>'Member','type'=>'route','priority'=>'critical','expected'=>'Video watch route loads and enforces access.'],
    ['group'=>'Media playback','scenario'=>'Signed stream endpoint exists','path'=>'stream.php','method'=>'GET','persona'=>'Member','type'=>'route','priority'=>'critical','expected'=>'Signed stream endpoint is present for protected media delivery.'],
    ['group'=>'Media playback','scenario'=>'Signed download endpoint exists','path'=>'download.php','method'=>'GET','persona'=>'Member','type'=>'route','priority'=>'high','expected'=>'Signed download endpoint is present for protected downloads.'],

    ['group'=>'Commerce + billing','scenario'=>'Subscribe page loads','path'=>'subscribe.php','method'=>'GET','persona'=>'Visitor','type'=>'route','priority'=>'critical','expected'=>'Membership plan purchase page renders.'],
    ['group'=>'Commerce + billing','scenario'=>'Billing checkout loads','path'=>'billing-checkout.php','method'=>'GET/POST','persona'=>'Visitor/Member','type'=>'route','priority'=>'critical','expected'=>'Subscription checkout handoff route renders safely.'],
    ['group'=>'Commerce + billing','scenario'=>'Billing success page loads','path'=>'billing-success.php','method'=>'GET','persona'=>'Member','type'=>'route','priority'=>'high','expected'=>'Successful subscription return path renders.'],
    ['group'=>'Commerce + billing','scenario'=>'Billing cancel page loads','path'=>'billing-cancel.php','method'=>'GET','persona'=>'Visitor/Member','type'=>'route','priority'=>'medium','expected'=>'Canceled subscription return path renders.'],
    ['group'=>'Commerce + billing','scenario'=>'Merch page loads','path'=>'merch.php','method'=>'GET','persona'=>'Visitor/Member','type'=>'route','priority'=>'critical','expected'=>'Merch catalog renders products.'],
    ['group'=>'Commerce + billing','scenario'=>'Product detail loads','path'=>'product.php','method'=>'GET','persona'=>'Visitor/Member','type'=>'route','priority'=>'high','expected'=>'Product detail route renders.'],
    ['group'=>'Commerce + billing','scenario'=>'Cart page loads','path'=>'cart.php','method'=>'GET/POST','persona'=>'Visitor/Member','type'=>'route','priority'=>'critical','expected'=>'Cart route renders and cart state can be reviewed.'],
    ['group'=>'Commerce + billing','scenario'=>'Checkout page loads','path'=>'checkout.php','method'=>'GET/POST','persona'=>'Visitor/Member','type'=>'route','priority'=>'critical','expected'=>'Checkout route renders safely.'],
    ['group'=>'Commerce + billing','scenario'=>'Order confirmation page loads','path'=>'order-confirmation.php','method'=>'GET','persona'=>'Customer','type'=>'route','priority'=>'high','expected'=>'Order confirmation route renders safely.'],

    ['group'=>'Admin operations','scenario'=>'Admin home loads','path'=>'admin/index.php','method'=>'GET','persona'=>'Admin','type'=>'route','priority'=>'critical','expected'=>'Admin dashboard loads with launch gate cards.'],
    ['group'=>'Admin operations','scenario'=>'Monitoring center loads','path'=>'admin/monitoring.php','method'=>'GET/POST','persona'=>'Admin','type'=>'route','priority'=>'critical','expected'=>'Monitoring health snapshots, errors, and service checks render.'],
    ['group'=>'Admin operations','scenario'=>'Incident center loads','path'=>'admin/incidents.php','method'=>'GET/POST','persona'=>'Admin','type'=>'route','priority'=>'critical','expected'=>'Incident records, events, alert rules, and notifications render.'],
    ['group'=>'Admin operations','scenario'=>'Backup manager loads','path'=>'admin/backups.php','method'=>'GET/POST','persona'=>'Admin','type'=>'route','priority'=>'critical','expected'=>'Backup profiles, runs, and restore readiness checks render.'],
    ['group'=>'Admin operations','scenario'=>'Release manager loads','path'=>'admin/releases.php','method'=>'GET/POST','persona'=>'Admin','type'=>'route','priority'=>'critical','expected'=>'Deployment releases, tasks, events, and rollback notes render.'],
    ['group'=>'Admin operations','scenario'=>'Ops scheduler loads','path'=>'admin/ops-scheduler.php','method'=>'GET/POST','persona'=>'Admin','type'=>'route','priority'=>'high','expected'=>'Scheduled jobs and runs render.'],
    ['group'=>'Admin operations','scenario'=>'Member messaging loads','path'=>'admin/member-messaging.php','method'=>'GET/POST','persona'=>'Admin','type'=>'route','priority'=>'high','expected'=>'Member message campaigns and recipients render.'],
    ['group'=>'Admin operations','scenario'=>'Security dashboard loads','path'=>'admin/security-dashboard.php','method'=>'GET','persona'=>'Admin','type'=>'route','priority'=>'high','expected'=>'Roles, sessions, audit events, and security checks render.'],
    ['group'=>'Admin operations','scenario'=>'Roles page loads','path'=>'admin/roles.php','method'=>'GET/POST','persona'=>'Admin','type'=>'route','priority'=>'high','expected'=>'Role matrix and user assignments render.'],

    ['group'=>'API contracts','scenario'=>'Media token API returns JSON contract','path'=>'api/media-token.php','method'=>'POST JSON','persona'=>'Member','type'=>'api','priority'=>'critical','expected'=>'Media token endpoint uses JSON response helper.'],
    ['group'=>'API contracts','scenario'=>'Audio tracking API returns JSON contract','path'=>'api/audio-track.php','method'=>'POST JSON','persona'=>'Member','type'=>'api','priority'=>'critical','expected'=>'Audio tracking endpoint uses JSON response helper.'],
    ['group'=>'API contracts','scenario'=>'Video tracking API returns JSON contract','path'=>'api/video-track.php','method'=>'POST JSON','persona'=>'Member','type'=>'api','priority'=>'critical','expected'=>'Video tracking endpoint uses JSON response helper.'],
    ['group'=>'API contracts','scenario'=>'Player state API returns JSON contract','path'=>'api/player-state.php','method'=>'GET/POST JSON','persona'=>'Member','type'=>'api','priority'=>'high','expected'=>'Player state endpoint uses JSON response helper.'],
    ['group'=>'API contracts','scenario'=>'Cart API returns JSON contract','path'=>'api/cart.php','method'=>'GET/POST JSON','persona'=>'Customer','type'=>'api','priority'=>'high','expected'=>'Cart runtime endpoint uses JSON response helper.'],
    ['group'=>'API contracts','scenario'=>'Comments API returns JSON contract','path'=>'api/comments.php','method'=>'GET/POST JSON','persona'=>'Member','type'=>'api','priority'=>'medium','expected'=>'Comments endpoint uses JSON response helper.'],
    ['group'=>'API contracts','scenario'=>'Notifications API returns JSON contract','path'=>'api/notifications.php','method'=>'GET/POST JSON','persona'=>'Member','type'=>'api','priority'=>'medium','expected'=>'Notifications endpoint uses JSON response helper.'],
    ['group'=>'API contracts','scenario'=>'Member messages API returns JSON contract','path'=>'api/member-messages.php','method'=>'GET/POST JSON','persona'=>'Member','type'=>'api','priority'=>'medium','expected'=>'Member messages endpoint uses JSON response helper.'],
    ['group'=>'API contracts','scenario'=>'Monitoring API returns JSON contract','path'=>'api/monitoring.php','method'=>'GET/POST JSON','persona'=>'Admin','type'=>'api','priority'=>'critical','expected'=>'Monitoring endpoint uses JSON response helper.'],
    ['group'=>'API contracts','scenario'=>'Incidents API returns JSON contract','path'=>'api/incidents.php','method'=>'GET/POST JSON','persona'=>'Admin','type'=>'api','priority'=>'critical','expected'=>'Incident endpoint uses JSON response helper.'],

    ['group'=>'Manual production checks','scenario'=>'Payment gateway keys and webhooks tested','path'=>'admin/payment-gateways.php','method'=>'Manual','persona'=>'Admin','type'=>'manual','priority'=>'critical','expected'=>'Live/sandbox provider keys are configured and webhook test events are recorded.'],
    ['group'=>'Manual production checks','scenario'=>'Email delivery verified','path'=>'admin/notifications.php','method'=>'Manual','persona'=>'Admin','type'=>'manual','priority'=>'high','expected'=>'Transactional email provider sends and logs delivery attempts.'],
    ['group'=>'Manual production checks','scenario'=>'Backup created before deployment','path'=>'admin/backups.php','method'=>'Manual','persona'=>'Admin','type'=>'manual','priority'=>'critical','expected'=>'Backup record exists and database/uploads/config are preserved.'],
    ['group'=>'Manual production checks','scenario'=>'Release record created for deployment','path'=>'admin/releases.php','method'=>'Manual','persona'=>'Admin','type'=>'manual','priority'=>'critical','expected'=>'Release record has branch, commit SHA, migration range, notes, and rollback plan.'],
    ['group'=>'Manual production checks','scenario'=>'Subscriber media protected','path'=>'watch.php','method'=>'Manual','persona'=>'Member/Visitor','type'=>'manual','priority'=>'critical','expected'=>'Visitor cannot access subscriber media; subscriber can stream allowed media.'],
  ];
}

function sf_smoke_evaluate_scenario(array $scenario): array {
  $path = (string)($scenario['path'] ?? '');
  $type = (string)($scenario['type'] ?? 'route');
  $exists = $path !== '' && sf_qa_file_exists($path);
  $status = 'fail';
  $detail = 'Missing route/file.';
  if ($type === 'manual') {
    $status = $exists ? 'manual' : 'fail';
    $detail = $exists ? 'Manual production verification required.' : 'Manual check target is missing.';
  } elseif ($type === 'api') {
    $json = $exists && sf_qa_contains($path, ['sf_json_response', 'header(\'Content-Type: application/json', 'application/json']);
    $status = $json ? 'pass' : ($exists ? 'warn' : 'fail');
    $detail = $json ? 'JSON response contract detected.' : ($exists ? 'Endpoint exists; JSON helper/header not detected.' : 'API endpoint missing.');
  } else {
    $status = $exists ? 'pass' : 'fail';
    $detail = $exists ? 'Route file present.' : 'Route file missing.';
  }
  $scenario['status'] = $status;
  $scenario['detail'] = $detail;
  $scenario['weight'] = sf_smoke_priority_weight((string)($scenario['priority'] ?? 'medium'));
  return $scenario;
}
function sf_smoke_priority_weight(string $priority): int { return ['critical'=>4,'high'=>3,'medium'=>2,'low'=>1][$priority] ?? 2; }
function sf_smoke_checks(): array { return array_map('sf_smoke_evaluate_scenario', sf_smoke_scenarios()); }
function sf_smoke_score(array $checks): int { return sf_qa_score($checks); }
function sf_smoke_grade(int $score): string { return sf_qa_grade($score); }
function sf_smoke_status_text(array $checks): string {
  $fails = count(array_filter($checks, static fn($c) => in_array(($c['status'] ?? ''), ['fail','missing'], true)));
  $score = sf_smoke_score($checks);
  if ($fails > 0) return 'blocked';
  if ($score >= 97) return 'ready';
  if ($score >= 90) return 'manual_review';
  return 'needs_work';
}
function sf_smoke_group_summary(array $checks): array {
  $summary = [];
  foreach ($checks as $check) {
    $group = (string)($check['group'] ?? 'General');
    if (!isset($summary[$group])) $summary[$group] = ['group'=>$group,'count'=>0,'fails'=>0,'warnings'=>0,'manual'=>0,'score'=>0,'checks'=>[]];
    $summary[$group]['count']++;
    $summary[$group]['checks'][] = $check;
    if (in_array(($check['status'] ?? ''), ['fail','missing'], true)) $summary[$group]['fails']++;
    if (in_array(($check['status'] ?? ''), ['warn','preview'], true)) $summary[$group]['warnings']++;
    if (($check['status'] ?? '') === 'manual') $summary[$group]['manual']++;
  }
  foreach ($summary as $group => $data) $summary[$group]['score'] = sf_smoke_score($data['checks']);
  return array_values($summary);
}
function sf_smoke_counts(array $checks): array {
  return [
    'total'=>count($checks),
    'pass'=>count(array_filter($checks, static fn($c) => ($c['status'] ?? '') === 'pass')),
    'fail'=>count(array_filter($checks, static fn($c) => in_array(($c['status'] ?? ''), ['fail','missing'], true))),
    'warn'=>count(array_filter($checks, static fn($c) => in_array(($c['status'] ?? ''), ['warn','preview'], true))),
    'manual'=>count(array_filter($checks, static fn($c) => ($c['status'] ?? '') === 'manual')),
  ];
}
function sf_smoke_render_table(array $checks): void {
  echo '<div class="sf-admin-table-wrap"><table class="sf-admin-table"><thead><tr><th>Group</th><th>Scenario</th><th>Persona</th><th>Method</th><th>Status</th><th>Expected</th><th>Detail</th></tr></thead><tbody>';
  foreach ($checks as $check) {
    echo '<tr><td>' . sf_smoke_h($check['group'] ?? '') . '</td><td><strong>' . sf_smoke_h($check['scenario'] ?? '') . '</strong><small>' . sf_smoke_h($check['path'] ?? '') . '</small></td><td>' . sf_smoke_h($check['persona'] ?? '') . '</td><td>' . sf_smoke_h($check['method'] ?? '') . '</td><td>' . sf_qa_badge((string)($check['status'] ?? 'info')) . '</td><td>' . sf_smoke_h($check['expected'] ?? '') . '</td><td>' . sf_smoke_h($check['detail'] ?? '') . '</td></tr>';
  }
  echo '</tbody></table></div>';
}
function sf_smoke_h($value): string { return sf_qa_h($value); }
?>
