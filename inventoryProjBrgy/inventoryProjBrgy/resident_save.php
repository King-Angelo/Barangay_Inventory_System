<?php
require_once __DIR__ . '/require_auth.php';
include __DIR__ . '/dbcon.php';
include __DIR__ . '/actions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['btn-send'])) {
    header('Location: residents.php');
    exit;
}

$id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$data = array(
    'last_name'    => trim($_POST['last_name']    ?? ''),
    'first_name'   => trim($_POST['first_name']   ?? ''),
    'middle_name'  => trim($_POST['middle_name']  ?? ''),
    'email'        => trim($_POST['email']         ?? ''),
    'phone'        => trim($_POST['phone']         ?? ''),
    'birthdate'    => trim($_POST['birthdate']     ?? ''),
    'gender'       => trim($_POST['gender']        ?? ''),
    'address_line' => trim($_POST['address_line']  ?? ''),
    'barangay_id'  => (int)($_POST['barangay_id'] ?? 1),
);

if ($id > 0) {
    $ok = update_resident($id, $data);
    if ($ok) {
        header('Location: residents.php?msg=' . urlencode('Resident updated successfully.'));
    } else {
        header('Location: resident_form.php?id=' . $id . '&error=' . urlencode('Update failed. Email may already be used in this barangay.'));
    }
} else {
    $uid    = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    $new_id = create_resident($data, $uid);
    if ($new_id > 0) {
        header('Location: residents.php?msg=' . urlencode('Resident created successfully.'));
    } else {
        header('Location: resident_form.php?error=' . urlencode('Create failed. Email may already be registered in this barangay.'));
    }
}
exit;
