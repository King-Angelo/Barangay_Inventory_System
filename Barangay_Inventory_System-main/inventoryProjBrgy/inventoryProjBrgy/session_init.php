<?php
declare(strict_types=1);

/**
 * Session cookies that work behind a reverse proxy (TLS terminated at proxy; PHP often sees HTTP).
 */
function inv_session_start(): void
{
	if (session_status() === PHP_SESSION_ACTIVE) {
		return;
	}
	$forwarded = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
	$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
	$secure = $https || strtolower((string) $forwarded) === 'https';

	// Omit 'domain' so PHP uses the current host (empty string caused issues on some PHP builds).
	session_set_cookie_params([
		'lifetime' => 0,
		'path' => '/',
		'secure' => $secure,
		'httponly' => true,
		'samesite' => 'Lax',
	]);

	session_start();
}
