<?php
declare(strict_types=1);

chdir(__DIR__ . '/..');
require_once __DIR__ . '/../env_bootstrap.php';
inv_load_dotenv(__DIR__ . '/../.env.local');
inv_load_dotenv(__DIR__ . '/../.env');

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Composer dependencies are missing. Run "composer install" in the project root.');
}

require_once $autoload;
require_once __DIR__ . '/../dbcon.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/residents.php';
require_once __DIR__ . '/permits.php';
require_once __DIR__ . '/events.php';
