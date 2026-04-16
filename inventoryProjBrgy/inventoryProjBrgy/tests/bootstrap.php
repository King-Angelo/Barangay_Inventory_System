<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

if (getenv('JWT_SECRET') === false || getenv('JWT_SECRET') === '') {
	putenv('JWT_SECRET=' . str_repeat('a', 32));
}
