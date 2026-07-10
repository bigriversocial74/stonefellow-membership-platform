<?php
require_once __DIR__ . '/db.php';

function sf_sec_db(): ?PDO { return sf_db(); }
function sf_sec_table_exists(string $table): bool { $pdo=sf_sec_db();if(!$pdo)return false;try{$s=$pdo->prepare('SHOW TABLES LIKE ?');$s->execute([$table]);return (bool)$s->fetchColumn();}catch(Throwable $e){return false;} }
function sf_sec_fetch_all(string $sql,array $params=[]): array { $pdo=sf_sec_db();if(!$pdo)return [];try{$s=$pdo->prepare($sql);$s->execute($params);return $s->fetchAll()?:[];}catch(Throwable $e){error_log('Stonefellow security fetch failed: '.$e->getMessage());return [];} }
function sf_sec_fetch_one(string $sql,array $params=[]): ?array { $rows=sf_sec_fetch_all($sql,$params);return $rows[0]??null; }
function sf_sec_execute(string $sql,array $params=[]): bool { $pdo=sf_sec_db();if(!$pdo)return false;try{$s=$pdo->prepare($sql);return $s->execute($params);}catch(Throwable $e){error_log('Stonefellow security execute failed: '.$e->getMessage());return false;} }
function sf_sec_h($value): string { return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8'); }
function sf_sec_current_user(): ?array { return function_exists('sf_auth_user')?sf_auth_user():null; }
function sf_sec_current_user_id(): int { return function_exists('sf_current_user_id')?(int)sf_current_user_id():(int)($_SESSION['sf_user_id']??0); }
function sf_sec_request_route(): string { return trim(str_replace('\\','/',$_SERVER['SCRIPT_NAME']??''),'/'); }
function sf_sec_ip(): string { return sf_auth_hardening_hash('audit-ip|'.substr((string)($_SERVER['REMOTE_ADDR']??''),0,64)); }
function sf_sec_agent(): string { return sf_auth_hardening_hash('audit-ua|'.substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,255)); }
function sf_sec_owner_admin_id(): int { if(!sf_sec_table_exists('users'))return 0;$row=sf_sec_fetch_one("SELECT id FROM users WHERE role='admin' AND status='active' ORDER BY id ASC LIMIT 1");return (int)($row['id']??0); }

