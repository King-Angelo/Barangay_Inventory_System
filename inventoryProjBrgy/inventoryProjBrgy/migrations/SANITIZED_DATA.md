# Sanitized test data (repo-safe)

Goal: **Member 3 / Member 4** can develop against predictable rows **without real PII** in Git.

## Approved scripts (commit these)

| Script | What it does |
|--------|----------------|
| **`007_seeds.sql`** | `admin`, `staff_dev`, **Barangay Clearance** `permit_types`, optional demo resident `resident.demo@example.com`. |
| **`optional_sample_permit.sql`** | Optional **draft** permit `DEV-REF-SAMPLE-001` linked to that demo resident (run **after** 007). |

All use **`@example.com`**, placeholder names, and **`DEV-*`** reference strings — safe for the repo.

## Do **not** commit

- **`mysqldump` / phpMyAdmin exports** with real barangay resident names, phones, or addresses from production.
- Screenshots or CSVs with real citizen data.
- A “full copy” of production `mimds` — if you need a local DB, keep dumps **outside** Git or in a private bucket per team policy.

## Legacy `mimds.sql` (repo root)

The bundled **`mimds.sql`** is a **development baseline** with legacy tables (`patient`, `medsupply`, …). Rows may look like real names from an old sample import — **not for production** and **not** a privacy-safe dataset. For demos, prefer **`007_seeds`** identities in **`DEV_REFERENCE.md`**.

## Verifying your DB

After migrations + seeds:

```bat
mysql -u root -p mimds < migrations\verify_schema.sql
```
