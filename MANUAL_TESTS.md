# Manual Integration Test Cases — Member 3
**System:** Barangay Inventory & Monitoring System  
**Scope:** Session handling, RBAC (staff vs admin), Resident & Permit flows  
**Tester instructions:** Run each case against a live XAMPP instance with migrations 001–007 applied.  
Use two browser profiles — one logged in as **staff**, one as **admin**.

---

## Preconditions

| Account  | Username | Role  | Setup |
|----------|----------|-------|-------|
| Admin    | `admin`  | admin | Seeded by `007_seeds.sql`; password `ChangeMe2026!` |
| Staff    | `staff1` | staff | Insert manually: `INSERT INTO users (UserName, PaSS, role) VALUES ('staff1','staff123','staff');` |

---

## IT-01 — Login stores role in session

| Field | Detail |
|-------|--------|
| **Precondition** | User is logged out |
| **Steps** | 1. Open `Login.php` → enter `admin` / `ChangeMe2026!` → click Login |
| **Expected** | Redirected to `brgy.php`; session contains `user = admin` and `role = admin` |
| **Pass criteria** | No PHP warning; nav shows SETTINGS link |
| **Fail indicator** | Settings link missing or PHP notice about undefined `$_SESSION['role']` |

---

## IT-02 — Login with staff account stores staff role

| Field | Detail |
|-------|--------|
| **Precondition** | `staff1` account exists (see Preconditions) |
| **Steps** | 1. Login as `staff1` / `staff123` |
| **Expected** | Redirected to `brgy.php`; nav shows **no** SETTINGS link; logout label shows "STAFF" |
| **Pass criteria** | Nav renders correctly with no Settings link |
| **Fail indicator** | Settings link appears for staff |

---

## IT-03 — Unauthenticated user is redirected to Login

| Field | Detail |
|-------|--------|
| **Precondition** | No active session (clear cookies or use incognito) |
| **Steps** | 1. Navigate directly to `residents.php` |
| **Expected** | Redirected to `Login.php` |
| **Pass criteria** | Browser lands on Login page; no resident data visible |
| **Fail indicator** | Residents page loads without login |

---

## IT-04 — Staff cannot access Settings (admin-only route)

| Field | Detail |
|-------|--------|
| **Precondition** | Logged in as `staff1` |
| **Steps** | 1. Navigate directly to `Settings.php` (URL bar) |
| **Expected** | HTTP 302 redirect to `brgy.php?denied=1` |
| **Pass criteria** | Page does not render Settings form; "Access denied" notice shown on dashboard |
| **Fail indicator** | Settings page loads for staff |

---

## IT-05 — Admin can access Settings

| Field | Detail |
|-------|--------|
| **Precondition** | Logged in as `admin` |
| **Steps** | 1. Click SETTINGS in nav |
| **Expected** | Settings page renders with username/password form |
| **Pass criteria** | Form is visible and functional |
| **Fail indicator** | Admin is redirected or sees 403 |

---

## IT-06 — Staff can create a resident

| Field | Detail |
|-------|--------|
| **Precondition** | Logged in as `staff1`; barangay with ID 1 exists |
| **Steps** | 1. Click RESIDENTS → New Resident → fill all required fields → Create Resident |
| **Expected** | Redirected to `residents.php` with success message; new resident row visible |
| **Pass criteria** | Resident appears in list with status "Active" |
| **Fail indicator** | DB error or no redirect |

---

## IT-07 — Staff can edit a resident

| Field | Detail |
|-------|--------|
| **Precondition** | At least one resident exists |
| **Steps** | 1. In residents list click Edit → change phone number → Save Changes |
| **Expected** | Redirected to `residents.php` with "Resident updated successfully" |
| **Pass criteria** | Updated phone number shows in the list |
| **Fail indicator** | Error message or old value still showing |

---

## IT-08 — Staff cannot archive a resident

| Field | Detail |
|-------|--------|
| **Precondition** | Logged in as `staff1`; at least one active resident exists |
| **Steps** | 1. Navigate directly to `resident_action.php?action=archive&id=1` |
| **Expected** | Redirected to `brgy.php?denied=1` (blocked by `require_admin_role()`) |
| **Pass criteria** | Resident status remains "active" in DB |
| **Fail indicator** | Resident is archived by staff |

