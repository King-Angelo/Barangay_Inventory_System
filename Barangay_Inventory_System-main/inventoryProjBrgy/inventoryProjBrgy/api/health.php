<?php
declare(strict_types=1);

/**
 * Health check endpoint for the Barangay Inventory API.
 * Verifies database connectivity, environment config, and basic service health.
 */

require_once __DIR__ . '/bootstrap.php';

$health = [
    'status' => 'ok',
    'timestamp' => date('c'),
    'checks' => [],
];

// Check database connectivity
$dbCheck = ['name' => 'database', 'status' => 'down'];
try {
    global $con;
    if ($con && !mysqli_connect_error()) {
        $result = mysqli_query($con, 'SELECT 1');
        if ($result) {
            $dbCheck['status'] = 'up';
        }
    }
} catch (Exception $e) {
    $dbCheck['error'] = $e->getMessage();
}
$health['checks'][] = $dbCheck;

// Check environment variables
$envCheck = ['name' => 'environment', 'status' => 'up'];
$required = ['DB_HOST', 'DB_USER', 'DB_NAME', 'JWT_SECRET'];
$missing = [];
foreach ($required as $key) {
    if (!getenv($key)) {
        $missing[] = $key;
    }
}
if (!empty($missing)) {
    $envCheck['status'] = 'degraded';
    $envCheck['missing_vars'] = $missing;
}
$health['checks'][] = $envCheck;

// Check JWT configuration
$jwtCheck = ['name' => 'jwt', 'status' => 'up'];
$jwtSecret = getenv('JWT_SECRET');
if (empty($jwtSecret) || $jwtSecret === 'change-me-please') {
    $jwtCheck['status'] = 'warning';
    $jwtCheck['message'] = 'JWT_SECRET is not set or uses default value';
}
$health['checks'][] = $jwtCheck;

// Overall status
$failedChecks = array_filter($health['checks'], fn($c) => $c['status'] === 'down');
$degradedChecks = array_filter($health['checks'], fn($c) => $c['status'] === 'degraded');

if (!empty($failedChecks)) {
    $health['status'] = 'error';
    http_response_code(503);
} elseif (!empty($degradedChecks)) {
    $health['status'] = 'degraded';
    http_response_code(200);
} else {
    $health['status'] = 'ok';
    http_response_code(200);
}

header('Content-Type: application/json');
echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
