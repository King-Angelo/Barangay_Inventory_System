# Barangay Inventory System

PHP + MySQL application with a **JWT-backed REST API** (residents, permits, mock payments) and legacy barangay UI. Primary code lives under **`inventoryProjBrgy/inventoryProjBrgy/`**.

## Quick start (local API)

1. **PHP** 8.1+ with `mysqli`, **Composer**, **MySQL** or MariaDB (e.g. XAMPP).
2. **Database:** create `mimds` (utf8mb4) and apply migrations in order — see [`inventoryProjBrgy/inventoryProjBrgy/migrations/README.md`](inventoryProjBrgy/inventoryProjBrgy/migrations/README.md).
3. **Required for full API:** at minimum include **`004_payments.sql`** (`payments`) and **`005_integration_events_and_notification_log.sql`** (`integration_events`) so approve and mock pay succeed. Full production-style steps: [`inventoryProjBrgy/inventoryProjBrgy/migrations/PRODUCTION_SETUP.md`](inventoryProjBrgy/inventoryProjBrgy/migrations/PRODUCTION_SETUP.md).
4. **Environment:** copy **`inventoryProjBrgy/inventoryProjBrgy/.env.example`** to **`.env.local`** in the same folder and set `DB_*`, `JWT_SECRET`, etc. (`.env.local` is gitignored.)
5. **Dependencies:**  
   `cd inventoryProjBrgy/inventoryProjBrgy` → `composer install`
6. **Run the API router:** from that directory:
   ```bash
   php -S 127.0.0.1:8765 dev-router.php
   ```
   Base URL for JSON: **`http://127.0.0.1:8765/api`** (see `dev-router.php`).

## Postman

Import **`postman/Barangay_Inventory_API.postman_collection.json`**. Set **`{{base_url}}`** to `http://127.0.0.1:8765/api`, run **Auth → Login**, then follow the collection order (Create resident → Create draft permit → Submit → Approve → Mock pay). **201 Created** responses apply to create endpoints; login and most PATCH calls return **200**.

## Documentation map

| Topic | Location |
|--------|----------|
| API routes, PHPUnit, local server | [`inventoryProjBrgy/inventoryProjBrgy/README.md`](inventoryProjBrgy/inventoryProjBrgy/README.md) |
| Migration order & schema | [`inventoryProjBrgy/inventoryProjBrgy/migrations/README.md`](inventoryProjBrgy/inventoryProjBrgy/migrations/README.md) |
| Team / module ownership | [`TEAM_WORK_DIVISION.md`](TEAM_WORK_DIVISION.md) |
| Column names & JSON shapes | [`inventoryProjBrgy/inventoryProjBrgy/migrations/DEV_REFERENCE.md`](inventoryProjBrgy/inventoryProjBrgy/migrations/DEV_REFERENCE.md) |

## CI

GitHub Actions workflow: [`.github/workflows/ci.yml`](.github/workflows/ci.yml) (if present in this branch).

## License / course context

Use and attribution follow your institution’s requirements; see project briefs in the repo root as applicable.
