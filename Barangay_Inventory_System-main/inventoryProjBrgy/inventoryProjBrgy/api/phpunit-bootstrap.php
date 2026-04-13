<?php
declare(strict_types=1);

/**
 * PHPUnit Bootstrap for Barangay Inventory API tests.
 * Initializes test environment and provides common test utilities.
 */

// Define test environment
define('RUNNING_TESTS', true);

// Auto-loader for test classes
spl_autoload_register(static function ($class): void {
    $prefix = 'BarangayInventory\\Tests\\';
    if (strpos($class, $prefix) === 0) {
        $relative_class = substr($class, strlen($prefix));
        $file = __DIR__ . '/tests/' . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});

// Load bootstrap
require_once __DIR__ . '/bootstrap.php';

/**
 * Test helper: Create a JWT token for authenticated requests.
 */
function create_test_token(string $username = 'Cj233', string $role = 'staff'): string
{
    return api_jwt_encode([
        'sub' => $username,
        'role' => $role,
    ]);
}

/**
 * Test helper: Get database connection for test setup/teardown.
 */
function get_test_connection(): mysqli
{
    global $con;
    return $con;
}

/**
 * Test helper: Clear test data.
 */
function cleanup_test_data(): void
{
    $con = get_test_connection();
    mysqli_query($con, 'DELETE FROM integration_events WHERE id > 0');
    mysqli_query($con, 'DELETE FROM permits WHERE id > 0');
    mysqli_query($con, 'DELETE FROM residents WHERE id > 0');
}