function sf_sec_audit(string $event,string $severity='info',string $entityType='',int $entityId=0,array $meta=[]): void {
  if(!sf_sec_table_exists('security_audit_events'))return;$u=sf_sec_current_user();$severity=in_array($severity,['info','notice','warning','critical'],true)?$severity:'warning';
  sf_sec_execute('INSERT INTO security_audit_events (actor_user_id,actor_email,event_type,severity,entity_type,entity_id,route_path,request_method,ip_address,user_agent,metadata_json) VALUES (?,?,?,?,?,?,?,?,?,?,?)',[sf_sec_current_user_id()?:null,$u['email']??null,substr($event,0,100),$severity,$entityType?:null,$entityId?:null,substr(sf_sec_request_route(),0,190),substr((string)($_SERVER['REQUEST_METHOD']??'GET'),0,12),sf_sec_ip(),sf_sec_agent(),json_encode($meta,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)]);
}
function sf_sec_track_admin_session(): void {
  $user=sf_sec_current_user();if(!$user||($user['role']??'')!=='admin'||!sf_sec_table_exists('admin_security_sessions'))return;$hash=hash('sha256',session_id()?:'missing-session');
  $existing=sf_sec_fetch_one('SELECT status FROM admin_security_sessions WHERE session_id_hash=? LIMIT 1',[$hash]);
  if($existing&&in_array(($existing['status']??''),['revoked','expired'],true)){
    sf_sec_audit('revoked_admin_session_rejected','critical','user',(int)$user['id']);
    unset($_SESSION['sf_user_id'],$_SESSION['user_id'],$_SESSION['member_id'],$_SESSION['sf_login_at']);
    if(function_exists('sf_auth_flash'))sf_auth_flash('warning','This administrator session was revoked. Sign in again.');
    if(function_exists('sf_redirect'))sf_redirect(function_exists('sf_url')?sf_url('signin.php'):'../signin.php');
    return;
  }
  sf_sec_execute('INSERT INTO admin_security_sessions (user_id,session_id_hash,ip_address,user_agent,last_route,status) VALUES (?,?,?,?,?,"active") ON DUPLICATE KEY UPDATE ip_address=VALUES(ip_address),user_agent=VALUES(user_agent),last_route=VALUES(last_route),last_seen_at=NOW()',[(int)$user['id'],$hash,sf_sec_ip(),sf_sec_agent(),sf_sec_request_route()]);
}
function sf_sec_permissions(): array { return ['admin.security.manage'=>'Manage security','admin.audit.view'=>'View audit logs','admin.content.manage'=>'Manage content','admin.members.manage'=>'Manage members','admin.billing.manage'=>'Manage billing','admin.ops.manage'=>'Manage ops','admin.analytics.view'=>'View analytics','admin.settings.manage'=>'Manage settings']; }
function sf_sec_route_permission_map(): array { return [
  'admin/roles.php'=>'admin.security.manage','admin/audit-log.php'=>'admin.audit.view','admin/security-dashboard.php'=>'admin.audit.view','admin/security-check.php'=>'admin.audit.view',
  'admin/tier-manager.php'=>'admin.billing.manage','admin/revenue-dashboard.php'=>'admin.billing.manage','admin/billing.php'=>'admin.billing.manage','admin/payment-gateways.php'=>'admin.billing.manage','admin/orders.php'=>'admin.billing.manage','admin/products.php'=>'admin.billing.manage','admin/entitlements.php'=>'admin.billing.manage','admin/media-access.php'=>'admin.billing.manage',
  'admin/member-lifecycle.php'=>'admin.members.manage','admin/support.php'=>'admin.members.manage','admin/member-messaging.php'=>'admin.members.manage','admin/members.php'=>'admin.members.manage','admin/notifications.php'=>'admin.members.manage','admin/email-templates.php'=>'admin.members.manage',
  'admin/ops-scheduler.php'=>'admin.ops.manage','admin/content-ops.php'=>'admin.ops.manage','admin/activity-feed.php'=>'admin.ops.manage','admin/launch-checklist.php'=>'admin.ops.manage','admin/backups.php'=>'admin.ops.manage','admin/releases.php'=>'admin.ops.manage','admin/operations-recovery.php'=>'admin.ops.manage','admin/monitoring.php'=>'admin.ops.manage','admin/incidents.php'=>'admin.ops.manage',
  'admin/posts.php'=>'admin.content.manage','admin/uploads.php'=>'admin.content.manage','admin/import.php'=>'admin.content.manage','admin/seed-manager.php'=>'admin.content.manage','admin/music.php'=>'admin.content.manage','admin/music-albums.php'=>'admin.content.manage','admin/music-songs.php'=>'admin.content.manage','admin/episodes.php'=>'admin.content.manage','admin/videos.php'=>'admin.content.manage','admin/seasons.php'=>'admin.content.manage','admin/publishing.php'=>'admin.content.manage','admin/media-delivery.php'=>'admin.content.manage','admin/storyboards.php'=>'admin.content.manage','admin/characters.php'=>'admin.content.manage','admin/series-assets.php'=>'admin.content.manage','admin/scene-backgrounds.php'=>'admin.content.manage','admin/theme-images.php'=>'admin.content.manage',
  'admin/analytics.php'=>'admin.analytics.view','admin/streaming-analytics.php'=>'admin.analytics.view','admin/audio-analytics.php'=>'admin.analytics.view','admin/video-analytics.php'=>'admin.analytics.view','admin/search-discovery.php'=>'admin.analytics.view','admin/qa.php'=>'admin.analytics.view','admin/routes-checker.php'=>'admin.analytics.view','admin/content-audit.php'=>'admin.analytics.view',
  'admin/migration-checker.php'=>'admin.settings.manage','admin/system-health.php'=>'admin.settings.manage','admin/settings.php'=>'admin.settings.manage','admin/ai-settings.php'=>'admin.settings.manage','admin/ai-staging-certification.php'=>'admin.settings.manage'
]; }
function sf_sec_route_permission(string $route): string {
  foreach(sf_sec_route_permission_map() as $path=>$permission)if(str_ends_with($route,$path))return $permission;
  $base=basename($route);if($base==='index.php')return '';
  if(str_contains($base,'ai-'))return str_contains($base,'settings')||str_contains($base,'autonomy')?'admin.settings.manage':'admin.content.manage';
  if(preg_match('/(billing|payment|revenue|order|product|tier|entitlement)/',$base))return 'admin.billing.manage';
  if(preg_match('/(member|support|message|notification|email)/',$base))return 'admin.members.manage';
  if(preg_match('/(analytics|report|audit|qa|search)/',$base))return 'admin.analytics.view';
  if(preg_match('/(backup|release|deploy|monitor|incident|scheduler|operations|health|migration)/',$base))return 'admin.ops.manage';
  if(preg_match('/(music|song|album|episode|video|season|post|publish|upload|import|story|character|scene|theme|content)/',$base))return 'admin.content.manage';
  return 'admin.settings.manage';
}
function sf_sec_user_permissions(int $userId=0): array {
  $userId=$userId?:sf_sec_current_user_id();$user=sf_sec_current_user();if(!$userId||!$user||($user['role']??'')!=='admin')return [];
  if(!sf_sec_table_exists('admin_user_roles'))return $userId===sf_sec_owner_admin_id()?array_keys(sf_sec_permissions()):[];
  $rows=sf_sec_fetch_all('SELECT DISTINCT rp.permission_key FROM admin_user_roles ur INNER JOIN admin_role_permissions rp ON rp.role_id=ur.role_id INNER JOIN admin_roles r ON r.id=ur.role_id WHERE ur.user_id=? AND r.status="active"',[$userId]);$perms=array_values(array_unique(array_map(fn($r)=>(string)$r['permission_key'],$rows)));
  if(!$perms&&$userId===sf_sec_owner_admin_id())return array_keys(sf_sec_permissions());return $perms;
}
function sf_sec_can(string $permission,int $userId=0): bool { return $permission===''||in_array($permission,sf_sec_user_permissions($userId),true); }
function sf_sec_require(string $permission): bool { if(sf_sec_can($permission))return true;sf_sec_audit('permission_denied','warning','permission',0,['permission'=>$permission]);if(function_exists('sf_auth_flash'))sf_auth_flash('error','You do not have permission to access that admin module.');if(function_exists('sf_redirect'))sf_redirect(function_exists('sf_url')?sf_url('admin/index.php'):'index.php');return false; }
function sf_sec_route_guard(): void { sf_sec_track_admin_session();$route=sf_sec_request_route();if(!str_contains('/'.$route,'/admin/'))return;sf_sec_require(sf_sec_route_permission($route)); }
function sf_sec_roles(): array { return sf_sec_table_exists('admin_roles')?sf_sec_fetch_all('SELECT * FROM admin_roles ORDER BY is_system DESC,role_label ASC'):[]; }
function sf_sec_role_permissions(int $roleId): array { if(!$roleId||!sf_sec_table_exists('admin_role_permissions'))return [];return array_map(fn($r)=>(string)$r['permission_key'],sf_sec_fetch_all('SELECT permission_key FROM admin_role_permissions WHERE role_id=?',[$roleId])); }
function sf_sec_admin_users(): array { if(!sf_sec_table_exists('users'))return [];return sf_sec_fetch_all("SELECT u.id,u.email,u.display_name,u.role,u.status,GROUP_CONCAT(r.role_label ORDER BY r.role_label SEPARATOR ', ') role_labels FROM users u LEFT JOIN admin_user_roles ur ON ur.user_id=u.id LEFT JOIN admin_roles r ON r.id=ur.role_id WHERE u.role='admin' GROUP BY u.id ORDER BY u.created_at DESC"); }
function sf_sec_save_role(array $data,int $id=0): int {
  if(!sf_sec_table_exists('admin_roles'))return 0;$label=trim((string)($data['role_label']??''));if($label==='')return 0;$key=trim((string)($data['role_key']??''));if($key==='')$key=strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$label),'-'));$status=in_array(($data['status']??'active'),['active','inactive'],true)?$data['status']:'active';
  if($id>0){$existing=sf_sec_fetch_one('SELECT role_key,is_system FROM admin_roles WHERE id=? LIMIT 1',[$id]);if(!empty($existing['is_system'])){$key=(string)$existing['role_key'];$status='active';}sf_sec_execute('UPDATE admin_roles SET role_key=?,role_label=?,description=?,status=?,updated_at=NOW() WHERE id=?',[$key,$label,$data['description']??null,$status,$id]);}else{sf_sec_execute('INSERT INTO admin_roles (role_key,role_label,description,status) VALUES (?,?,?,?)',[$key,$label,$data['description']??null,$status]);$id=(int)(sf_sec_db()?->lastInsertId()?:0);}
  if($id&&sf_sec_table_exists('admin_role_permissions')){sf_sec_execute('DELETE FROM admin_role_permissions WHERE role_id=?',[$id]);foreach((array)($data['permissions']??[]) as $p)if(isset(sf_sec_permissions()[$p]))sf_sec_execute('INSERT IGNORE INTO admin_role_permissions (role_id,permission_key) VALUES (?,?)',[$id,$p]);}sf_sec_audit('admin_role_saved','notice','admin_role',$id,['role_key'=>$key]);return $id;
}
function sf_sec_assign_role(int $userId,int $roleId): bool { if(!$userId||!$roleId||!sf_sec_table_exists('admin_user_roles'))return false;$user=sf_sec_fetch_one('SELECT role,status FROM users WHERE id=? LIMIT 1',[$userId]);if(!$user||($user['role']??'')!=='admin'||($user['status']??'')!=='active')return false;$ok=sf_sec_execute('INSERT IGNORE INTO admin_user_roles (user_id,role_id,assigned_by_user_id) VALUES (?,?,?)',[$userId,$roleId,sf_sec_current_user_id()?:null]);sf_sec_audit('admin_role_assigned','notice','user',$userId,['role_id'=>$roleId]);return $ok; }
function sf_sec_remove_role(int $userId,int $roleId): bool {
  if(!$userId||!$roleId||!sf_sec_table_exists('admin_user_roles'))return false;$role=sf_sec_fetch_one('SELECT role_key FROM admin_roles WHERE id=? LIMIT 1',[$roleId]);if(($role['role_key']??'')==='super_admin'){$count=(int)(sf_sec_fetch_one('SELECT COUNT(DISTINCT user_id) total FROM admin_user_roles ur INNER JOIN admin_roles r ON r.id=ur.role_id WHERE r.role_key="super_admin"')['total']??0);if($count<=1){sf_sec_audit('last_super_admin_removal_blocked','critical','user',$userId,['role_id'=>$roleId]);return false;}}
  $ok=sf_sec_execute('DELETE FROM admin_user_roles WHERE user_id=? AND role_id=?',[$userId,$roleId]);sf_sec_audit('admin_role_removed','warning','user',$userId,['role_id'=>$roleId]);return $ok;
}
function sf_sec_audit_events(int $limit=250): array { return sf_sec_table_exists('security_audit_events')?sf_sec_fetch_all('SELECT * FROM security_audit_events ORDER BY created_at DESC,id DESC LIMIT '.max(1,min(1000,$limit))):[]; }
function sf_sec_dashboard_summary(): array { $out=['roles'=>0,'permissions'=>count(sf_sec_permissions()),'admin_users'=>0,'audit_events'=>0,'denied'=>0,'sessions'=>0];if(sf_sec_table_exists('admin_roles'))$out['roles']=(int)(sf_sec_fetch_one('SELECT COUNT(*) total FROM admin_roles')['total']??0);if(sf_sec_table_exists('users'))$out['admin_users']=(int)(sf_sec_fetch_one("SELECT COUNT(*) total FROM users WHERE role='admin'")['total']??0);if(sf_sec_table_exists('security_audit_events')){$out['audit_events']=(int)(sf_sec_fetch_one('SELECT COUNT(*) total FROM security_audit_events')['total']??0);$out['denied']=(int)(sf_sec_fetch_one("SELECT COUNT(*) total FROM security_audit_events WHERE event_type='permission_denied'")['total']??0);}if(sf_sec_table_exists('admin_security_sessions'))$out['sessions']=(int)(sf_sec_fetch_one("SELECT COUNT(*) total FROM admin_security_sessions WHERE status='active'")['total']??0);return $out; }
?>
