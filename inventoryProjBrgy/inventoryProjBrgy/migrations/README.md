# SQL migrations (Member 2 — database)

Run these **in order** on database **`mimds`** after importing the **legacy base** from the repo root `mimds.sql` (tables `users`, `barangays`, `patient`, `medsupply`, etc.).

## Order

| File | Purpose |
|------|---------|
| `001_users_role_and_id.sql` | `users.id` PK, `role`, `password_hash`, `barangay_id`, timestamps (keeps legacy `UserName` / `PaSS` until you migrate passwords). |
| `002_residents.sql` | `residents` + **`UNIQUE(barangay_id, email)`** per `RESIDENT_ROADMAP.md`. |
| `003_permit_types_and_permits.sql` | `permit_types`, `permits` (draft → submitted → approved/rejected + extended statuses). |
| `004_payments.sql` | Mock `payments` (one row per permit when you implement pay flow). |
| `005_integration_events_and_notification_log.sql` | Outbox + mail audit log. |
| `006_patient_resident_link.sql` | Optional `patient.resident_id` → `residents.id`. |
| `007_seeds.sql` | Admin, **`staff_dev`**, **Barangay Clearance** `permit_types`, optional demo resident; see **`SEEDS.md`**. |

**All-in-one:** `apply_all_migrations.sql` concatenates `001`–`007` (use on a **fresh** DB or expect duplicate-object errors if tables already exist).

## Commands (Windows XAMPP / CMD)

```bat
cd C:\xampp\mysql\bin
mysql -u root -p mimds < C:\path\to\inv\mimds.sql
mysql -u root -p mimds < C:\path\to\inv\inventoryProjBrgy\inventoryProjBrgy\migrations\001_users_role_and_id.sql
REM … repeat 002 through 007, or use apply_all_migrations.sql once
```

## Idempotency

- **`001`** is **not** safe to run twice (duplicate columns / PK errors).
- **`002`–`005`** use `CREATE TABLE IF NOT EXISTS` where applicable.
- **`006`** fails if `resident_id` already exists — skip or edit manually.
- **`007`** uses `INSERT … ON DUPLICATE KEY UPDATE` for seeded rows.

## Verify (optional)

After migrations, run:

```bat
mysql -u root -p mimds < migrations\verify_schema.sql
```

## Seeds & bcrypt workflow

See **`SEEDS.md`** (what is seeded, dev passwords, how to rotate `password_hash`).

## Legacy vs new schema

See **`LEGACY_AND_RESIDENTS.md`** (`patient` / `medsupply` vs `residents` / `permits`, optional **`patient.resident_id`** from migration **006**).

## Env template (no secrets)

Canonical template: **`../.env.example`** (i.e. `inventoryProjBrgy/inventoryProjBrgy/.env.example`). Copy to **`.env.local`** in that directory. The file `migrations/env.example` here is only a pointer. See **`PRODUCTION_SETUP.md`** §5.

## Support for other devs (repo)

- **`DEV_REFERENCE.md`** — column names, FKs, seeded test users/resident, links to Postman and env.
- **`optional_sample_permit.sql`** — optional **draft** permit `DEV-REF-SAMPLE-001` for the demo resident (run after **007**) if you want a row without using the API first.
