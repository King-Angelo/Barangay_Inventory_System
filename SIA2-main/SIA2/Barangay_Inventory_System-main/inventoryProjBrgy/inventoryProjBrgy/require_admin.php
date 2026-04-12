<?php
/**
 * require_admin.php
 * ─────────────────────────────────────────────────────────────────────────────
 * Shortcut for pages that are admin-only.
 * Includes require_auth.php (login check + helpers) then immediately
 * calls require_admin_role() to block non-admins.
 *
 * Usage (at the very top of an admin page, before any HTML):
 *   require_once __DIR__ . '/require_admin.php';
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/require_auth.php';
require_admin_role();
