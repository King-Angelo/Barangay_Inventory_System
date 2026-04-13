<?php
require_once __DIR__ . '/session_init.php';
inv_session_start();
session_destroy();
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$loginUrl = $base === '' ? '/Login.php' : $base . '/Login.php';
header('Location: ' . $loginUrl);
exit;
