# Member 3 Presentation Script - Legacy PHP UI, Session & RBAC

**Time allocation:** 1:30 - 2:00 minutes  
**Visuals:** Copy to PPT/Google Slides (1 slide per section). Use Mermaid from FLOWCHART.md §2 for RBAC diagram. Demo on local server (XAMPP/PHP -S).  
**Team context:** From TEAM_WORK_DIVISION.md - Member 3: Phase 1 (Legacy PHP enhancements for staff/admin RBAC).

## 1. Introduction (15 sec)
\"Hi everyone, I'm [Your Name], Member 3. My focus was enhancing the legacy PHP app with Role-Based Access Control (RBAC) for staff vs admin, using PHP sessions. This ensures staff handle day-to-day (create/submit), while admins approve sensitive actions like permit approval, resident archive, or barangay changes - per RESIDENT_ROADMAP.md.\"

*[Slide: Role quote from TEAM_WORK_DIVISION.md + RBAC matrix table]*

| Action | Staff | Admin |
|--------|-------|-------|
| Create/edit resident | Yes | Yes |
| Change barangay_id | No | Yes |
| Archive resident | No | Yes |
| Permit approve/reject | No | Yes |

## 2. Before/After Overview (20 sec)
\"The original app had basic login but no roles. I wired `users.role` (staff/admin) into sessions via session_init.php, added require_auth.php middleware for protection, and built minimal UI screens for residents/permits (residents.php, permits.php).\"

*[Slide: Code snippet from session_init.php]*

```php
// In session_init.php (enhanced)
session_start();
if (isset($_SESSION['user_id'])) {
    $role = getUserRole($_SESSION['user_id']); // Query DB
    $_SESSION['role'] = $role; // 'staff' or 'admin'
}
```

*[Transition: \"Let me demo staff vs admin.\"]*

## 3. Live Demo (45 sec)
**Pre-req:** Local server running (`php -S localhost:8000 inventoryProjBrgy/inventoryProjBrgy/`), DB imported (mimds.sql + migrations), test users: staff1/staffpass (staff), admin1/adminpass (admin).

**Part A: Staff Login (20 sec)**
1. Go to http://localhost:8000/Login.php → Login: staff1/staffpass.
2. Navigate to residents.php → Can list/create resident (e.g., name: Test Resident, email: test@barangay.ph, barangay_id:1) → Submit permit draft → Status: submitted.
3. Try permit approve → Blocked: \"Access denied - Admin only.\"

**Part B: Admin Login (25 sec)**
1. Logout → Login: admin1/adminpass.
2. residents.php → Edit existing → Change barangay_id (staff couldn't) → Archive resident (status=archived).
3. permits.php → Approve submitted permit → Status: ready_for_payment.

*[Backup: Screenshots if server issues. Slide with flowchart from FLOWCHART.md §2]*

## 4. Key Implementation Highlights (20 sec)
- **RBAC Check:** require_auth.php (before sensitive pages):
  ```php
  require_once 'require_auth.php';
  if ($_SESSION['role'] !== 'admin') {
      header('Location: /unauthorized.php');
      exit;
  }
  ```
- **Legacy UI:** residents.php (list/search/create), permit_view.php (status transitions). Used prepared statements (dbcon.php) for security.
- **Challenges:** Existing code had no roles; solution: minimal migrations (001_users_role_and_id.sql) + session propagation without breaking inventory module.

*[Slide: Code diffs + ERD snippet from RESIDENT_ROADMAP.md §2.1]*

## 5. Testing & Security Notes (15 sec)
\"Manual tests (MANUAL_TESTS.md): 10+ cases - e.g., IT-03: Staff cannot approve permit (fail expected).\"

*[Quick table on slide]*

| Test ID | Description | Staff | Admin |
|---------|-------------|-------|-------|
| IT-01 | Create resident | Pass | Pass |
| IT-03 | Approve permit | Fail | Pass |
| IT-05 | Archive resident | Fail | Pass |

\"Security: Sessions regenerated on login, no PII in sessions, role checks on every protected route. Technical debt: Legacy SQL - new code uses PDO/prepared statements.\"

## 6. Handover (10 sec)
\"My foundation enables API integration (Member 4). Questions? Over to [Next Member] for REST/JWT.\"

**Total: ~1:45. Rehearse with timer. Export diagrams to PNG for slides. Ready for Canvas/PDF integration.\"
