<?php
declare(strict_types=1);

/**
 * Load KEY=VALUE lines from a dotenv file without overriding existing environment variables
 * (process / Docker / system env take precedence).
 */
function inv_load_dotenv(string $path): void
{
	if (!is_readable($path)) {
		return;
	}
	$raw = file_get_contents($path);
	if ($raw === false || $raw === '') {
		return;
	}
	if (str_starts_with($raw, "\xEF\xBB\xBF")) {
		$raw = substr($raw, 3);
	}
	$lines = preg_split("/\r\n|\n|\r/", $raw) ?: [];
	foreach ($lines as $line) {
		$line = trim($line);
		if ($line === '' || str_starts_with($line, '#')) {
			continue;
		}
		$eq = strpos($line, '=');
		if ($eq === false) {
			continue;
		}
		$name = trim(substr($line, 0, $eq));
		$value = trim(substr($line, $eq + 1));
		if ($name === '') {
			continue;
		}
		if (str_starts_with($value, '"') && str_ends_with($value, '"') && strlen($value) >= 2) {
			$value = stripcslashes(substr($value, 1, -1));
		} elseif (str_starts_with($value, "'") && str_ends_with($value, "'") && strlen($value) >= 2) {
			$value = substr($value, 1, -1);
		}
		if (getenv($name) !== false) {
			continue;
		}
		if (array_key_exists($name, $_ENV)) {
			continue;
		}
		putenv("{$name}={$value}");
		$_ENV[$name] = $value;
		$_SERVER[$name] = $value;
	}
}
