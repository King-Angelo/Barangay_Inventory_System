<?php
/**
 * require_auth.php
 * Enforces login. Exposes RBAC helper functions.
 * Uses RELATIVE redirects so it works in any subfolder (e.g. /inventoryProjBrgy/).
 *
 * Session keys set at login: user, role, user_id, barangay_id (nullable — NULL for admin “super” scope).
 */

chdir(__DIR__);
require_once __DIR__ . '/session_init.php';
inv_session_start();

// Must be logged in
if (!isset($_SESSION['user'])) {
    header('Location: Login.php', true, 302);
    exit;
}

/** Returns the current user's role: 'admin' | 'staff' */
function current_role() {
    return isset($_SESSION['role']) ? (string)$_SESSION['role'] : 'staff';
}

/** Returns true only when the logged-in user is an admin. */
function is_admin() {
    return current_role() === 'admin';
}

/**
 * Blocks non-admins with a 302 redirect.
 * Uses a relative path so it works in any subfolder.
 */
function require_admin_role($redirect = 'brgy.php') {
    if (!is_admin()) {
        http_response_code(403);
        header('Location: ' . $redirect . '?denied=1', true, 302);
        exit;
    }
}
