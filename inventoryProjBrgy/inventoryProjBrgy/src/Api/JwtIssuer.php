<?php

declare(strict_types=1);

namespace App\Api;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class JwtIssuer
{
	public static function issue(array $userRow, int $ttlSeconds): string
	{
		$secret = self::requireSecret();
		$iss = getenv('JWT_ISS') !== false && (string) getenv('JWT_ISS') !== ''
			? (string) getenv('JWT_ISS')
			: 'barangay-inventory';

		$now = time();
		$payload = [
			'iss' => $iss,
			'iat' => $now,
			'exp' => $now + $ttlSeconds,
			'sub' => (string) $userRow['id'],
			'role' => $userRow['role'],
			'username' => $userRow['UserName'],
		];

		return JWT::encode($payload, $secret, 'HS256');
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function decode(string $jwt): array
	{
		$secret = self::requireSecret();
		$decoded = JWT::decode($jwt, new Key($secret, 'HS256'));
		return (array) $decoded;
	}

	private static function requireSecret(): string
	{
		$raw = getenv('JWT_SECRET');
		$secret = $raw !== false ? (string) $raw : '';
		if ($secret === '') {
			throw new \RuntimeException('JWT_SECRET is not set. Add it to .env.local (see .env.example).');
		}
		if (strlen($secret) < 32) {
			throw new \RuntimeException('JWT_SECRET must be at least 32 characters for HS256.');
		}
		return $secret;
	}
}
