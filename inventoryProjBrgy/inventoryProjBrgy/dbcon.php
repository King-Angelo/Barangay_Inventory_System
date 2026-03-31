<?php
chdir(__DIR__);
require_once __DIR__ . '/env_bootstrap.php';
inv_load_dotenv(__DIR__ . '/.env.local');
inv_load_dotenv(__DIR__ . '/.env');

$host = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT');
$port = ($dbPort !== false && $dbPort !== '') ? (int) $dbPort : 3306;
if ($port < 1 || $port > 65535) {
	$port = 3306;
}
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '';
$dbname = getenv('DB_NAME') ?: 'mimds';

$con = mysqli_connect($host, $username, $password, $dbname, $port);
if ($con === false) {
	error_log('mysqli_connect failed: ' . mysqli_connect_error());
	http_response_code(503);
	exit('Database unavailable.');
}
mysqli_set_charset($con, 'utf8mb4');
