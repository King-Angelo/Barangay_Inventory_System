<?php
chdir(__DIR__);
require_once __DIR__ . '/env_bootstrap.php';
inv_load_dotenv(__DIR__ . '/.env.local');
inv_load_dotenv(__DIR__ . '/.env');

// Default 127.0.0.1 (TCP). "localhost" on Linux PHP often uses a Unix socket that does not exist
// in some containers → mysqli_sql_exception. For remote MySQL, set DB_HOST (and related vars) via `.env.local` or server env.
$host = getenv('DB_HOST') !== false && getenv('DB_HOST') !== '' ? (string) getenv('DB_HOST') : '127.0.0.1';
$dbPort = getenv('DB_PORT');
$port = ($dbPort !== false && $dbPort !== '') ? (int) $dbPort : 3306;
if ($port < 1 || $port > 65535) {
	$port = 3306;
}
// Use $dbUser / $dbPass — not $username / $password — so callers (e.g. api/index.php login) are not overwritten.
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '';
$dbname = getenv('DB_NAME') ?: 'mimds';

try {
	$con = mysqli_connect($host, $dbUser, $dbPass, $dbname, $port);
} catch (mysqli_sql_exception $e) {
	error_log('mysqli_connect failed: ' . $e->getMessage());
	if (PHP_SAPI === 'cli') {
		fwrite(STDERR, 'Database connection failed: ' . $e->getMessage() . "\n");
		fwrite(STDERR, "Tried: host={$host} port={$port} user={$dbUser} database={$dbname}\n");
		fwrite(STDERR, "Fix: start MySQL (e.g. XAMPP → Start MySQL). Check DB_HOST and DB_PORT in .env.local.\n");
		exit(1);
	}
	http_response_code(503);
	exit('Database unavailable.');
}
if ($con === false) {
	$err = mysqli_connect_error();
	error_log('mysqli_connect failed: ' . $err);
	if (PHP_SAPI === 'cli') {
		fwrite(STDERR, 'Database connection failed: ' . $err . "\n");
		fwrite(STDERR, "Tried: host={$host} port={$port} user={$dbUser} database={$dbname}\n");
		fwrite(STDERR, "Fix: start MySQL (e.g. XAMPP → Start MySQL). Check DB_HOST and DB_PORT in .env.local.\n");
		exit(1);
	}
	http_response_code(503);
	exit('Database unavailable.');
}
mysqli_set_charset($con, 'utf8mb4');
