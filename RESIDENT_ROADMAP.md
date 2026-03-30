# Resident — Roadmap (Data + Workflow)

> **File:** `RESIDENT_ROADMAP.md` (canonical name). *Previously `resident_roadmap.md` — use this path in links and PRs.*  
> There is no separate `residennt.md` / `resident.md`. Staff/admin phases stay in **`STAFF_ADMIN_ROADMAP.md`**.

**Target deadline:** April 18, 2026  
**Parent plan:** `STAFF_ADMIN_ROADMAP.md`  

This doc focuses **only** on **Resident** as **master data** and **workflows**. Official course deliverables stay in PDF per `SIA2-DOCU.MD`.

### Where the flowcharts live

Charts stay in **one** `FLOWCHART.md` (easier to maintain). For resident-specific visuals:

| Section | What it shows |
|---------|----------------|
| **§1** | End-to-end permit flow: search/create resident (**name / email / permit ref**), **UNIQUE** barangay+email, **staff submit → admin approve**, pay, outbox, email |
| **§2** | Resident CRUD: validation, **admin-only archive** (soft), no hard delete in v1 |

Split into extra `.md` files **only** if your PDF needs separate chapters—avoid duplicating the same Mermaid in two places.

---

## Frozen group decisions (requirements)

| Topic | Decision |
|--------|-----------|
| **Uniqueness (Q1)** | Enforce **`UNIQUE (barangay_id, email)`** on `residents` so the same person is not duplicated per barangay. Reject create/update that would violate it (API + DB constraint). |
| **Change barangay** | **Admin only** (`barangay_id` updates). |
| **Archive (Q3)** | **Soft archive only** for v1: set `status = archived`. **Only admin** may archive (or restore, if you add that). **No hard delete** in normal operations—keeps audit trail aligned with barangay records. |
| **Extra PII** | Birthdate, gender, address, etc. **optional**. |
| **Search (Q5)** | Find residents by **name**, **email**, and **permit reference** (join `permits` or `reference_no`). |
| **Permit approval (Q6)** | **Staff** prepares and moves permit to **`submitted`**. **Admin only** sets **`approved`** or **`rejected`** (and downstream states your policy allows). |
| **Health link (Q7)** | Prefer **`patient.resident_id`** (nullable FK) over merging tables: same real person can exist in both modules without one giant table. |
| **Locale (Q8)** | **Bilingual** names allowed; support **Unicode** (e.g. ñ, apostrophes). DB: **utf8mb4**; do not validate “ASCII-only.” |

---

## 1. Two meanings of “resident” (don’t mix them)

| Layer | v1 (now) | Later (optional) |
|--------|-----------|------------------|
| **Resident = data** | Row in **`residents`** created/edited by **staff/admin** | Same table; possible **self-service** updates with rules |
| **Resident = login** | **Not in v1** (no `resident` **role** on `users`) | `users.resident_id` + JWT/session for “my permits” portal |

**Rule:** In v1, **only `staff` and `admin`** authenticated users touch resident records through the app/API.

---

## 2. Data model (baseline)

**Purpose:** One person’s barangay record for clearance and **email notifications**.

### 2.1 Table: `residents` (suggested columns)

| Column | Notes |
|--------|--------|
| `id` | PK |
| `barangay_id` | FK → existing barangay table |
| `last_name`, `first_name`, `middle_name` | Match local forms |
| `email` | Required for **SMTP** notifications in v1 |
| `phone` | Optional (no SMS in v1) |
| `birthdate`, `gender`, `address_line` | Optional; add only if permit/legal needs |
| `status` | e.g. `active` \| `archived` |
| `created_at`, `updated_at` | Audit |
| `created_by_user_id` | FK → `users` (which staff created the row) |

### 2.2 Link to health module (`patient`)

- **Recommended:** add nullable **`patient.resident_id` → `residents.id`** when you integrate (v1 or v1.1).  
- **Why:** Keeps **`patient`** and **`residents`** as separate bounded contexts; links the **same person** without duplicating governance fields into the medical schema (or vice versa).  
- **Optional later:** `household_id` (FK) if you introduce households  

### 2.3 Uniqueness & search indexes

- Database: **`UNIQUE (barangay_id, email)`** on `residents`.  
- **Indexes (suggested):** `(barangay_id, email)` (unique), index on **`email`** for lookups, composite or prefix index on **`last_name`, `first_name`** for search; **`permits.reference_no`** (or `id`) for permit-ref search (join `permits` → `residents`).  
- *Edge case:* One shared family email ⇒ **one** resident row per barangay+email; state in **scope/limitations** or add **`official_id`** / PhilSys later if required.

### 2.4 ERD relationships (v1)

- **Barangay** `1 — N` **Resident**  
- **Resident** `1 — N` **Permit** (`permits.resident_id`)  
- **Users** `1 — N` **Resident** via `created_by_user_id` (who filed), not “resident login”  
- **Resident** `1 — 0..N` **Patient** via optional **`patient.resident_id`** (integrate when ready)

