<?php

declare(strict_types=1);

/**
 * API bootstrap: working directory = app root, Composer autoload, dotenv (same as dbcon).
 */
chdir(dirname(__DIR__));

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_readable($autoload)) {
	header('Content-Type: application/json; charset=utf-8', true, 503);
	echo json_encode([
		'error' => 'service_unavailable',
		'message' => 'Run `composer install` in inventoryProjBrgy/inventoryProjBrgy.',
	], JSON_UNESCAPED_SLASHES);
	exit;
}

require_once $autoload;
require_once dirname(__DIR__) . '/env_bootstrap.php';
inv_load_dotenv(dirname(__DIR__) . '/.env.local');
inv_load_dotenv(dirname(__DIR__) . '/.env');
