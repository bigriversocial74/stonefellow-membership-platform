<?php

declare(strict_types=1);
require_once __DIR__ . '/auth.php';

function sf_auth_secure_logout(): void
{
    $userId = sf_current_user_id() ?: 0;
    $sessionHash = hash('sha256', session_id() ?: 'missing-session');
    $pdo = sf_db();
    if ($pdo instanceof PDO && $userId > 0) {
        try {
            if (sf_auth_hardening_table_exists($pdo, 'admin_security_sessions')) {
                $stmt = $pdo->prepare("UPDATE admin_security_sessions SET status='revoked', last_seen_at=NOW() WHERE user_id=? AND session_id_hash=?");
                $stmt->execute([$userId, $sessionHash]);
            }
        } catch (Throwable $e) {
            error_log('Stonefellow secure logout session revocation failed: ' . $e->getMessage());
        }
    }
    sf_auth_logout(false);
    unset(
        $_SESSION['sf_auth_last_activity'],
        $_SESSION['sf_auth_absolute_expires_at'],
        $_SESSION['sf_auth_fingerprint'],
        $_SESSION['sf_session_key']
    );
    session_regenerate_id(true);
    sf_auth_flash('success', 'You have been signed out securely.');
    sf_redirect(sf_url('signin.php'));
}
