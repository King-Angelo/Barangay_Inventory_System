<?php
// Task 3 — Create a new permit (staff or admin)
require_once __DIR__ . '/require_auth.php';
include __DIR__ . '/dbcon.php';    // sets $con
include __DIR__ . '/actions.php';  // functions

$prefill_resident = isset($_GET['resident_id']) ? (int)$_GET['resident_id'] : 0;
$permit_types     = get_permit_types();
$error            = htmlspecialchars($_GET['error'] ?? '', ENT_QUOTES, 'UTF-8');

// Resident search
$search    = trim($_GET['search'] ?? '');
$residents = array();
$brgy_sql  = '';
if (!function_exists('is_admin') || !is_admin()) {
    $bid = (int)($_SESSION['barangay_id'] ?? 1);
    $brgy_sql = ' AND barangay_id = ' . $bid;
}
if ($search !== '') {
    $esc = mysqli_real_escape_string($con, $search);
    $q   = mysqli_query($con,
        "SELECT id, last_name, first_name, email
         FROM residents
         WHERE status='active'
           $brgy_sql
           AND (last_name LIKE '%$esc%' OR first_name LIKE '%$esc%' OR email LIKE '%$esc%')
         LIMIT 20");
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $residents[] = $row;
        }
    }
}
?><!DOCTYPE html>
<html>
<head><?php include __DIR__ . '/head.php'; ?><title>New Permit</title></head>
<body>
<div class="pane">
  <?php include __DIR__ . '/nav.php'; ?>
  <div class="content">
    <h2>New Permit Application</h2>
    <a href="permits.php">&larr; Back to Permits</a>
    <br><br>

    <?php if ($error !== ''): ?>
      <p style="color:#c0392b;font-weight:600"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="GET" style="margin-bottom:14px">
      <input type="hidden" name="resident_id" value="<?php echo $prefill_resident; ?>">
      <div style="display:flex;gap:8px;max-width:420px">
        <input class="form-control" type="text" name="search"
               value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
               placeholder="Search resident by name or email&hellip;">
        <button type="submit" class="btn btn-default">Search</button>
      </div>
    </form>

    <?php if ($search !== '' && empty($residents)): ?>
      <p style="color:#888">No active residents found for that search.</p>
    <?php elseif (!empty($residents)): ?>
      <p style="font-size:.9em;color:#555">Click a name to pre-fill the Resident ID below:</p>
      <ul>
        <?php foreach ($residents as $res): ?>
          <li>
            <a href="permit_form.php?resident_id=<?php echo $res['id']; ?>&search=<?php echo urlencode($search); ?>">
              <?php echo htmlspecialchars($res['last_name'] . ', ' . $res['first_name'] . ' (' . $res['email'] . ')', ENT_QUOTES, 'UTF-8'); ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <form method="POST" action="permit_save.php">
      <table class="tableheader">
        <tr>
          <td><label>Resident ID *</label></td>
          <td>
            <input type="number" name="resident_id"
                   value="<?php echo $prefill_resident ?: ''; ?>"
                   placeholder="Enter ID or search above" required
                   style="width:200px;padding:6px;border-radius:4px;border:1px solid #ccc">
          </td>
        </tr>
        <tr>
          <td><label>Permit Type *</label></td>
          <td>
            <select name="permit_type_id" required
                    style="width:200px;padding:6px;border-radius:4px;border:1px solid #ccc">
              <option value="">— select —</option>
              <?php foreach ($permit_types as $pt): ?>
                <option value="<?php echo $pt['id']; ?>">
                  <?php echo htmlspecialchars($pt['name'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
      </table>
      <br>
      <button type="submit" class="btn btn-primary" name="btn-send">Create Permit (Draft)</button>
      <a href="permits.php" class="btn btn-default">Cancel</a>
    </form>
  </div>
</div>
<script src="assets/js/jquery.min.js"></script>
<script src="assets/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
