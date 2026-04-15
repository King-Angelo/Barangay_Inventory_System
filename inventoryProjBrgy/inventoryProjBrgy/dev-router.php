<?php

declare(strict_types=1);

/**
 * PHP built-in server router (Apache not required for quick API tests).
 *
 *   cd inventoryProjBrgy/inventoryProjBrgy
 *   php -S 127.0.0.1:8765 dev-router.php
 *
 * Then: GET http://127.0.0.1:8765/api/v1/auth/health
 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

if (str_starts_with($uri, '/api/')) {
	$_SERVER['SCRIPT_NAME'] = '/api/index.php';
	require __DIR__ . '/api/index.php';
	return true;
}

return false;
