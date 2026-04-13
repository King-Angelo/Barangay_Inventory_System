<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = api_get_route_path();

if ($method === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, Content-Type');
    exit;
}

if ($path === '/health' && $method === 'GET') {
    require_once __DIR__ . '/health.php';
    exit;
}

if ($method === 'POST' && $path === '/auth/login') {
    handle_auth_login();
}

if ($path === '/v1/users' && $method === 'GET') {
    handle_get_users();
}

if ($path === '/v1/users' && $method === 'POST') {
    handle_create_user();
}

if (preg_match('#^/v1/users/([^/]+)$#', $path, $matches)) {
    $username = urldecode($matches[1]);
    if ($method === 'GET') {
        handle_get_user($username);
    }
    if ($method === 'PUT') {
        handle_update_user($username);
    }
    if ($method === 'DELETE') {
        handle_delete_user($username);
    }
}

if ($path === '/v1/residents' && $method === 'GET') {
    handle_get_residents();
}

if ($path === '/v1/residents' && $method === 'POST') {
    handle_create_resident();
}

if (preg_match('#^/v1/residents/(\d+)$#', $path, $matches)) {
    $residentId = (int) $matches[1];
    if ($method === 'GET') {
        handle_get_resident($residentId);
    }
    if ($method === 'PUT') {
        handle_update_resident($residentId);
    }
    if ($method === 'PATCH') {
        handle_patch_resident($residentId);
    }
}

if ($path === '/v1/permits' && $method === 'GET') {
    handle_get_permits();
}
if ($path === '/v1/permits' && $method === 'POST') {
    handle_create_permit();
}
if (preg_match('#^/v1/permits/(\d+)$#', $path, $matches)) {
    $permitId = (int) $matches[1];
    if ($method === 'GET') {
        handle_get_permit($permitId);
    }
    if ($method === 'PUT') {
        handle_update_permit($permitId);
    }
    if ($method === 'PATCH') {
        handle_patch_permit($permitId);
    }
    if ($method === 'DELETE') {
        handle_delete_permit($permitId);
    }
}

if ($path === '/v1/integration-events' && $method === 'GET') {
    handle_get_integration_events();
}
if ($path === '/v1/integration-events/process' && $method === 'POST') {
    handle_process_integration_events();
}

api_error('Not found', 404);

function handle_auth_login(): void
{
    $payload = get_request_data();
    $username = trim((string) ($payload['username'] ?? $payload['user'] ?? ''));
    $password = (string) ($payload['password'] ?? $payload['pass'] ?? '');

    if ($username === '' || $password === '') {
        api_error('Username and password are required.', 400);
    }

    global $con;
    $stmt = mysqli_prepare($con, 'SELECT UserName, PaSS FROM users WHERE UserName = ? LIMIT 1');
    if ($stmt === false) {
        api_error('Unable to prepare login query.', 500);
    }
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!is_array($user) || !isset($user['PaSS'])) {
        api_error('Invalid username or password.', 401);
    }

    if (!verify_password($password, (string) $user['PaSS'])) {
        api_error('Invalid username or password.', 401);
    }

    $claims = [
        'sub' => $user['UserName'],
        'role' => api_role_for_username((string) $user['UserName']),
    ];

    api_json_response([
        'token' => api_jwt_encode($claims),
        'expires_in' => api_jwt_ttl(),
        'role' => $claims['role'],
    ]);
}

function handle_get_users(): void
{
    $claims = api_require_jwt();
    api_require_role($claims, ['staff', 'admin']);

    global $con;
    $query = 'SELECT UserName FROM users ORDER BY UserName ASC';
    $result = mysqli_query($con, $query);
    if ($result === false) {
        api_error('Unable to retrieve users.', 500);
    }

    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = ['username' => $row['UserName']];
    }

    api_json_response(['users' => $users]);
}

function handle_get_user(string $username): void
{
    $claims = api_require_jwt();
    api_require_role($claims, ['staff', 'admin']);

    global $con;
    $stmt = mysqli_prepare($con, 'SELECT UserName FROM users WHERE UserName = ? LIMIT 1');
    if ($stmt === false) {
        api_error('Unable to prepare query.', 500);
    }
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!is_array($user)) {
        api_error('User not found.', 404);
    }

    api_json_response(['user' => ['username' => $user['UserName']]]);
}

function handle_create_user(): void
{
    $claims = api_require_jwt();
    api_require_role($claims, ['admin']);

    $payload = get_request_data();
    $username = trim((string) ($payload['username'] ?? $payload['user'] ?? ''));
    $password = (string) ($payload['password'] ?? $payload['pass'] ?? '');

    if ($username === '' || $password === '') {
        api_error('Username and password are required.', 400);
    }

    global $con;
    $stmt = mysqli_prepare($con, 'SELECT 1 FROM users WHERE UserName = ? LIMIT 1');
    if ($stmt === false) {
        api_error('Unable to prepare query.', 500);
    }
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_fetch_assoc($result)) {
        mysqli_stmt_close($stmt);
        api_error('User already exists.', 409);
    }
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($con, 'INSERT INTO users (UserName, PaSS) VALUES (?, ?)');
    if ($stmt === false) {
        api_error('Unable to prepare insert.', 500);
    }
    mysqli_stmt_bind_param($stmt, 'ss', $username, $password);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        api_error('Unable to create user.', 500);
    }
    mysqli_stmt_close($stmt);

    http_response_code(201);
    api_json_response(['user' => ['username' => $username]], 201);
}

function handle_update_user(string $username): void
{
    $claims = api_require_jwt();
    api_require_role($claims, ['admin']);

    $payload = get_request_data();
    $password = (string) ($payload['password'] ?? $payload['pass'] ?? '');

    if ($password === '') {
        api_error('Password is required to update the user.', 400);
    }

    global $con;
    $stmt = mysqli_prepare($con, 'UPDATE users SET PaSS = ? WHERE UserName = ?');
    if ($stmt === false) {
        api_error('Unable to prepare update.', 500);
    }
    mysqli_stmt_bind_param($stmt, 'ss', $password, $username);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        api_error('Unable to update user.', 500);
    }
    if (mysqli_stmt_affected_rows($stmt) < 1) {
        mysqli_stmt_close($stmt);
        api_error('User not found.', 404);
    }
    mysqli_stmt_close($stmt);

    api_json_response(['user' => ['username' => $username]]);
}

function handle_delete_user(string $username): void
{
    $claims = api_require_jwt();
    api_require_role($claims, ['admin']);

    global $con;
    $stmt = mysqli_prepare($con, 'DELETE FROM users WHERE UserName = ?');
    if ($stmt === false) {
        api_error('Unable to prepare delete.', 500);
    }
    mysqli_stmt_bind_param($stmt, 's', $username);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        api_error('Unable to delete user.', 500);
    }
    if (mysqli_stmt_affected_rows($stmt) < 1) {
        mysqli_stmt_close($stmt);
        api_error('User not found.', 404);
    }
    mysqli_stmt_close($stmt);

    api_json_response(['message' => 'User deleted.']);
}
