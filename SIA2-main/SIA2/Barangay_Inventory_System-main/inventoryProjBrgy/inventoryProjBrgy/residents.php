<?php
require_once __DIR__ . '/require_auth.php';
include __DIR__ . '/dbcon.php';
include __DIR__ . '/actions.php';

$msg    = htmlspecialchars($_GET['msg']   ?? '', ENT_QUOTES, 'UTF-8');
$error  = htmlspecialchars($_GET['error'] ?? '', ENT_QUOTES, 'UTF-8');
$denied = isset($_GET['denied']);

$show_archived = (function_exists('is_admin') && is_admin()) && isset($_GET['archived']);
$barangay_id   = (int)($_SESSION['barangay_id'] ?? 1);

$residents = list_residents($barangay_id, $show_archived);
?><!DOCTYPE html>
<html>
<head>
  <?php include __DIR__ . '/head.php'; ?>
  <title>Residents</title>
  <style>
    .badge-active   { display:inline-block; background:#27ae60; color:#fff; padding:2px 9px; border-radius:10px; font-size:.8em; font-weight:600; }
    .badge-archived { display:inline-block; background:#95a5a6; color:#fff; padding:2px 9px; border-radius:10px; font-size:.8em; font-weight:600; }
    .badge-admin    { display:inline-block; background:#e67e22; color:#fff; padding:2px 8px; border-radius:10px; font-size:.78em; margin-left:6px; }
    .denied-msg     { background:#fdecea; color:#c0392b; padding:9px 14px; border-radius:5px; margin-bottom:14px; border:1px solid #f5c6cb; }
    .ok-msg         { color:#27ae60; font-weight:600; margin-bottom:10px; }
    .err-msg        { color:#c0392b; font-weight:600; margin-bottom:10px; }
  </style>
</head>
<body>
<div class="pane">
  <?php include __DIR__ . '/nav.php'; ?>
  <div class="content">
    <h2>
      Residents
      <?php if (function_exists('is_admin') && is_admin()): ?>
        <span class="badge-admin">ADMIN</span>
      <?php endif; ?>
    </h2>

    <?php if ($denied): ?><p class="denied-msg">&#9888; Access denied &mdash; admin only.</p><?php endif; ?>
    <?php if ($msg   !== ''): ?><p class="ok-msg"><?php echo $msg; ?></p><?php endif; ?>
    <?php if ($error !== ''): ?><p class="err-msg"><?php echo $error; ?></p><?php endif; ?>

    <div style="margin-bottom:14px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <a href="resident_form.php" class="btn btn-primary btn-sm">+ New Resident</a>
      <?php if (function_exists('is_admin') && is_admin()): ?>
        <?php if ($show_archived): ?>
          <a href="residents.php" class="btn btn-default btn-sm">Show Active Only</a>
        <?php else: ?>
          <a href="residents.php?archived=1" class="btn btn-default btn-sm">Show Archived</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <?php if (empty($residents)): ?>
      <p style="color:#888">No residents found.</p>
    <?php else: ?>
    <table class="tableheader table table-bordered table-hover" style="width:100%">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Gender</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($residents as $r): ?>
        <tr>
          <td>
            <a href="permits.php?resident_id=<?php echo $r['id']; ?>">
              <?php echo htmlspecialchars($r['last_name'] . ', ' . $r['first_name'] . ' ' . ($r['middle_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            </a>
          </td>
          <td><?php echo htmlspecialchars($r['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($r['phone'] ?? '&mdash;', ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($r['gender'] ?? '&mdash;', ENT_QUOTES, 'UTF-8'); ?></td>
          <td>
            <?php if ($r['status'] === 'active'): ?>
              <span class="badge-active">Active</span>
            <?php else: ?>
              <span class="badge-archived">Archived</span>
            <?php endif; ?>
          </td>
          <td>
            <a href="resident_form.php?id=<?php echo $r['id']; ?>" class="btn btn-xs btn-default">Edit</a>
            <a href="permits.php?resident_id=<?php echo $r['id']; ?>" class="btn btn-xs btn-info">Permits</a>
            <?php if (function_exists('is_admin') && is_admin() && $r['status'] === 'active'): ?>
              <a href="resident_action.php?action=archive&id=<?php echo $r['id']; ?>"
                 class="btn btn-xs btn-warning"
                 onclick="return confirm('Archive this resident? They will no longer appear in active lists.')">
                Archive
              </a>
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
