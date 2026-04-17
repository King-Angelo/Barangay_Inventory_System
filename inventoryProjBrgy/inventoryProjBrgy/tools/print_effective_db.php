<?php

declare(strict_types=1);

/**
 * Shows which DB settings PHP will use (same rules as dbcon.php).
 * Run: php tools/print_effective_db.php
 *
 * If this differs between "Cursor terminal" and "cmd where you run php -S",
 * set or clear DB_* in the environment so both match .env.local.
 */
chdir(__DIR__ . '/..');
require_once __DIR__ . '/../env_bootstrap.php';
inv_load_dotenv(__DIR__ . '/../.env.local');
inv_load_dotenv(__DIR__ . '/../.env');

$host = getenv('DB_HOST') !== false && getenv('DB_HOST') !== '' ? (string) getenv('DB_HOST') : '127.0.0.1';
$dbPort = getenv('DB_PORT');
$port = ($dbPort !== false && $dbPort !== '') ? (int) $dbPort : 3306;
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '';
$dbname = getenv('DB_NAME') ?: 'mimds';

echo "Effective mysqli target (as dbcon.php):\n";
echo "  DB_HOST={$host}\n";
echo "  DB_PORT={$port}\n";
echo "  DB_USER={$user}\n";
echo "  DB_PASS=" . ($pass === '' ? '(empty)' : '(set, hidden)') . "\n";
echo "  DB_NAME={$dbname}\n";
