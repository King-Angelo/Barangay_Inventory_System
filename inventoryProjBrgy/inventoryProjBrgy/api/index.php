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

use App\Api\AuthContextFactory;
use App\Api\AuthService;
use App\Api\JwtIssuer;
use App\Api\PaymentApiService;
use App\Api\PermitApiService;
use App\Api\ResidentApiService;

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'OPTIONS') {
	header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
	header('Access-Control-Allow-Headers: Content-Type, Authorization');
	http_response_code(204);
	exit;
}

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

function read_json_body(): array
{
	$raw = file_get_contents('php://input') ?: '';
	$data = json_decode($raw, true);
	return is_array($data) ? $data : [];
}

/** Dotenv + getenv; Windows may only populate $_ENV for custom keys. */
function auth_debug_login_enabled(): bool
{
	$v = $_ENV['AUTH_DEBUG_LOGIN'] ?? $_SERVER['AUTH_DEBUG_LOGIN'] ?? getenv('AUTH_DEBUG_LOGIN');
	if ($v === false || $v === null) {
		return false;
	}
	return trim((string) $v) === '1';
}

// --- Public: POST /v1/auth/login
if ($method === 'POST' && $segments === ['v1', 'auth', 'login']) {
	$data = read_json_body();
	$username = isset($data['username']) ? (string) $data['username'] : '';
	$password = isset($data['password']) ? (string) $data['password'] : '';
	if (trim($username) === '') {
		json_error(400, 'validation_error', 'Field "username" is required.');
	}
	if ($password === '') {
		json_error(400, 'validation_error', 'Field "password" is required.');
	}

	require_once dirname(__DIR__) . '/dbcon.php';

	AuthService::resetLoginDebugState();

	try {
		$user = AuthService::verifyPassword($con, $username, $password);
	} catch (\Throwable $e) {
		error_log('api login: ' . $e->getMessage());
		json_error(500, 'server_error', 'Could not complete login.');
	}

	if ($user === null) {
		if (auth_debug_login_enabled()) {
			http_response_code(401);
			echo json_encode([
				'error' => 'invalid_credentials',
				'message' => 'Invalid username or password.',
				'debug' => [
					'reason' => AuthService::getLastLoginFailureReason(),
					'meta' => AuthService::getLastLoginFailureMeta(),
					'api_lens' => [
						'username_len' => strlen($username),
						'password_len' => strlen($password),
					],
				],
			], JSON_UNESCAPED_SLASHES);
			exit;
		}
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

// GET /v1/auth/health
if ($method === 'GET' && $segments === ['v1', 'auth', 'health']) {
	echo json_encode([
		'status' => 'ok',
		'service' => 'barangay-inventory-api',
	], JSON_UNESCAPED_SLASHES);
	exit;
}

// --- Protected routes (JWT)
require_once dirname(__DIR__) . '/dbcon.php';

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
try {
	$ctx = AuthContextFactory::fromBearer($con, $authHeader);
} catch (\RuntimeException $e) {
	json_error(401, 'unauthorized', $e->getMessage());
} catch (\Throwable $e) {
	error_log('jwt: ' . $e->getMessage());
	json_error(401, 'unauthorized', 'Invalid or expired token.');
}

try {
	// GET /v1/residents
	if ($method === 'GET' && $segments === ['v1', 'residents']) {
		$bid = isset($_GET['barangay_id']) ? (int) $_GET['barangay_id'] : null;
		$q = isset($_GET['q']) ? (string) $_GET['q'] : null;
		$includeArchived = isset($_GET['include_archived']) && $_GET['include_archived'] === '1';
		$listBid = ResidentApiService::resolveListBarangayId($ctx, $bid > 0 ? $bid : null);
		$rows = ResidentApiService::list($con, $ctx, $listBid, $q, $includeArchived);
		echo json_encode(['data' => $rows], JSON_UNESCAPED_SLASHES);
		exit;
	}

	// GET /v1/residents/{id}
	if ($method === 'GET' && count($segments) === 3 && $segments[0] === 'v1' && $segments[1] === 'residents' && ctype_digit($segments[2])) {
		$row = ResidentApiService::getById($con, $ctx, (int) $segments[2]);
		if ($row === null) {
			json_error(404, 'not_found', 'Resident not found.');
		}
		echo json_encode(['data' => $row], JSON_UNESCAPED_SLASHES);
		exit;
	}

	// POST /v1/residents
	if ($method === 'POST' && $segments === ['v1', 'residents']) {
		$data = read_json_body();
		$newId = ResidentApiService::create($con, $ctx, $data);
		$row = ResidentApiService::getById($con, $ctx, $newId);
		http_response_code(201);
		echo json_encode(['data' => $row], JSON_UNESCAPED_SLASHES);
		exit;
	}

	// PATCH /v1/residents/{id}
	if ($method === 'PATCH' && count($segments) === 3 && $segments[0] === 'v1' && $segments[1] === 'residents' && ctype_digit($segments[2])) {
		$data = read_json_body();
		$ok = ResidentApiService::patch($con, $ctx, (int) $segments[2], $data);
		if (!$ok) {
			json_error(404, 'not_found', 'Resident not found.');
		}
		$row = ResidentApiService::getById($con, $ctx, (int) $segments[2]);
		echo json_encode(['data' => $row], JSON_UNESCAPED_SLASHES);
		exit;
	}

	// PUT /v1/residents/{id}
	if ($method === 'PUT' && count($segments) === 3 && $segments[0] === 'v1' && $segments[1] === 'residents' && ctype_digit($segments[2])) {
		$data = read_json_body();
		$ok = ResidentApiService::put($con, $ctx, (int) $segments[2], $data);
		if (!$ok) {
			json_error(404, 'not_found', 'Resident not found.');
		}
		$row = ResidentApiService::getById($con, $ctx, (int) $segments[2]);
		echo json_encode(['data' => $row], JSON_UNESCAPED_SLASHES);
		exit;
	}

	// GET /v1/permits
	if ($method === 'GET' && $segments === ['v1', 'permits']) {
		$rid = isset($_GET['resident_id']) ? (int) $_GET['resident_id'] : null;
		if ($rid < 1) {
			$rid = null;
		}
		$rows = PermitApiService::list($con, $ctx, $rid);
		echo json_encode(['data' => $rows], JSON_UNESCAPED_SLASHES);
		exit;
	}

	// GET /v1/permits/{id}
	if ($method === 'GET' && count($segments) === 3 && $segments[0] === 'v1' && $segments[1] === 'permits' && ctype_digit($segments[2])) {
		$row = PermitApiService::getById($con, $ctx, (int) $segments[2]);
		if ($row === null) {
			json_error(404, 'not_found', 'Permit not found.');
		}
		echo json_encode(['data' => $row], JSON_UNESCAPED_SLASHES);
		exit;
	}

	// POST /v1/permits
	if ($method === 'POST' && $segments === ['v1', 'permits']) {
		$data = read_json_body();
		$resId = isset($data['resident_id']) ? (int) $data['resident_id'] : 0;
		$ptId = isset($data['permit_type_id']) ? (int) $data['permit_type_id'] : 0;
		if ($resId < 1 || $ptId < 1) {
			json_error(400, 'validation_error', 'resident_id and permit_type_id are required.');
		}
		$newId = PermitApiService::create($con, $ctx, $resId, $ptId);
		$row = PermitApiService::getById($con, $ctx, $newId);
		http_response_code(201);
		echo json_encode(['data' => $row], JSON_UNESCAPED_SLASHES);
		exit;
	}

	// PATCH /v1/permits/{id}
	if ($method === 'PATCH' && count($segments) === 3 && $segments[0] === 'v1' && $segments[1] === 'permits' && ctype_digit($segments[2])) {
		$data = read_json_body();
		$out = PermitApiService::patch($con, $ctx, (int) $segments[2], $data);
		echo json_encode(['data' => $out], JSON_UNESCAPED_SLASHES);
		exit;
	}

	// DELETE /v1/permits/{id} — draft only
	if ($method === 'DELETE' && count($segments) === 3 && $segments[0] === 'v1' && $segments[1] === 'permits' && ctype_digit($segments[2])) {
		$deleted = PermitApiService::deleteDraft($con, $ctx, (int) $segments[2]);
		if (!$deleted) {
			json_error(404, 'not_found', 'Permit could not be deleted.');
		}
		http_response_code(204);
		exit;
	}

	// POST /v1/payments — mock provider (permit must be approved or ready_for_payment)
	if ($method === 'POST' && $segments === ['v1', 'payments']) {
		$data = read_json_body();
		$out = PaymentApiService::createMockPayment($con, $ctx, $data);
		http_response_code(201);
		echo json_encode(['data' => $out], JSON_UNESCAPED_SLASHES);
		exit;
	}
} catch (\InvalidArgumentException $e) {
	$msg = $e->getMessage();
	$lower = strtolower($msg);
	if (str_contains($lower, 'forbidden') || str_contains($lower, 'only admins')) {
		json_error(403, 'forbidden', $msg);
	}
	json_error(400, 'bad_request', $msg);
} catch (\Throwable $e) {
	error_log('api: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
	json_error(500, 'server_error', 'Request failed.');
}

http_response_code(404);
echo json_encode([
	'error' => 'not_found',
	'message' => 'No route for ' . $method . ' /' . $path,
], JSON_UNESCAPED_SLASHES);
