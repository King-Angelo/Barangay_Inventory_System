<?php
require_once __DIR__ . '/require_auth.php';
include __DIR__ . '/dbcon.php';
include __DIR__ . '/actions.php';

$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$permit = ($id > 0) ? get_permit($id) : null;

if (!$permit) {
    header('Location: permits.php?error=' . urlencode('Permit not found.'));
    exit;
}

if (!user_can_access_resident_id((int)$permit['resident_id'])) {
    header('Location: permits.php?error=' . urlencode('Access denied.'));
    exit;
}

$msg   = htmlspecialchars($_GET['msg']   ?? '', ENT_QUOTES, 'UTF-8');
$error = htmlspecialchars($_GET['error'] ?? '', ENT_QUOTES, 'UTF-8');

$status_colors = array(
    'draft'     => '#95a5a6',
    'submitted' => '#3498db',
    'approved'  => '#27ae60',
    'rejected'  => '#e74c3c',
    'paid'      => '#8e44ad',
    'issued'    => '#1a5276',
);
$color = isset($status_colors[$permit['status']]) ? $status_colors[$permit['status']] : '#555';
?><!DOCTYPE html>
<html>
<head>
  <?php include __DIR__ . '/head.php'; ?>
  <title>Permit &mdash; <?php echo htmlspecialchars($permit['reference_no'], ENT_QUOTES, 'UTF-8'); ?></title>
  <style>
    .permit-box { background:#fff; border:1px solid #ddd; border-radius:6px; padding:20px 24px; max-width:600px; margin-bottom:16px; }
    .permit-box table td:first-child { font-weight:600; color:#555; width:160px; padding:5px 12px 5px 0; }
    .permit-box table td { padding:5px 0; font-size:13px; }
    .status-pill { display:inline-block; padding:4px 14px; border-radius:14px; color:#fff; font-weight:700; font-size:.9em; }
    .action-box  { background:#f9f9f9; border:1px solid #e0e0e0; border-radius:6px; padding:16px 20px; max-width:600px; }
    .ok-msg  { color:#27ae60; font-weight:600; margin-bottom:10px; }
    .err-msg { color:#c0392b; font-weight:600; margin-bottom:10px; }
  </style>
</head>
<body>
<div class="pane">
  <?php include __DIR__ . '/nav.php'; ?>
  <div class="content">
    <a href="permits.php">&larr; Back to Permits</a>
    <br><br>

    <?php if ($msg   !== ''): ?><p class="ok-msg"><?php echo $msg; ?></p><?php endif; ?>
    <?php if ($error !== ''): ?><p class="err-msg"><?php echo $error; ?></p><?php endif; ?>

    <div class="permit-box">
      <h3>Permit Details</h3>
      <table>
        <?php
        $rows = array(
            array('Reference No',   $permit['reference_no']),
            array('Resident',       $permit['last_name'] . ', ' . $permit['first_name']),
            array('Permit Type',    $permit['permit_type_name']),
            array('Submitted By',   $permit['submitted_by'] ? 'User #' . $permit['submitted_by'] : '—'),
            array('Submitted At',   $permit['submitted_at'] ?? '—'),
            array('Approved By',    $permit['approved_by']  ? 'User #' . $permit['approved_by']  : '—'),
            array('Approved At',    $permit['approved_at']  ?? '—'),
            array('Remarks',        $permit['remarks']      ?? '—'),
            array('Created At',     $permit['created_at']),
        );
        foreach ($rows as $row): ?>
          <tr>
            <td><?php echo $row[0]; ?></td>
            <td><?php echo htmlspecialchars((string)$row[1], ENT_QUOTES, 'UTF-8'); ?></td>
          </tr>
        <?php endforeach; ?>
        <tr>
          <td>Status</td>
          <td><span class="status-pill" style="background:<?php echo $color; ?>"><?php echo strtoupper($permit['status']); ?></span></td>
        </tr>
      </table>
    </div>

    <!-- Staff: submit if draft -->
    <?php if ($permit['status'] === 'draft'): ?>
    <div class="action-box">
      <p><strong>This permit is a Draft.</strong> Submit it to send for admin review.</p>
      <a href="permit_action.php?action=submit&id=<?php echo $permit['id']; ?>"
         class="btn btn-primary"
         onclick="return confirm('Submit this permit for admin review?')">Submit for Review</a>
    </div>
    <?php endif; ?>

    <!-- Admin: approve or reject if submitted -->
    <?php if ($permit['status'] === 'submitted' && function_exists('is_admin') && is_admin()): ?>
    <div class="action-box" style="margin-top:16px">
      <h4>Admin Decision</h4>
      <form method="POST" action="permit_action.php">
        <input type="hidden" name="permit_id" value="<?php echo $permit['id']; ?>">
        <div style="margin-bottom:10px">
          <label style="display:block;font-weight:600;margin-bottom:4px">Remarks (optional)</label>
          <textarea name="remarks" rows="2"
                    style="width:100%;max-width:400px;padding:6px;border-radius:4px;border:1px solid #ccc"
                    placeholder="Add a note for the record&hellip;"></textarea>
        </div>
        <button type="submit" name="action" value="approve" class="btn btn-success"
                onclick="return confirm('Approve this permit?')">&#10003; Approve</button>
        &nbsp;
        <button type="submit" name="action" value="reject"  class="btn btn-danger"
                onclick="return confirm('Reject this permit?')">&#10007; Reject</button>
      </form>
    </div>
    <?php endif; ?>

  </div>
</div>
<script src="assets/js/jquery.min.js"></script>
<script src="assets/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
