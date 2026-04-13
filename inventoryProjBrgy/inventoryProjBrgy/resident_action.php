<?php
require_once __DIR__ . '/require_admin.php';
include __DIR__ . '/dbcon.php';
include __DIR__ . '/actions.php';

$action = isset($_GET['action']) ? (string)$_GET['action'] : '';
$id     = isset($_GET['id'])     ? (int)$_GET['id']     : 0;

if ($action === 'archive' && $id > 0) {
    $ok = archive_resident($id);
    if ($ok) {
        header('Location: residents.php?msg=' . urlencode('Resident archived successfully.'));
    } else {
        header('Location: residents.php?error=' . urlencode('Archive failed.'));
    }
} else {
    header('Location: residents.php');
}
exit;
