# Security Notes — Session Handling & RBAC
**For:** SIA2 Final PDF — §Security & Access Control  
**Authors:** Member 3 (implementation) · Group Leader (review & wording)  
**Scope:** Legacy PHP layer (`inventoryProjBrgy/`)

---

## 1. Session Management

### 1.1 Session Initialization (`session_init.php`)

All PHP pages that require a session call `inv_session_start()` from `session_init.php` instead of the bare `session_start()`. This wrapper configures the session cookie before the session opens, which is required by PHP's session API.

The cookie is set with the following flags:

| Flag | Value | Purpose |
|------|-------|---------|
| `httponly` | `true` | Prevents JavaScript from reading the session cookie, blocking XSS-based session theft |
| `samesite` | `Lax` | Blocks the cookie from being sent in cross-site POST requests, mitigating CSRF |
| `secure` | auto-detected | Set to `true` when the request arrives over HTTPS or via a reverse proxy (`X-Forwarded-Proto: https`); prevents cookie transmission over plain HTTP in production |
| `lifetime` | `0` | Session expires when the browser is closed (no persistent cookie) |

### 1.2 What is Stored in `$_SESSION`

After a successful login, three values are written to the session:

```
$_SESSION['user']    — username string (display / audit use)
$_SESSION['role']    — 'staff' | 'admin'  (RBAC gate key)
$_SESSION['user_id'] — integer FK to users.id (used for audit columns submitted_by, approved_by)
```

No passwords, tokens, or sensitive DB data are stored in the session.

### 1.3 Session Destruction on Logout

`logout.php` calls `session_destroy()` after starting the session, which removes all session data server-side. The browser is then redirected to `Login.php`. A new session ID is issued on the next login (PHP regenerates it automatically on `session_start()`).

---

## 2. Authentication

### 2.1 Password Verification

`Login.php` supports two credential formats to allow gradual migration from the legacy system:

1. **Bcrypt (new)** — `password_verify($input, $row['password_hash'])`. The admin seed account uses this. New accounts should always use `password_hash($password, PASSWORD_BCRYPT)`.
2. **Legacy plaintext (`PaSS` column)** — accepted only when `password_hash` is empty, to avoid locking out existing staff accounts during migration. This fallback should be removed once all passwords are migrated.

Usernames are escaped with `mysqli_real_escape_string()` before use in SQL. A failed login returns a generic error message — it does not reveal whether the username or the password was wrong.

### 2.2 No Registration Page

There is no self-registration flow. Accounts are created by an admin via direct DB insert or the Settings page. This eliminates the risk of unauthorized account creation.

---

## 3. Role-Based Access Control (RBAC)

### 3.1 Role Model

The system uses a two-role model stored in `users.role` (ENUM `'staff'` | `'admin'`):

| Role  | Capabilities |
|-------|-------------|
| **staff** | Login; view all pages; create/edit residents; create permits (draft); submit permits for review |
| **admin** | Everything staff can do, plus: approve/reject permits; archive residents; access Settings; change barangay |

### 3.2 Enforcement Files

| File | Purpose |
|------|---------|
| `require_auth.php` | Included on every protected page. Checks `$_SESSION['user']`; redirects to Login if absent. Defines helper functions `is_admin()`, `current_role()`, `require_admin_role()`. |
| `require_admin.php` | Thin wrapper — includes `require_auth.php` then immediately calls `require_admin_role()`. Used at the top of admin-only pages. |

### 3.3 Admin-Only Pages

The following pages include `require_admin.php` and will redirect any non-admin to `brgy.php?denied=1` with HTTP 302:

- `Settings.php` — user account management
- `resident_action.php` — archive a resident (`action=archive`)
- `permit_action.php` — approve or reject a permit (`action=approve`, `action=reject`)

### 3.4 Role-Conditional UI

`nav.php` checks `current_role()` at render time:
- The **SETTINGS** nav link is only emitted for admins.
- The **Approve / Reject** buttons on `permit_view.php` are only rendered for admins.
- The **Archive** button on `residents.php` is only rendered for admins.

This is a UX convenience — the server-side gate in `require_admin.php` / `require_admin_role()` is the real enforcement layer. Hiding UI elements alone would not be sufficient.

### 3.5 Permit Status Flow & RBAC

```
[draft] ──(staff: submit)──▶ [submitted] ──(admin: approve)──▶ [approved]
                                                  └──(admin: reject)──▶ [rejected]
```

- Only a **staff or admin** user can move a permit from `draft` → `submitted`.
- Only an **admin** can move a permit from `submitted` → `approved` or `rejected`.
- These transitions are enforced in `permit_action.php` via `require_admin_role()` for the decide actions, and at the SQL level — the `WHERE status='submitted'` clause in `decide_permit()` prevents status skipping.

---

## 4. SQL Injection Mitigation

All user-supplied values used in SQL queries are escaped with `mysqli_real_escape_string()` before interpolation. The codebase uses MySQLi procedural style inherited from the legacy system. Future work should migrate these queries to prepared statements (`mysqli_prepare` / `PDO`) for stronger protection.

---

## 5. Known Limitations (v1)

| Limitation | Risk | Recommendation |
|------------|------|----------------|
| Legacy `PaSS` column stores plaintext | High — if DB is exposed, all legacy passwords are readable | Migrate all accounts to `password_hash`; drop `PaSS` column after |
| Raw string interpolation in SQL | Medium — `mysqli_real_escape_string` mitigates but prepared statements are safer | Refactor to PDO or MySQLi prepared statements in v2 |
| No rate limiting on `Login.php` | Low (local deployment) | Add login attempt throttling if publicly hosted |
| No CSRF token on forms | Medium | Add `csrf_token` hidden field + validation for all POST forms in v2 |

---

*This document is intended for inclusion in the SIA2 Final PDF under the Security & Access Control section. Pair with Group Leader for final wording and diagrams.*
