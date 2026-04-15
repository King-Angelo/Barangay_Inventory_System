# Password security baseline (`users`: `PaSS` → `password_hash`)

Aligned with **`STAFF_ADMIN_ROADMAP.md`** Phase 0: move off **plaintext** `PaSS` to **bcrypt** in `password_hash`.

## Current behavior (no script required to log in)

- **Migration `001`** already adds **`password_hash`** and keeps legacy **`PaSS`** until you migrate.
- **`Login.php`** and **`App\Api\AuthService`** accept:
  1. **`password_verify`** against `password_hash` when set, else  
  2. **Legacy** match against `PaSS`.

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

If some users **already** have `password_hash` but **`PaSS` still has old text**:

```bash
php tools/migrate_passwords_to_bcrypt.php --clear-plaintext-only   # dry-run
php tools/migrate_passwords_to_bcrypt.php --apply --clear-plaintext-only
```

Requires **`.env.local`** (or `.env`) with working **`DB_*`** — same as the app.

## Optional: drop `PaSS` later

After **all** rows use `password_hash` and **`PaSS` is empty**, you may run (once, manual):

```sql
ALTER TABLE `users` DROP COLUMN `PaSS`;
```

Only if nothing else in the codebase still references `PaSS` (search the repo first).

## Not in SQL

Bcrypt must be generated in **PHP** (`password_hash`). There is **no** pure-SQL migration for this step.
