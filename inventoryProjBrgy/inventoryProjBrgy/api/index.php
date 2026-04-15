<?php

declare(strict_types=1);

// PHP built-in server + dev-router.php: normalize SCRIPT_NAME for path routing.
if (PHP_SAPI === 'cli-server') {
	$u = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
	if (str_starts_with($u, '/api/')) {
		$_SERVER['SCRIPT_NAME'] = '/api/index.php';
	}
}

require_once __DIR__ . '/bootstrap.php';

use App\Api\AuthService;
use App\Api\JwtIssuer;

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'OPTIONS') {
	header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
	header('Access-Control-Allow-Headers: Content-Type, Authorization');
	http_response_code(204);
	exit;
}

/**
 * Strip script directory from REQUEST_URI so routing works under any base path.
 */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
if ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($path, $scriptDir)) {
	$path = substr($path, strlen($scriptDir));
}
$path = trim((string) $path, '/');
$segments = $path === '' ? [] : explode('/', $path);

function json_error(int $code, string $error, string $message): void
{
	http_response_code($code);
	echo json_encode(['error' => $error, 'message' => $message], JSON_UNESCAPED_SLASHES);
	exit;
}

// POST /v1/auth/login
if ($method === 'POST' && $segments === ['v1', 'auth', 'login']) {
	$raw = file_get_contents('php://input') ?: '';
	$data = json_decode($raw, true);
	if (!is_array($data)) {
		json_error(400, 'invalid_json', 'Request body must be JSON.');
	}

	$username = isset($data['username']) ? (string) $data['username'] : '';
	$password = isset($data['password']) ? (string) $data['password'] : '';
	if (trim($username) === '') {
		json_error(400, 'validation_error', 'Field "username" is required.');
	}
	if ($password === '') {
		json_error(400, 'validation_error', 'Field "password" is required.');
	}

	require_once dirname(__DIR__) . '/dbcon.php';

	try {
		$user = AuthService::verifyPassword($con, $username, $password);
	} catch (\Throwable $e) {
		error_log('api login: ' . $e->getMessage());
		json_error(500, 'server_error', 'Could not complete login.');
	}

	if ($user === null) {
		json_error(401, 'invalid_credentials', 'Invalid username or password.');
	}

	$ttl = 3600;
	$ttlEnv = getenv('JWT_TTL');
	if ($ttlEnv !== false && $ttlEnv !== '') {
		$t = (int) $ttlEnv;
		if ($t > 60 && $t <= 86400) {
			$ttl = $t;
		}
	}

	try {
		$token = JwtIssuer::issue($user, $ttl);
	} catch (\RuntimeException $e) {
		json_error(500, 'misconfigured', $e->getMessage());
	}

	echo json_encode([
		'access_token' => $token,
		'token_type' => 'Bearer',
		'expires_in' => $ttl,
		'user' => [
			'id' => $user['id'],
			'username' => $user['UserName'],
			'role' => $user['role'],
		],
	], JSON_UNESCAPED_SLASHES);
	exit;
}

// GET /v1/auth/health — no DB; confirms API reachable
if ($method === 'GET' && $segments === ['v1', 'auth', 'health']) {
	echo json_encode([
		'status' => 'ok',
		'service' => 'barangay-inventory-api',
	], JSON_UNESCAPED_SLASHES);
	exit;
}

http_response_code(404);
echo json_encode([
	'error' => 'not_found',
	'message' => 'No route for ' . $method . ' /' . $path,
], JSON_UNESCAPED_SLASHES);
