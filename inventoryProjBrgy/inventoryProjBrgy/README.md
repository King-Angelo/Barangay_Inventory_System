# Barangay Inventory — PHP app & API

## For Member 3 (legacy UI) & Member 4 (REST API)

| Need | Location |
|------|----------|
| **Column names, FKs, seeded test users** | [`migrations/DEV_REFERENCE.md`](migrations/DEV_REFERENCE.md) |
| **Safe test data policy & approved SQL scripts** | [`migrations/SANITIZED_DATA.md`](migrations/SANITIZED_DATA.md) |
| **Migration order** | [`migrations/README.md`](migrations/README.md) |
| **Env template (no secrets)** | [`.env.example`](.env.example) → copy to `.env.local` |
| **Migrate `users.PaSS` → `password_hash`** | [`migrations/PASSWORD_MIGRATION.md`](migrations/PASSWORD_MIGRATION.md) · `tools/migrate_passwords_to_bcrypt.php` |
| **Legacy session / RBAC / IT-xx manual tests** | Repo root [`MANUAL_TESTS.md`](../../MANUAL_TESTS.md) · [`SECURITY_NOTES.md`](../../SECURITY_NOTES.md) |
| **Postman** | Repo root `postman/` |
| **API entry** | `api/index.php` (JWT on `/v1/residents`, `/v1/permits`) |
| **Local API server** | `php -S 127.0.0.1:8765 dev-router.php` from this directory |

Composer: run `composer install` here before using the API.
