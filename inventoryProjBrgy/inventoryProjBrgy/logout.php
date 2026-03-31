<?php
require_once __DIR__ . '/session_init.php';
inv_session_start();
session_destroy();
header('Location: Login.php');
exit;