---

## IT-09 — Admin can archive a resident

| Field | Detail |
|-------|--------|
| **Precondition** | Logged in as `admin`; at least one active resident exists |
| **Steps** | 1. Click Archive on any active resident row → confirm prompt |
| **Expected** | Resident disappears from active list; visible when "Show Archived" is toggled |
| **Pass criteria** | `residents.status = 'archived'` in DB |
| **Fail indicator** | Resident still shows as active |

---

## IT-10 — Staff can create a permit (draft)

| Field | Detail |
|-------|--------|
| **Precondition** | Logged in as `staff1`; at least one active resident and permit type exist |
| **Steps** | 1. Click PERMITS → New Permit → enter Resident ID → select "Barangay Clearance" → Create Permit |
| **Expected** | Redirected to `permits.php` with success message; new row with status DRAFT |
| **Pass criteria** | Permit row shows DRAFT badge |
| **Fail indicator** | Error or no permit row |

---

## IT-11 — Staff can submit a draft permit

| Field | Detail |
|-------|--------|
| **Precondition** | A DRAFT permit exists |
| **Steps** | 1. In permits list, click Submit on a DRAFT row → confirm prompt |
| **Expected** | Status changes to SUBMITTED; "Submitted By" column shows staff username |
| **Pass criteria** | `permits.status = 'submitted'` in DB |
| **Fail indicator** | Status unchanged or error |

---

## IT-12 — Staff cannot approve or reject a permit

| Field | Detail |
|-------|--------|
| **Precondition** | Logged in as `staff1`; a SUBMITTED permit exists |
| **Steps** | 1. Open `permit_view.php?id=X` for a submitted permit |
| **Expected** | Approve/Reject form is **not visible** on the page |
| **Pass criteria** | Page shows permit details only; no decision buttons |
| **Fail indicator** | Approve/Reject buttons appear for staff |

---

## IT-13 — Staff cannot call permit_action approve directly

| Field | Detail |
|-------|--------|
| **Precondition** | Logged in as `staff1`; a SUBMITTED permit with ID `X` exists |
| **Steps** | 1. Navigate directly to `permit_action.php?action=approve&id=X` |
| **Expected** | Redirected to `brgy.php?denied=1` |
| **Pass criteria** | `permits.status` unchanged in DB |
| **Fail indicator** | Permit status changes to "approved" |

---

## IT-14 — Admin can approve a submitted permit

| Field | Detail |
|-------|--------|
| **Precondition** | Logged in as `admin`; a SUBMITTED permit exists |
| **Steps** | 1. Click Review on a SUBMITTED permit → add optional remarks → click Approve |
| **Expected** | Redirected to `permits.php` with "Permit approved" message; status = APPROVED |
| **Pass criteria** | `permits.status = 'approved'`, `approved_by` = admin ID in DB |
| **Fail indicator** | Status unchanged or wrong `approved_by` |

---

## IT-15 — Admin can reject a submitted permit

| Field | Detail |
|-------|--------|
| **Precondition** | Logged in as `admin`; a SUBMITTED permit exists |
| **Steps** | 1. Click Review → enter remarks "Missing documents" → click Reject |
| **Expected** | Status = REJECTED; remarks saved |
| **Pass criteria** | `permits.status = 'rejected'`, `remarks = 'Missing documents'` in DB |
| **Fail indicator** | Status unchanged |

---

## IT-16 — Logout destroys session

| Field | Detail |
|-------|--------|
| **Precondition** | Any user is logged in |
| **Steps** | 1. Click LOGOUT in nav |
| **Expected** | Session destroyed; redirected to `Login.php` |
| **Pass criteria** | Navigating to `brgy.php` after logout redirects back to Login |
| **Fail indicator** | Session persists; pages still accessible |

---

*Documented by: Member 3*  
*Paired with Group Leader for PDF §Security & RBAC prose (see `SECURITY_NOTES.md`).*
