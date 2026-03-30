# Staff & admin — project roadmap (Barangay e-Governance evolution)

> **File:** `STAFF_ADMIN_ROADMAP.md` (canonical name). *Previously **`STAFF + ADMIN_ROADMAP.md`** — use this filename in links and Git; avoids spaces/special characters.*

**Target deadline:** April 18, 2026  
**Repository root:** this `inv` directory  
**Course alignment:** `SIA2-DOCU.MD` (System Integration)

This roadmap is separate from the official course brief. Use it for **internal planning**; submit documentation in the **required PDF format** per `SIA2-DOCU.MD`.

**Resident track (data & workflow):** `RESIDENT_ROADMAP.md`  
**Flowcharts (Mermaid):** `FLOWCHART.md` — export to PNG/SVG from [mermaid.live](https://mermaid.live) for the PDF.

**Team (4 people):** work split by role — **`TEAM_WORK_DIVISION.md`** (Group Leader + Members 2–4).

---

## 1. Vision (v1 scope)

Evolve the existing **Medical Inventory & Monitoring** prototype into a **barangay office system** that:

- Keeps the **current inventory / medical module** as-is initially, **merged at the data level** via shared **barangay** context and optional link (`patient.resident_id`).
- Adds **e-governance** capabilities: **resident records**, **permit/clearance** (one type in v1), **mock payment flow**, **real email notifications**, **integration events (DB outbox)**.
- Exposes a **REST API** with **JWT** for integration demos, while **legacy PHP pages** continue to use **session** during transition.
- Deploys the app to **Render** (PHP via **Dockerfile** as needed). **Production database** strategy is decided early (Render **PostgreSQL** vs **hosted MySQL** — see Phase 0).

**v1 roles:** `staff`, `admin` only (no `resident` self-service portal until a later phase unless scope changes).

**Permit type v1:** single type — **Barangay Clearance** (or equivalent label).

---

## 2. Locked decisions (summary)

| Area | Decision |
|------|----------|
| Auth (UI) | PHP **session** for legacy pages |
| Auth (API) | **JWT** (Bearer token) |
| API stack | **PHP + Composer + micro-router** (no Node/Laravel required) |
| RBAC | **`staff`** (day-to-day), **`admin`** (users/roles / sensitive actions) |
| Payments | **Mock** provider + documented webhook/idempotency pattern |
| Notifications | **Real email** (SMTP); **no SMS** in v1 |
| Middleware | **MySQL/MariaDB `integration_events` outbox** + **worker** script or Render **cron / second service** (no Docker Compose / RabbitMQ requirement for local dev) |
| Testing | **Manual integration test matrix** + **Postman**; **light automation**: **Newman and/or PHPUnit** in **GitHub Actions** |
| Version control | **Git** / GitHub (group owner: designated member) |
| Hosting | **Render** for web app; **DB URL** via environment variables |
| Endpoints | Minimum **5** REST operations on resources + **`POST /auth/login`** (or equivalent) as **6th** for JWT |

---

## 3. Phases & milestones

### Phase 0 — Foundations (do first)

- [ ] **Database hosting:** Choose and document **production DB** (e.g. Render Postgres vs external MySQL). Run **one** connectivity test from local app to that class of server before feature freeze.
- [ ] **Git:** Branch strategy (`main` + short `feature/*`). **`.env` / secrets** not committed.
- [ ] **ERD v1** (for PDF): barangay, users + roles, residents, permit_type (1 row), permits, payments (mock), notifications or mail log, `integration_events`; **existing** `patient` / `medsupply` / … retained with **merge note** (shared barangay; optional `resident_id` on `patient` later).
- [ ] **Security baseline:** Plan migration from plaintext passwords to **`password_hash`** for `users`; **prepared statements** on all **new** API code.

### Phase 1 — Data model & staff RBAC (legacy UI)

Resident tables + staff CRUD begin once RBAC baseline ships. **Detail:** `RESIDENT_ROADMAP.md`.

- [ ] Migrations / SQL: `users.role` (`staff` | `admin`) or equivalent; seed **one admin**.
- [ ] **`require_auth.php`** (or successor): enforce **role** on legacy routes (e.g. admin-only settings).
- [ ] New tables: **residents**, **permit_types** (seed clearance), **permits**, **payments**, **integration_events** (+ optional **notification log**).
- [ ] **Manual tests** for “staff cannot do X / admin can do Y” on existing pages.

### Phase 2 — REST API + JWT + Postman

- [ ] **Composer** + **micro-router** entry (e.g. `api/index.php`) with routes aligned to rubric: **GET, POST, PUT, PATCH, DELETE** on agreed resources + **login**.
- [ ] JWT issuance on login; middleware: verify JWT + **role claims** on protected routes.
- [ ] **Postman collection** + environment (`base_url`, `token`).
- [ ] **Integration testing section** in doc: table of **IT-xx** cases + screenshots.

### Phase 3 — Workflows: permit, mock pay, outbox, email

- [ ] **Workflow:** resident record (staff-entered v1) → create permit → status transitions → **mock payment** → update status → **insert outbox event** → **worker** sends email (SMTP from env).
- [ ] Document **data flow** and **sequence** for PDF (Integration Design).
- [ ] Mock webhook handler (if in scope) with **signature check** documented for production parity.

### Phase 4 — DevOps: CI + Render

- [ ] **GitHub Actions:** on push — PHP + Composer install, **`php -l`** and/or **PHPUnit** and/or **Newman** (pick what fits; keep workflow **green**).
- [ ] **Dockerfile** for PHP app; **Render** Web Service; env: `DATABASE_URL`, `JWT_SECRET`, SMTP vars, `APP_ENV`.
- [ ] **Worker / cron** for outbox on Render (or documented limitation + manual trigger for prototype).
- [ ] **Health** route + **minimal logging** (no secrets/PII in logs).

### Phase 5 — Documentation & submission (before April 18, 2026)

- [ ] PDF per `SIA2-DOCU.MD`: architecture, integration, ERD, security, DevOps pipeline diagram, tests, conclusion.
- [ ] **Presentation** slides: problem, architecture, **integration demo**, security & DevOps, conclusion.
- [ ] **Canvas:** representative submits PDF + slides + **repo link** (and **live URL** if required by instructor).

---

## 4. SIA2 rubric quick map

| Rubric area | Where it shows up in this roadmap |
|-------------|-----------------------------------|
| System design & architecture | Phase 0 ERD + architecture diagram |
| Prototype | Phases 1–3 + legacy + API |
| Integration (APIs & middleware) | Phase 2 API + Phase 3 outbox & email |
| Testing & security | Phase 2 Postman; Phase 4 CI; Phases 1–2 RBAC & validation |
| DevOps | Phase 4 Git + Actions + Render |
| Documentation | Phase 5 PDF |
| Presentation | Phase 5 |

---

## 5. Risks & mitigations

| Risk | Mitigation |
|------|------------|
| Scope creep (residents portal, many permit types) | Keep v1 to **staff/admin** + **one permit type**; document Phase 2+ in limitations |
| Render + DB mismatch | **Phase 0** decision and one **end-to-end** deploy test |
| Outbox never processed | Schedule **worker** or document **inline process** with honest limitation |
| Email blocked / spam | Use reputable SMTP; keep **logs** and **screenshots** for grading |
| Legacy SQL injection | Isolate **new** API as **gold standard**; plan legacy hardening or mark as technical debt |

---

## 6. Next actions (immediate)

1. Confirm **production database** target (Postgres vs MySQL host) with the group.  
2. Freeze **ERD v1** and create migration scripts.  
3. Add **`role`** to users and protect one **admin-only** page.  
4. Scaffold **`api/`** + Composer + first **login + one GET** with JWT.

---

*Adjust checkboxes and dates as the group progresses.*
