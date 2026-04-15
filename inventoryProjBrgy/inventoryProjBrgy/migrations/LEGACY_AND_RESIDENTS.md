# Legacy schema vs residents / permits (dev notes)

Canonical product rules: **`RESIDENT_ROADMAP.md`**. This file only explains **how the old PHP inventory stack coexists** with the new barangay resident + permit model.

## Legacy (from `mimds.sql`)

| Area | Tables / pattern | Notes |
|------|------------------|--------|
| **Barangay list** | `barangays` (`n` INT PK, `brgy` name) | Used as FK for **`residents.barangay_id`** and **`users.barangay_id`**. |
| **Med / supply** | `medsupply`, `logs`, `rhusupply`, … | **Unrelated** to residents/permits; keep as-is for legacy screens. |
| **Health / RHU style** | `patient` | PK **`n`** (INT). **`brgy`** is a **varchar barangay name**, not a FK to `barangays.n` — historical data shape. |
| **Users** | `users` | Legacy columns **`UserName`**, **`PaSS`**; migration **`001`** adds **`id`**, **`role`**, **`password_hash`**, **`barangay_id`**. New auth uses **`password_hash`**. |

Do **not** merge `patient` into `residents` in v1. They are **separate** domains (governance/clearance vs medical supply context).

## New (migrations `002`+)

| Table | Role |
|-------|------|
| **`residents`** | Master resident record; **`UNIQUE(barangay_id, email)`**; **`created_by_user_id` → users.id**. |
| **`permit_types`**, **`permits`** | Clearance workflow; FK to **`residents`**. |
| **`payments`**, **`integration_events`**, **`notification_log`** | Mock pay + outbox + mail audit. |

## Optional link: `patient.resident_id`

**Migration:** `006_patient_resident_link.sql`.

- Adds nullable **`patient.resident_id` → `residents.id`** (Q7 in **`RESIDENT_ROADMAP.md`**).
- **When NULL:** legacy patient row is unchanged; no barangay governance record.
- **When set:** same real person can appear in both modules; join for email/contact from **`residents`**.

Apply **`006`** only after **`002`** exists and you are ready to backfill or link rows manually.

```sql
-- Example: link one patient row to a resident (adjust IDs)
UPDATE `patient` SET `resident_id` = 2 WHERE `n` = 1;

SELECT p.`n`, p.`Lname`, p.`Fname`, r.`email`, r.`id` AS `resident_id`
FROM `patient` p
LEFT JOIN `residents` r ON r.`id` = p.`resident_id`;
```

Re-running **`006`** fails if **`resident_id`** already exists — that is expected.

## Summary

| Question | Answer |
|----------|--------|
| Does every `patient` need a `resident`? | **No.** Link when integration needs it. |
| Same barangay field everywhere? | **`residents`** use **`barangay_id` → barangays.n**; **`patient.brgy`** remains legacy text unless you migrate data separately. |
| New features on old tables? | Prefer **prepared statements** and avoid widening legacy tables except via numbered migrations. |
