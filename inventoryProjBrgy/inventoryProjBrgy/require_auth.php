<?php
chdir(__DIR__);
require_once __DIR__ . '/session_init.php';
inv_session_start();
if (!isset($_SESSION['user'])) {
    header('Location: Login.php');
    exit;
}
