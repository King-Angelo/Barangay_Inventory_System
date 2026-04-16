# Password security baseline (`users`: `PaSS` → `password_hash`)

Aligned with **`STAFF_ADMIN_ROADMAP.md`** Phase 0: move off **plaintext** `PaSS` to **bcrypt** in `password_hash`.

## Current behavior (no script required to log in)

- **Migration `001`** already adds **`password_hash`** and keeps legacy **`PaSS`** until you migrate.
- **`Login.php`** and **`App\Api\AuthService`** accept:
  1. **`password_verify`** against `password_hash` when set, else  
  2. **Legacy** match against `PaSS`.

## Schema detection

The CLI checks **`INFORMATION_SCHEMA`** for **`users.id`**. If **`id`** exists (after migration **001**), updates use **`id`**; if not (legacy table with **`UserName`** as the only key), updates use **`UserName`**. The **`password_hash`** column must still exist — run **`001_users_role_and_id.sql`** first if it is missing.

## When to run the CLI migration

Run on **development/staging** before production, after backup:

- Any **`users`** row with **non-empty `PaSS`** and **empty `password_hash`** should be hashed so plaintext is no longer the source of truth.

## Tool

From **`inventoryProjBrgy/inventoryProjBrgy`**:

```bash
# 1) Preview (no writes)
php tools/migrate_passwords_to_bcrypt.php

# 2) Write bcrypt hashes
php tools/migrate_passwords_to_bcrypt.php --apply

# 3) Recommended: clear plaintext column after hashing
php tools/migrate_passwords_to_bcrypt.php --apply --clear-plaintext
```

If some users **already** have `password_hash` but **`PaSS` still has old text** (e.g. you ran `--apply` in one step and `--apply --clear-plaintext` in a second command **before** this fix):

```bash
php tools/migrate_passwords_to_bcrypt.php --clear-plaintext-only   # dry-run
php tools/migrate_passwords_to_bcrypt.php --apply --clear-plaintext-only
```

A single **`--apply --clear-plaintext`** run now performs a **second pass** to clear `PaSS` when no rows need hashing anymore.

Requires **`.env.local`** (or `.env`) with working **`DB_*`** — same as the app.

## Optional: drop `PaSS` later

After **all** rows use `password_hash` and **`PaSS` is empty**, you may run (once, manual):

```sql
ALTER TABLE `users` DROP COLUMN `PaSS`;
```

Only if nothing else in the codebase still references `PaSS` (search the repo first).

## Troubleshooting

| Symptom | What to do |
|---------|------------|
| `No connection could be made … actively refused it` | Start **MySQL** (e.g. XAMPP). Confirm **`DB_HOST`** / **`DB_PORT`** in **`.env.local`** (often `127.0.0.1` and `3306`). |
| `Unknown column 'id'` (older script) | Use the latest **`migrate_passwords_to_bcrypt.php`** — it supports legacy **`UserName`**-only rows. |
| `password_hash` missing | Run **`migrations/001_users_role_and_id.sql`**. |

## Not in SQL

Bcrypt must be generated in **PHP** (`password_hash`). There is **no** pure-SQL migration for this step.