---

## 3. Workflow — v1 (staff-managed)

**Prerequisite:** RBAC baseline shipped (`STAFF_ADMIN_ROADMAP.md` Phase 1 one-liner).

```text
Staff/Admin login (session)
  → Search resident (name / email / permit ref) or create (enforce UNIQUE barangay + email)
  → Staff: create/edit permit draft, move to submitted
  → Admin only: approve or reject permit
  → If approved: ready_for_payment → mock payment → paid/issued
  → integration_events → worker → email to resident.email
```

Align detail with **§1** in `FLOWCHART.md`.

**RBAC summary**

| Action | Staff | Admin |
|--------|------|-------|
| Create / read / update resident (names, contact, optional fields) | Yes | Yes |
| Change **`barangay_id`** | No | Yes |
| **Archive** resident (`status = archived`) | No | Yes |
| Permit: draft → **`submitted`** | Yes | Yes (if you allow) |
| Permit: **`approved`** / **`rejected`** | **No** | **Yes** |
| After approval: payment / outbox | Per your policy (often **staff** runs mock pay, **admin** optional) — **document who** in PDF |

---

## 4. API (JWT) — resident resources

All routes: **`staff` or `admin`** only in v1; claims checked on every call.

Suggested alignment with rubric (adjust paths to match your micro-router):

| Method | Path | v1 purpose |
|--------|------|------------|
| `GET` | `/api/v1/residents` or `...?q=&email=&permit_ref=` | **List / search** by **name**, **email**, **permit reference** (implement as query params your router supports) |
| `GET` | `/api/v1/residents/{id}` | Read one |
| `POST` | `/api/v1/residents` | Create (**enforce UNIQUE** barangay+email) |
| `PUT` | `/api/v1/residents/{id}` | Full replace (**staff** cannot change `barangay_id` unless admin — enforce in handler) |
| `PATCH` | `/api/v1/residents/{id}` | Partial update; **`status: archived`** → **admin only** |
| `DELETE` | `/api/v1/residents/{id}` | **Not used in v1.** Use **`PATCH`** to archive. Reserve **`DELETE`** for **permits** if rubric needs five verbs across resources. |

*If the group caps “5 verbs” across **residents + permits**, put **DELETE** on **permits** and keep residents to GET/POST/PUT/PATCH — match `STAFF_ADMIN_ROADMAP.md`.*

**Validation (server-side):** required names + barangay + valid email; **UTF-8 / utf8mb4** for names (no ASCII-only stripping); **prepared statements** only. Enforce **unique (barangay_id, email)** on create/update.

---

## 5. Legacy UI (optional parallel)

- **List / create / edit** resident screens (PHP + session) mirroring API rules.  
- Or **API-first** + minimal admin HTML — acceptable if Postman + one demo page suffice for prototype.

---

## 6. Integration & notifications

- **Outbox payload** should include `resident_id` (and `permit_id`) so the worker loads **`residents.email`**.  
- **Logs:** never log full email bodies with secrets; OK to log `resident_id`, `event_type`, `sent` flag.

---

## 7. Testing checklist (resident slice)

- [ ] Create resident → correct `barangay_id`, `created_by_user_id`.  
- [ ] Duplicate **same barangay + email** → 409 / 4xx with clear message.  
- [ ] Invalid email / missing barangay → 4xx.  
- [ ] Staff **cannot** approve permit, change `barangay_id`, or archive resident.  
- [ ] Admin **can** approve/reject, archive, change barangay.  
- [ ] Permit issued → email received at **`residents.email`** (screenshot for PDF).  
- [ ] Postman: GET list search by name, email, permit ref; GET one by `id`.  
- [ ] Unicode name (e.g. **Ñ**, **’**) round-trips and displays correctly (**utf8mb4**).

---

## 8. Future phase — resident portal (out of v1)

Only after staff pipeline is stable:

- [ ] Add `users.resident_id` (nullable) or separate resident auth table.  
- [ ] Role **`resident`**: scoped to **own** `resident_id` only.  
- [ ] Pages or API: “my permits”, read-only or limited edit profile.  
- [ ] Update ERD + RBAC section in PDF.

---

## 9. Milestone map (vs main roadmap)

| Main roadmap phase | Resident-focused work |
|--------------------|------------------------|
| **0** | ERD includes `residents` + **`UNIQUE(barangay_id,email)`**; plan nullable **`patient.resident_id`** |
| **1** | Table + staff CRUD + link to permits |
| **2** | JWT endpoints for residents + Postman folder |
| **3** | End-to-end email uses resident row (see flowchart) |
| **5** | PDF: resident entity description + workflow paragraph |

---

*If `household` or national ID is added later, revisit uniqueness (may supplement barangay+email).*

---

## Change log (internal)

| When | Change |
|------|--------|
| Latest | Renamed file to **`RESIDENT_ROADMAP.md`**; update all repo links to this name. |
| Prior | Diagram pointers, search/API table, RBAC matrix, indexes, Unicode test, single `FLOWCHART.md` |
