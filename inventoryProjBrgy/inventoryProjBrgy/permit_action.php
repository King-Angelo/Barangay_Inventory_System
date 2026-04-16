<?php
require_once __DIR__ . '/require_auth.php';
include __DIR__ . '/dbcon.php';
include __DIR__ . '/actions.php';

$action    = isset($_GET['action'])  ? (string)$_GET['action']  : (isset($_POST['action']) ? (string)$_POST['action'] : '');
$permit_id = isset($_GET['id'])      ? (int)$_GET['id']          : (isset($_POST['permit_id']) ? (int)$_POST['permit_id'] : 0);
$user_id   = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

if ($permit_id < 1) {
    header('Location: permits.php?error=' . urlencode('Invalid permit.'));
    exit;
}

if ($action === 'submit') {
    $pchk = get_permit($permit_id);
    if (!$pchk || !user_can_access_resident_id((int)$pchk['resident_id'])) {
        header('Location: permits.php?error=' . urlencode('Access denied.'));
        exit;
    }
    $ok = submit_permit($permit_id, $user_id);
    if ($ok) {
        header('Location: permits.php?msg=' . urlencode('Permit submitted for admin review.'));
    } else {
        header('Location: permits.php?error=' . urlencode('Submit failed. Permit may no longer be in Draft status.'));
    }

} elseif ($action === 'approve') {
    require_admin_role();
    $remarks = trim(isset($_POST['remarks']) ? (string)$_POST['remarks'] : '');
    $ok      = decide_permit($permit_id, $user_id, 'approved', $remarks);
    if ($ok) {
        header('Location: permits.php?msg=' . urlencode('Permit approved successfully.'));
    } else {
        header('Location: permit_view.php?id=' . $permit_id . '&error=' . urlencode('Approve failed. Permit must be in Submitted status.'));
    }

} elseif ($action === 'reject') {
    require_admin_role();
    $remarks = trim(isset($_POST['remarks']) ? (string)$_POST['remarks'] : '');
    $ok      = decide_permit($permit_id, $user_id, 'rejected', $remarks);
    if ($ok) {
        header('Location: permits.php?msg=' . urlencode('Permit rejected.'));
    } else {
        header('Location: permit_view.php?id=' . $permit_id . '&error=' . urlencode('Reject failed. Permit must be in Submitted status.'));
    }

} else {
    header('Location: permits.php');
}
exit;
