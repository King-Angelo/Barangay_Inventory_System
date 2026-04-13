<?php
require_once __DIR__ . '/require_auth.php';
include __DIR__ . '/dbcon.php';
include __DIR__ . '/actions.php';

$resident_id = isset($_GET['resident_id']) ? (int)$_GET['resident_id'] : null;
$permits     = list_permits($resident_id);
$msg         = htmlspecialchars($_GET['msg']   ?? '', ENT_QUOTES, 'UTF-8');
$error       = htmlspecialchars($_GET['error'] ?? '', ENT_QUOTES, 'UTF-8');

$status_colors = array(
    'draft'     => '#95a5a6',
    'submitted' => '#3498db',
    'approved'  => '#27ae60',
    'rejected'  => '#e74c3c',
    'paid'      => '#8e44ad',
    'issued'    => '#1a5276',
);
?><!DOCTYPE html>
<html>
<head>
  <?php include __DIR__ . '/head.php'; ?>
  <title>Permits</title>
  <style>
    .badge-status { display:inline-block; padding:2px 9px; border-radius:10px; font-size:.8em; color:#fff; font-weight:600; }
    .ok-msg  { color:#27ae60; font-weight:600; margin-bottom:10px; }
    .err-msg { color:#c0392b; font-weight:600; margin-bottom:10px; }
  </style>
</head>
<body>
<div class="pane">
  <?php include __DIR__ . '/nav.php'; ?>
  <div class="content">
    <h2>Permits / Barangay Clearance</h2>

    <?php if ($msg   !== ''): ?><p class="ok-msg"><?php echo $msg; ?></p><?php endif; ?>
    <?php if ($error !== ''): ?><p class="err-msg"><?php echo $error; ?></p><?php endif; ?>

    <div style="margin-bottom:14px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <a href="permit_form.php<?php echo $resident_id ? '?resident_id='.$resident_id : ''; ?>"
         class="btn btn-primary btn-sm">+ New Permit</a>
      <?php if ($resident_id): ?>
        <a href="permits.php" class="btn btn-default btn-sm">Show All Permits</a>
      <?php endif; ?>
    </div>

    <?php if (empty($permits)): ?>
      <p style="color:#888">No permits found.</p>
    <?php else: ?>
    <table class="tableheader table table-bordered" style="width:100%">
      <thead>
        <tr>
          <th>Ref #</th><th>Resident</th><th>Type</th><th>Status</th>
          <th>Submitted By</th><th>Approved By</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($permits as $p):
        $color = isset($status_colors[$p['status']]) ? $status_colors[$p['status']] : '#555';
      ?>
        <tr>
          <td><?php echo htmlspecialchars($p['reference_no'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($p['last_name'] . ', ' . $p['first_name'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($p['permit_type_name'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><span class="badge-status" style="background:<?php echo $color; ?>"><?php echo strtoupper($p['status']); ?></span></td>
          <td><?php echo htmlspecialchars($p['submitted_by_name'] ?? '&mdash;', ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($p['approved_by_name'] ?? '&mdash;', ENT_QUOTES, 'UTF-8'); ?></td>
          <td>
            <?php if ($p['status'] === 'draft'): ?>
              <a href="permit_action.php?action=submit&id=<?php echo $p['id']; ?>"
                 class="btn btn-xs btn-primary"
                 onclick="return confirm('Submit this permit for admin review?')">Submit</a>
            <?php endif; ?>
            <?php if ($p['status'] === 'submitted' && function_exists('is_admin') && is_admin()): ?>
              <a href="permit_view.php?id=<?php echo $p['id']; ?>" class="btn btn-xs btn-success">Review</a>
            <?php else: ?>
              <a href="permit_view.php?id=<?php echo $p['id']; ?>" class="btn btn-xs btn-default">View</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>
<script src="assets/js/jquery.min.js"></script>
<script src="assets/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
