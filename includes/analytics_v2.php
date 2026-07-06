<?php
require_once __DIR__ . '/admin_analytics.php';

function sf_analytics_v2_snapshot(int $days = 30): array {
  $overview = sf_analytics_overview($days);
  $audioEvents = (int)($overview['audio']['events'] ?? 0);
  $videoEvents = (int)($overview['video']['events'] ?? 0);
  $audioSeconds = (int)($overview['audio']['seconds'] ?? 0);
  $videoSeconds = (int)($overview['video']['seconds'] ?? 0);
  $members = (int)($overview['members']['total'] ?? 0);
  $subs = (int)($overview['members']['active_subscriptions'] ?? 0);
  $revenue = (int)($overview['commerce']['revenue_cents'] ?? 0);
  $engaged = 0;
  if (sf_admin_table_exists('audio_play_events')) $engaged += (int)(sf_admin_fetch_one('SELECT COUNT(DISTINCT user_id) AS total FROM audio_play_events WHERE created_at >= ? AND user_id IS NOT NULL', [sf_analytics_since($days)])['total'] ?? 0);
  if (sf_admin_table_exists('video_watch_events')) $engaged += (int)(sf_admin_fetch_one('SELECT COUNT(DISTINCT user_id) AS total FROM video_watch_events WHERE created_at >= ? AND user_id IS NOT NULL', [sf_analytics_since($days)])['total'] ?? 0);
  $library = ['items'=>0,'watchlist'=>0,'liked'=>0,'completed'=>0];
  if (sf_admin_table_exists('member_library_items')) {
    $row = sf_admin_fetch_one("SELECT COUNT(*) AS items, SUM(library_status='watchlist') AS watchlist, SUM(library_status='liked') AS liked, SUM(library_status='completed') AS completed FROM member_library_items WHERE created_at >= ? OR last_interaction_at >= ?", [sf_analytics_since($days), sf_analytics_since($days)]);
    foreach ($library as $k=>$v) $library[$k] = (int)($row[$k] ?? 0);
  }
  return [
    'days'=>$days,
    'overview'=>$overview,
    'engagement'=>[
      'events'=>$audioEvents + $videoEvents,
      'seconds'=>$audioSeconds + $videoSeconds,
      'engaged_members'=>$engaged,
      'avg_seconds_per_event'=>($audioEvents+$videoEvents)>0 ? (int)(($audioSeconds+$videoSeconds)/($audioEvents+$videoEvents)) : 0,
      'subscriber_rate'=>$members>0 ? round(($subs/$members)*100, 1) : 0,
      'revenue_per_member_cents'=>$members>0 ? (int)round($revenue/$members) : 0,
    ],
    'library'=>$library,
    'top_songs'=>sf_analytics_audio_top_songs($days, 10),
    'top_videos'=>sf_analytics_video_top_videos($days, 10),
    'daily'=>sf_analytics_daily_activity(min($days, 30)),
  ];
}

function sf_analytics_v2_stage_score(array $snapshot): array {
  $eng = $snapshot['engagement'];
  return [
    ['label'=>'Audience engagement','value'=>(int)$eng['engaged_members'],'note'=>'Unique audio/video members in range'],
    ['label'=>'Total stream time','value'=>sf_analytics_time((int)$eng['seconds']),'note'=>'Audio + video seconds'],
    ['label'=>'Avg seconds/event','value'=>(string)(int)$eng['avg_seconds_per_event'],'note'=>'Quality-of-session signal'],
    ['label'=>'Subscriber rate','value'=>$eng['subscriber_rate'].'%','note'=>'Active subscriptions / total members'],
    ['label'=>'Revenue/member','value'=>sf_analytics_money((int)$eng['revenue_per_member_cents']),'note'=>'Merch revenue per member in range'],
    ['label'=>'Library saves','value'=>(string)(int)($snapshot['library']['items'] ?? 0),'note'=>'Library engagement in range'],
  ];
}
?>
