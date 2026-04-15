<?php

declare(strict_types=1);

namespace App\Api;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use mysqli;

final class AuthContextFactory
{
	/**
	 * @throws \RuntimeException if token invalid or user missing
	 */
	public static function fromBearer(mysqli $con, string $authorizationHeader): AuthContext
	{
		if (!preg_match('/^Bearer\s+(\S+)/i', $authorizationHeader, $m)) {
			throw new \RuntimeException('Missing Bearer token.');
		}
		$token = $m[1];
		try {
			$claims = JwtIssuer::decode($token);
		} catch (ExpiredException $e) {
			throw new \RuntimeException('Token expired.');
		} catch (SignatureInvalidException $e) {
			throw new \RuntimeException('Invalid token signature.');
		} catch (\Throwable $e) {
			throw new \RuntimeException('Invalid token.');
		}
		$sub = isset($claims['sub']) ? (string) $claims['sub'] : '';
		if ($sub === '' || !ctype_digit($sub)) {
			throw new \RuntimeException('Invalid token subject.');
		}
		$uid = (int) $sub;

		$sql = 'SELECT `id`, `UserName`, `role`, `barangay_id` FROM `users` WHERE `id` = ? LIMIT 1';
		$st = mysqli_prepare($con, $sql);
		if ($st === false) {
			throw new \RuntimeException('Database error.');
		}
		mysqli_stmt_bind_param($st, 'i', $uid);
		mysqli_stmt_execute($st);
		$res = mysqli_stmt_get_result($st);
		$row = $res ? mysqli_fetch_assoc($res) : null;
		mysqli_stmt_close($st);
		if (!is_array($row)) {
			throw new \RuntimeException('User not found.');
		}

		$role = isset($row['role']) ? (string) $row['role'] : 'staff';
		$bid = isset($row['barangay_id']) && $row['barangay_id'] !== null
			? (int) $row['barangay_id']
			: null;

		return new AuthContext(
			(int) $row['id'],
			$role,
			(string) $row['UserName'],
			$bid,
		);
	}
}
