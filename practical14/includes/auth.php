<?php
// Practical 14 – Admin Dashboard RBAC + Audit Log
// includes/auth.php – Session management, RBAC helpers, audit logging

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

session_name(SESSION_NAME);
if (session_status() === PHP_SESSION_NONE) session_start();

// ── Session timeout ────────────────────────────────────────────
if (isset($_SESSION['last_active']) && (time() - $_SESSION['last_active']) > SESSION_TIMEOUT) {
    session_unset();
    session_destroy();
    header('Location: login.php?reason=timeout');
    exit;
}
$_SESSION['last_active'] = time();

// ── Auth helpers ───────────────────────────────────────────────

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function current_user(): array
{
    return [
        'id'   => $_SESSION['user_id']   ?? 0,
        'name' => $_SESSION['user_name'] ?? '',
        'role' => $_SESSION['user_role'] ?? '',
    ];
}

/**
 * Require login; optionally restrict to specific roles.
 * @param string[] $roles  e.g. [ROLE_ADMIN] or [ROLE_ADMIN, ROLE_MODERATOR]
 */
function require_role(array $roles = []): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
    if ($roles && !in_array($_SESSION['user_role'], $roles, true)) {
        header('Location: unauthorized.php');
        exit;
    }
}

function is_admin(): bool      { return ($_SESSION['user_role'] ?? '') === ROLE_ADMIN; }
function is_moderator(): bool  { return in_array($_SESSION['user_role'] ?? '', [ROLE_ADMIN, ROLE_MODERATOR]); }

// ── Dynamic menu ───────────────────────────────────────────────

function get_menu(): array
{
    $role = $_SESSION['user_role'] ?? ROLE_STUDENT;
    return MENU[$role] ?? MENU[ROLE_STUDENT];
}

function render_nav(string $current = ''): string
{
    $html = '';
    foreach (get_menu() as $item) {
        $active = (basename($current) === basename($item['href'])) ? ' class="active"' : '';
        $html  .= "<li><a href='{$item['href']}'{$active}>{$item['icon']} {$item['label']}</a></li>";
    }
    return $html;
}

// ── Audit logging ──────────────────────────────────────────────

function audit_log(string $action, string $module = '', string $details = ''): void
{
    try {
        $db   = get_db();
        $u    = current_user();
        $ip   = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $db->prepare(
            "INSERT INTO audit_logs (user_id, user_name, action, module, details, ip_address)
             VALUES (?,?,?,?,?,?)"
        );
        $stmt->bind_param('isssss', $u['id'], $u['name'], $action, $module, $details, $ip);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        error_log('[P14 Audit] ' . $e->getMessage());
    }
}
