<?php

declare(strict_types=1);

/**
 * Outbox worker: send pending integration_events (e.g. permit approved/rejected) via SMTP.
 *
 * Run manually (from app root):
 *
 *   cd inventoryProjBrgy/inventoryProjBrgy
 *   php worker/send_outbox.php
 *
 * Requires DB (migration 005), SMTP_* and MAIL_FROM in .env.local — see migrations/PRODUCTION_SETUP.md.
 */

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "This script must be run from the command line.\n");
	exit(1);
}

$root = dirname(__DIR__);
chdir($root);

require_once $root . '/vendor/autoload.php';
require_once $root . '/env_bootstrap.php';
inv_load_dotenv($root . '/.env.local');
inv_load_dotenv($root . '/.env');
require_once $root . '/dbcon.php';

use App\Worker\OutboxProcessor;

exit(OutboxProcessor::run($con));
