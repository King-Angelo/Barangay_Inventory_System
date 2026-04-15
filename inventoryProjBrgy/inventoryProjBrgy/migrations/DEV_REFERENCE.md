# Developer reference — DB ↔ app (Member 2 support)

Use this when wiring **Member 3** (legacy PHP), **Member 4** (REST API), or **Postman**. Column names match **MySQL**; JSON APIs use the same names unless noted.

## PII and test data

- Do **not** commit SQL dumps with real resident names, phones, or government IDs.
- Seeded demo data uses **`@example.com`** and obvious placeholders (`Demo`, `Resident`, `DEV-REF-*`).
- Legacy **`mimds.sql`** may contain sample names from an older import — treat as **non-production** only.

## Seeded identities (after `007_seeds.sql`)

| Purpose | Where | Notes |
|--------|--------|--------|
| Admin login | `users.UserName = 'admin'` | `role = admin`, `barangay_id` NULL (all barangays). JWT/API + session auth. |
| Staff login | `users.UserName = 'staff_dev'` | `role = staff`, `barangay_id = 1`. |
| Demo resident | `residents.email = 'resident.demo@example.com'` | `barangay_id = 1`, `status = active`. |
| Permit type | `permit_types.name = 'Barangay Clearance'` | Usually `id = 1` on a fresh DB — **do not hard-code id** in app code; resolve by name or query. |

Default bcrypt password for **`admin`** / **`staff_dev`** (rotate in production): see **`SEEDS.md`**.

## Foreign keys (new schema)

```
barangays (n)
    ↑
    ├── users.barangay_id (nullable)
    ├── residents.barangay_id
    └── (legacy patient.brgy is varchar name, not FK)

users (id)
    ↑
    ├── residents.created_by_user_id
    ├── permits.submitted_by, permits.approved_by
    └── …

residents (id)
    ↑
    ├── permits.resident_id
    ├── patient.resident_id (after migration 006)
    └── notification_log.resident_id

permit_types (id)
    ↑
    └── permits.permit_type_id

permits (id)
    ↑
    ├── payments.permit_id
    └── integration_events (aggregate_id + aggregate_type='permit' in payload)

integration_events (id)
    ↑
    └── notification_log.integration_event_id (nullable)
```

## Tables — columns APIs touch often

### `users` (post migration 001)

| Column | Type | Notes |
|--------|------|--------|
| `id` | INT PK | JWT `sub` / API user id |
| `UserName` | VARCHAR | Login name (unique) |
| `role` | `staff` \| `admin` | RBAC |
| `password_hash` | VARCHAR | Bcrypt; API login uses this |
| `barangay_id` | INT NULL | FK → `barangays.n`; NULL = super-admin |

### `residents`

| Column | Type | Notes |
|--------|------|--------|
| `id` | INT PK | |
| `barangay_id` | INT | FK → `barangays.n` |
| `last_name`, `first_name`, `middle_name` | VARCHAR | Search / display |
| `email` | VARCHAR | Required; **UNIQUE with `barangay_id`** |
| `status` | `active` \| `archived` | Admin-only archive in v1 |
| `created_by_user_id` | INT | FK → `users.id` |

### `permits`

| Column | Type | Notes |
|--------|------|--------|
| `id` | INT PK | |
| `resident_id` | INT | FK → `residents.id` |
| `permit_type_id` | INT | FK → `permit_types.id` |
| `reference_no` | VARCHAR | **Unique**; search target |
| `status` | ENUM | `draft` → `submitted` → `approved` \| `rejected` → … |
| `submitted_by`, `approved_by` | INT NULL | FK → `users.id` |

### `integration_events` (outbox)

| Column | Type | Notes |
|--------|------|--------|
| `event_type` | VARCHAR | e.g. `permit.approved` |
| `aggregate_id` | INT | e.g. permit id |
| `aggregate_type` | VARCHAR | e.g. `permit` |
| `payload` | JSON | Must include `resident_id`, `permit_id` for mailer |
| `status` | ENUM | `pending` → `processing` → `processed` \| `failed` |

## REST API (`api/index.php`) — JSON ↔ DB

Base path depends on host (e.g. `http://127.0.0.1:8765/api` with `dev-router.php`). All protected routes need **`Authorization: Bearer <jwt>`** from `POST /v1/auth/login`.

### Residents

| HTTP | Path | Query / body | DB alignment |
|------|------|----------------|--------------|
| GET | `/v1/residents` | `barangay_id`, optional `q`, `include_archived=1` (admin) | Lists `residents` for barangay; `q` searches `last_name`, `first_name`, `email`. Response includes `barangay_name` from join. |
| GET | `/v1/residents/{id}` | — | Row from `residents` |
| POST | `/v1/residents` | JSON: **`barangay_id`**, **`last_name`**, **`first_name`**, **`email`**; optional `middle_name`, `phone`, `birthdate`, `gender`, `address_line` | Same column names as table |
| PATCH | `/v1/residents/{id}` | JSON: any of `last_name`, `first_name`, `middle_name`, `email`, `phone`, `birthdate`, `gender`, `address_line`; admin may add **`barangay_id`**, **`status`** (`active` \| `archived`) | Same as columns |

### Permits

| HTTP | Path | Query / body | DB alignment |
|------|------|----------------|--------------|
| GET | `/v1/permits` | optional `resident_id` | Filters `permits` |
| GET | `/v1/permits/{id}` | — | Joined row incl. `permit_type_name`, resident names |
| POST | `/v1/permits` | JSON: **`resident_id`**, **`permit_type_id`** | Creates `draft` |
| PATCH | `/v1/permits/{id}` | JSON: **`action`**: `submit` \| `approve` \| `reject`; optional **`remarks`** | Updates `permits.status`, etc. |

Login body: **`username`**, **`password`** → `users.UserName` + `password_hash`.

## Repo pointers

| Artifact | Path |
|----------|------|
| Migrations order | `migrations/README.md` |
| Seeds & passwords | `migrations/SEEDS.md` |
| Sanitized data policy | `migrations/SANITIZED_DATA.md` |
| Legacy vs `residents` | `migrations/LEGACY_AND_RESIDENTS.md` |
| Env template | `inventoryProjBrgy/inventoryProjBrgy/.env.example` |
| Postman | `postman/Barangay_Inventory_API.postman_collection.json` |
| Optional draft permit row | `migrations/optional_sample_permit.sql` (run after **007**) |
| App entry for devs | `inventoryProjBrgy/inventoryProjBrgy/README.md` |

## Optional sample permit

After **`007_seeds.sql`**, you may run **`optional_sample_permit.sql`** once to insert a **draft** permit for the demo resident (`DEV-REF-SAMPLE-001`). Safe to skip if you create permits only via API/UI.
