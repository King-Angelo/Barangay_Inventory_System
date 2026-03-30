# Flowcharts — Barangay e-Governance (v1)

**Related docs:** `STAFF_ADMIN_ROADMAP.md` (phases), `RESIDENT_ROADMAP.md` (resident data + API).

View diagrams in **GitHub**, **VS Code** (Markdown Preview + Mermaid), or paste into [Mermaid Live Editor](https://mermaid.live) to **export PNG/SVG** for your PDF.

---

## 1. Permit & clearance workflow (v1 — staff-entered)

Primary happy path: **Barangay Clearance**, **mock payment**, **email to `residents.email`** (no SMS in v1). **Resident** is **data only**—no resident login in v1.

```mermaid
flowchart TD
    A([Start]) --> B[Staff or Admin logs in\nlegacy UI: PHP session]
    B --> C{Authorized\nstaff or admin?}
    C -->|No| Z([End — redirect to login])
    C -->|Yes| D{Resident:\nexisting or new?}
    D -->|Search / pick| D1[Find by name, email,\nor permit ref\n→ resident_id + email]
    D -->|Create| D2[Validate PII + barangay_id + email\nUNIQUE barangay+email\nINSERT residents + created_by_user_id]
    D1 --> E[Create Permit\nFK resident_id, type: clearance\nstatus: draft]
    D2 --> E
    E --> F[Staff: submit for review\nstatus: submitted\nadmin cannot be skipped]
    F --> G{Admin review only}
    G -->|Reject| H[status: rejected\noptional: outbox notify → email]
    H --> Z
    G -->|Approve| I[status: ready_for_payment]
    I --> J[Initiate mock payment session\nsee §3]
    J --> K[Mock provider:\nsimulated success]
    K --> L[Update payments + permits\nstatus: paid / issued]
    L --> M[INSERT integration_events\npayload: resident_id, permit_id,\nevent e.g. permit.issued]
    M --> N[Outbox worker or cron\nprocesses pending rows]
    N --> O[Load residents.email\nSend via SMTP]
    O --> P([End — email delivered])
```

---

## 2. Resident master data (staff / admin only — v1)

No **resident** role on `users` until a future portal phase — see **`RESIDENT_ROADMAP.md` §8**. Data, rules, and workflow: **§§1–3** in that file.

```mermaid
flowchart TD
    S([Staff or Admin authenticated]) --> V{Validate input\nnames, barangay_id, email}
    V -->|Invalid| X([400 — do not save])
    V -->|Valid| W{Operation}
    W -->|Create| C[INSERT residents\nset created_by_user_id]
    W -->|Read| R[SELECT residents\nscoped by policy]
    W -->|Update| U[PUT/PATCH residents\nadmin rules for barangay/archive if defined]
    W -->|Archive| A[Admin only:\nPATCH status = archived\nno hard delete in v1]
    C --> OK([OK])
    R --> OK
    U --> OK
    A --> OK
```

---

## 3. Who uses which door (session vs JWT)

```mermaid
flowchart LR
    subgraph clients [Clients]
        U[Browser — legacy pages]
        P[Postman / future SPA]
    end

    subgraph app [Barangay application]
        L[Legacy PHP + session\nRBAC: staff / admin]
        A[REST API + micro-router\nComposer, JWT]
    end

    subgraph data [Data and integration]
        DB[(MySQL — Render Private\nService or external host;\nMariaDB-compatible)]
        O[(integration_events\noutbox)]
        W[Worker — email / logs]
    end

    U --> L
    P --> A
    L --> DB
    A --> DB
    L --> O
    A --> O
    O --> W
    W --> M[SMTP server]
```

---

## 4. Mock payment + outbox (integration)

Worker resolves **`resident_id`** from payload (or JOIN via `permit_id`) to find **email**.

```mermaid
flowchart TD
    A[Permit status:\nready_for_payment] --> B[POST mock payment intent\nlocal / simulated provider]
    B --> C{Payment OK?}
    C -->|No| D[Update permit / payment failed\nlog or event row]
    C -->|Yes| E[Update DB:\npermit + payment succeeded]
    E --> F[INSERT integration_events\npayload JSON: resident_id, permit_id,\ntype e.g. permit.issued\nstatus: pending]
    F --> G[Worker picks pending rows]
    G --> H[Resolve email from residents\nSend SMTP + mark event done]
    H --> I[Optional: audit / health log]
```

---

## 5. Entity trail (context — not a full ERD)

Use **F. Database Design** / ERD in PDF for keys and full schema. This is a **logical** chain for narratives.

```mermaid
flowchart LR
    BG[Barangay] --> RS[Resident]
    RS --> PM[Permit]
    PM --> PAY[Payment\nmock in v1]
    RS -.->|email| OB[integration_events\n+ worker]
    PM -.->|triggers| OB
```

---

## 6. DevOps — push to GitHub (light automation)

```mermaid
flowchart LR
    A[Developer push\nfeature branch] --> B[GitHub Actions\nCI workflow]
    B --> C[Composer install]
    C --> D[php -l / PHPUnit / Newman]
    D --> E{Pass?}
    E -->|Yes| F[Merge to main\noptional: deploy Render]
    E -->|No| G[Fix and push again]
```

---

## Document map (PDF)

| Diagram | Suggested PDF section |
|---------|------------------------|
| §1 Permit + resident link | Integration Design, System Architecture |
| §2 Resident CRUD (staff) | Database / Security — RBAC, `RESIDENT_ROADMAP.md` narrative |
| §3 Session vs JWT | Security — Authentication & Authorization |
| §4 Mock pay + outbox | Middleware / Event-based Integration |
| §5 Entity trail | Introduction or Database Design preamble |
| §6 CI | DevOps Pipeline |

---

*Aligned with `STAFF_ADMIN_ROADMAP.md` and `RESIDENT_ROADMAP.md` (staff/admin v1, mock payments, real email, DB outbox, no resident login in v1).*
