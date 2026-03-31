# Team work division — four members

**Deadline:** April 18, 2026  
**Main roadmap:** `STAFF_ADMIN_ROADMAP.md`  
**Resident detail:** `RESIDENT_ROADMAP.md` · **Diagrams:** `FLOWCHART.md`

Fill in **Member 2–4** names. **Group Leader** = you (GitHub / repo owner).

---

## Roles at a glance

| Person | Focus | Primary phases |
|--------|--------|----------------|
| **Group Leader** (you) | Coordination, architecture narrative, CI/CD & deploy, final PDF / Canvas | 0 (facilitate), 4–5, reviews all |
| **Member 2** | Database, ERD, SQL migrations, data integrity | 0–1 |
| **Member 3** | Legacy PHP UI, session, RBAC (`staff` / `admin`) on existing app | 1 |
| **Member 4** | REST API, JWT, Postman, permit/mock-pay/outbox, SMTP worker | 2–3 (supports 4) |

*Everyone* helps with **manual test cases** and **presentation** rehearsal.

---

## Group Leader — responsibilities

**Name:** *(you)*  

1. **Repository & process:** `main` / `feature/*`, protect `main` if possible; resolve merge conflicts; ensure `.env` is gitignored.  
2. **Phase 0 facilitation:** **Production DB locked — Option B:** **MySQL** with **local-first** dev (XAMPP). Chase: `DB_HOST` / `.env.local`, **`mysqldump`** backup habit, **one** connectivity test, `.env.example` placeholders; optional public deploy is **not** assumed.  
3. **Integration story:** Own the **architecture / data-flow** sections of the PDF; keep `FLOWCHART.md` aligned with reality.  
4. **Phase 4 (DevOps):** **GitHub Actions** workflow; document **local run** + optional deploy env/secrets if the group hosts publicly.  
5. **Phase 5:** Merge sections into **final PDF** per `SIA2-DOCU.MD`; **Canvas** submission; **presentation** structure and timekeeping.  
6. **Weekly sync:** 30 min — status vs roadmap checkboxes, blockers, next-week owners.

**Optional:** Light coding on the **API** or **worker** if Member 4 is overloaded.

---

## Member 2 — Data & persistence

**Name:** _____________________  

1. **ERD v1** (draw + export for PDF): barangay, `users` + role, `residents` (**`UNIQUE(barangay_id,email)`**), permits, payments, `integration_events`, optional notification log; note legacy `patient` / `medsupply` + optional `patient.resident_id`.  
2. **Production MySQL (Option B):** Help provision **MySQL** (local XAMPP or chosen host); import **`mimds.sql`** + new migrations; document **`.env.example`** placeholders (no secrets); confirm **app → DB** connectivity with Leader.  
3. **SQL / migrations:** Create/alter scripts for new tables + `users.role`; seed **admin** + **permit_types** (Barangay Clearance).  
4. **Align with** `RESIDENT_ROADMAP.md` (frozen rules: admin-only archive/bar change, staff submit → admin approve).  
5. **Support** Member 3 & 4 with **column names**, FKs, and test data dumps (no real PII in repo).  
6. **Document** table descriptions for PDF **§F. Database Design**.

---

## Member 3 — Legacy PHP, session & RBAC

**Name:** _____________________  

1. **`users.role`:** Wire login/session to carry `staff` vs `admin` (after Member 2 schema).  
2. **`require_auth.php` (or successor):** Enforce authentication; **admin-only** routes (e.g. Settings, archive, barangay change, **permit approve/reject** per `RESIDENT_ROADMAP.md`).  
3. **Legacy UI:** Minimal screens to **list/create/edit residents** and **drive permit status** (staff: draft→submitted; admin: approve/reject) if not API-only.  
4. **Manual tests:** Document **IT-xx** cases for “staff cannot X / admin can Y.”  
5. **Security note for PDF:** Session handling, RBAC rules (pair with Leader for wording).

---

## Member 4 — API, JWT, integration workflow

**Name:** _____________________  

1. **Composer + micro-router** (`api/`): `POST /auth/login` (JWT) + **five resource verbs** (e.g. residents + permits per group plan).  
2. **JWT:** Claims include `sub` / `role`; middleware on protected routes.  
3. **Postman:** Collection + environment (`base_url`, `token`); export for repo / PDF screenshots.  
4. **Phase 3:** Mock payment flow, **`integration_events` insert**, **worker/cron** sketch, **SMTP** send using `residents.email`.  
5. **Support Leader:** **`health.php`** for sanity checks if needed; **Newman** or **PHPUnit** for CI.

---

## Shared / Phase 5 (all members)

| Task | Owner suggestion |
|------|------------------|
| Integration test matrix (manual) | Member 3 + 4 draft; Leader reviews |
| Postman screenshots | Member 4 |
| ERD + table descriptions PDF | Member 2 |
| Security & RBAC prose | Leader + Member 3 |
| DevOps diagram | Leader + Member 4 |
| Presentation slides (10–15 min) | Split by section; **Leader** runs dry run |

---

## Weekly checklist (Leader runs)

- [ ] Each member: done / blocked / next 3 tasks  
- [ ] Roadmap checkboxes updated in `STAFF_ADMIN_ROADMAP.md`  
- [ ] Open PRs reviewed within 48h  
- [ ] No secrets in Git  

---

## Definition of done (v1)

- [ ] Staff vs admin behavior matches **`RESIDENT_ROADMAP.md`** frozen decisions  
- [ ] Postman proves **login + 5 verbs +** core permit path  
- [ ] At least one **email** sent via outbox/worker path (screenshot)  
- [ ] **CI** green on `main`  
- [ ] **Runnable prototype** (local + env template) or documented **source** submission per Canvas  
- [ ] **PDF** complete per `SIA2-DOCU.MD`

---

*Rename members or redistribute tasks here first, then mirror any change in `STAFF_ADMIN_ROADMAP.md` if you add dates.*
