<?php
require_once __DIR__ . '/require_auth.php';
include __DIR__ . '/dbcon.php';
include __DIR__ . '/actions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['btn-send'])) {
    header('Location: permits.php');
    exit;
}

$resident_id    = (int)($_POST['resident_id']    ?? 0);
$permit_type_id = (int)($_POST['permit_type_id'] ?? 0);
$user_id        = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

if ($resident_id < 1 || $permit_type_id < 1) {
    header('Location: permit_form.php?error=' . urlencode('Please select a resident and a permit type.'));
    exit;
}

$new_id = create_permit($resident_id, $permit_type_id, $user_id);

if ($new_id > 0) {
    header('Location: permits.php?msg=' . urlencode('Permit created as Draft. Click Submit to send for admin review.'));
} else {
    header('Location: permit_form.php?error=' . urlencode('Failed to create permit. Check that the Resident ID exists.'));
}
exit;
