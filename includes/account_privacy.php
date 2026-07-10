<?php

declare(strict_types=1);
require_once __DIR__ . '/auth.php';

function sf_privacy_table_exists(PDO $pdo, string $table): bool
{
    if (!preg_match('/^[a-z0-9_]+$/i', $table)) return false;
    try {
        $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function sf_privacy_fetch(PDO $pdo, string $sql, array $params = []): array
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('Stonefellow privacy export query failed: ' . $e->getMessage());
        return [];
    }
}

function sf_privacy_export_data(int $userId): array
{
    $pdo = sf_db();
    if (!$pdo instanceof PDO || $userId <= 0) return [];
    $sections = [];
    $queries = [
        'profile' => ['users', 'SELECT id,email,display_name,role,status,email_verified_at,last_login_at,created_at,updated_at FROM users WHERE id=?', [$userId]],
        'subscriptions' => ['user_subscriptions', 'SELECT id,plan_id,status,current_period_start,current_period_end,cancel_at_period_end,created_at,updated_at FROM user_subscriptions WHERE user_id=? ORDER BY id', [$userId]],
        'access_grants' => ['content_access_grants', 'SELECT content_type,content_id,grant_type,access_level,starts_at,expires_at,created_at FROM content_access_grants WHERE user_id=? ORDER BY created_at', [$userId]],
        'library' => ['member_library_items', 'SELECT content_type,content_id,library_status,created_at,updated_at FROM member_library_items WHERE user_id=? ORDER BY created_at', [$userId]],
        'playlists' => ['playlists', 'SELECT id,title,description,visibility,created_at,updated_at FROM playlists WHERE user_id=? ORDER BY id', [$userId]],
        'song_progress' => ['user_song_progress', 'SELECT song_id,last_position_seconds,play_count,completed_count,last_played_at FROM user_song_progress WHERE user_id=? ORDER BY last_played_at', [$userId]],
        'video_progress' => ['user_video_progress', 'SELECT video_id,last_position_seconds,watch_count,completed_count,last_watched_at FROM user_video_progress WHERE user_id=? ORDER BY last_watched_at', [$userId]],
        'episode_progress' => ['user_episode_progress', 'SELECT episode_id,last_position_seconds,percent_complete,completed_at,updated_at FROM user_episode_progress WHERE user_id=? ORDER BY updated_at', [$userId]],
        'orders' => ['orders', 'SELECT id,order_number,email,status,payment_status,subtotal_cents,shipping_cents,tax_cents,total_cents,currency,created_at,updated_at FROM orders WHERE user_id=? ORDER BY id', [$userId]],
        'support_tickets' => ['support_tickets', 'SELECT id,ticket_number,subject,status,priority,created_at,updated_at FROM support_tickets WHERE user_id=? ORDER BY id', [$userId]],
        'notifications' => ['member_notifications', 'SELECT notification_type,title,body,status,created_at,read_at FROM member_notifications WHERE user_id=? ORDER BY created_at', [$userId]],
        'comments' => ['fan_comments', 'SELECT content_type,content_id,body,status,created_at,updated_at FROM fan_comments WHERE user_id=? ORDER BY created_at', [$userId]],
    ];
    foreach ($queries as $key => [$table, $sql, $params]) {
        if (sf_privacy_table_exists($pdo, $table)) $sections[$key] = sf_privacy_fetch($pdo, $sql, $params);
    }
    return [
        'export_version' => 1,
        'generated_at' => gmdate('c'),
        'account_user_id' => $userId,
        'data' => $sections,
        'retention_note' => 'Financial, tax, fraud-prevention, security, and legal records may be retained after account deactivation where required.',
    ];
}

function sf_privacy_download_export(int $userId): void
{
    $payload = sf_privacy_export_data($userId);
    if (!$payload) {
        http_response_code(503);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Account export is unavailable.';
        exit;
    }
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="stonefellow-account-export-' . $userId . '-' . gmdate('Ymd-His') . '.json"');
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    echo $json;
    exit;
}

function sf_privacy_deactivation_blockers(int $userId): array
{
    $pdo = sf_db();
    if (!$pdo instanceof PDO) return ['Database connection unavailable.'];
    $blockers = [];
    if (sf_privacy_table_exists($pdo, 'user_subscriptions')) {
        $rows = sf_privacy_fetch($pdo, "SELECT status,current_period_end FROM user_subscriptions WHERE user_id=? AND status IN ('active','trialing','past_due') AND (current_period_end IS NULL OR current_period_end>=NOW()) LIMIT 1", [$userId]);
        if ($rows) $blockers[] = 'Cancel the active membership and allow provider confirmation before deactivating the account.';
    }
    if (sf_privacy_table_exists($pdo, 'orders')) {
        $rows = sf_privacy_fetch($pdo, "SELECT id FROM orders WHERE user_id=? AND status IN ('pending','processing','paid','fulfilled') AND payment_status NOT IN ('refunded','canceled','failed') LIMIT 1", [$userId]);
        if ($rows) $blockers[] = 'Resolve active merchandise orders before deactivating the account.';
    }
    return $blockers;
}

function sf_privacy_deactivate_account(int $userId, string $confirmation): array
{
    if ($userId <= 0 || !hash_equals('DEACTIVATE', strtoupper(trim($confirmation)))) return ['ok' => false, 'message' => 'Type DEACTIVATE exactly to confirm.'];
    $pdo = sf_db();
    if (!$pdo instanceof PDO) return ['ok' => false, 'message' => 'Database connection unavailable.'];
    $blockers = sf_privacy_deactivation_blockers($userId);
    if ($blockers) return ['ok' => false, 'message' => implode(' ', $blockers)];
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE users SET status='disabled', updated_at=NOW() WHERE id=? AND status='active'");
        $stmt->execute([$userId]);
        if ($stmt->rowCount() !== 1) throw new RuntimeException('Account is not eligible for deactivation.');
        if (sf_privacy_table_exists($pdo, 'user_auth_tokens')) $pdo->prepare('UPDATE user_auth_tokens SET used_at=COALESCE(used_at,NOW()) WHERE user_id=?')->execute([$userId]);
        if (sf_privacy_table_exists($pdo, 'admin_security_sessions')) $pdo->prepare("UPDATE admin_security_sessions SET status='revoked',last_seen_at=NOW() WHERE user_id=? AND status='active'")->execute([$userId]);
        if (sf_privacy_table_exists($pdo, 'security_audit_events')) {
            $pdo->prepare("INSERT INTO security_audit_events (actor_user_id,event_type,severity,entity_type,entity_id,route_path,request_method,metadata_json) VALUES (?, 'account_deactivated', 'warning', 'user', ?, ?, 'POST', ?)")->execute([$userId,$userId,'account-privacy.php',json_encode(['self_service'=>true],JSON_UNESCAPED_SLASHES)]);
        }
        $pdo->commit();
        return ['ok' => true, 'message' => 'Account deactivated.'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Stonefellow account deactivation failed: ' . $e->getMessage());
        return ['ok' => false, 'message' => 'Account deactivation could not be completed.'];
    }
}
