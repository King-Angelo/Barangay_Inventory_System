# Seed data (`007_seeds.sql`)

Aligned with **`RESIDENT_ROADMAP.md`**: v1 uses only **`staff`** and **`admin`** on `users` (no resident login); **`UNIQUE (barangay_id, email)`** on `residents`; permit workflow **staff → submitted**, **admin → approved/rejected**.

## What gets seeded

| Kind | Details |
|------|---------|
| **`users`** | **`admin`** (super-admin, `barangay_id` NULL), **`staff_dev`** (`staff`, `barangay_id = 1`). Both use the same bcrypt placeholder until you rotate. |
| **`permit_types`** | **Barangay Clearance** (`is_active = 1`). |
| **`residents`** (optional row) | One **demo** row in barangay **1** (`resident.demo@example.com`) if not already present — supports permit/API tests without manual inserts. |

## Password / bcrypt workflow (admin & seeded staff)

1. **Default dev password** for seeded hashes: **`ChangeMe2026!`** (rotate before any real deployment).
2. **Generate a new bcrypt hash** (PHP 8+):

   ```bash
   php -r "echo password_hash('YourNewPassword', PASSWORD_BCRYPT) . PHP_EOL;"
   ```

3. **Apply in MySQL** (example for `admin`):

   ```sql
   UPDATE `users`
   SET `password_hash` = '$2y$12$...(paste hash)...', `PaSS` = ''
   WHERE `UserName` = 'admin';
   ```

4. **JWT/API login** uses `password_hash` via `AuthService` — legacy **`PaSS`** is ignored when `password_hash` is set.

See also **`PRODUCTION_SETUP.md`** §4 (set admin password).

## Re-running `007_seeds.sql`

Uses **`INSERT … ON DUPLICATE KEY UPDATE`** on `users` and `permit_types` (by unique keys). The demo **`residents`** row is inserted only if **`(barangay_id, email)`** does not already exist.

## No real PII

Demo emails are **fake** (`@example.com`). Replace with sanitized test data for your environment; never commit real resident data.
