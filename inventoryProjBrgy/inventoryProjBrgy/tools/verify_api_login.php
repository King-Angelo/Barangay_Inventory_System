<?php

declare(strict_types=1);

/**
 * CLI: same DB + AuthService as POST /api/v1/auth/login.
 * Usage (from inventoryProjBrgy/inventoryProjBrgy):
 *   php tools/verify_api_login.php
 *   php tools/verify_api_login.php admin "ChangeMe2026!"
 */
chdir(__DIR__ . '/..');
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../env_bootstrap.php';
inv_load_dotenv(__DIR__ . '/../.env.local');
inv_load_dotenv(__DIR__ . '/../.env');

$u = $argv[1] ?? 'admin';
$p = $argv[2] ?? 'ChangeMe2026!';

$host = getenv('DB_HOST') !== false && getenv('DB_HOST') !== '' ? (string) getenv('DB_HOST') : '127.0.0.1';
$dbPort = getenv('DB_PORT');
$port = ($dbPort !== false && $dbPort !== '') ? (int) $dbPort : 3306;
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '';
$dbName = getenv('DB_NAME') ?: 'mimds';

echo "Effective DB: {$host}:{$port} / {$dbName} (user: {$dbUser})\n";

require_once __DIR__ . '/../dbcon.php';

use App\Api\AuthService;

$row = AuthService::verifyPassword($con, $u, $p);
if ($row !== null) {
	echo "OK: login would succeed for role={$row['role']} id={$row['id']}\n";
	exit(0);
}

echo "FAIL: AuthService::verifyPassword returned null (same as API 401 invalid_credentials).\n";

$esc = mysqli_real_escape_string($con, $u);
$q = mysqli_query($con, "SELECT id, UserName, CHAR_LENGTH(password_hash) AS len, password_hash FROM users WHERE UserName='{$esc}' LIMIT 1");
$r = $q ? mysqli_fetch_assoc($q) : null;
if (!is_array($r)) {
	echo "No row for UserName='{$u}'.\n";
	exit(1);
}
$h = (string) ($r['password_hash'] ?? '');
echo "Row exists: id={$r['id']}, hash_len=" . strlen($h) . "\n";
if ($h !== '' && function_exists('password_verify')) {
	$direct = password_verify($p, $h);
	echo "password_verify(password, stored_hash): " . ($direct ? 'true' : 'false') . "\n";
	if (!$direct && strlen($h) === 60) {
		echo "Tip: run UPDATE from migrations/007_seeds.sql for this user, or set password_hash to the seed in DEV_REFERENCE.\n";
	}
}
exit(1);
