<?php

declare(strict_types=1);

namespace App\Api;

use mysqli;
use mysqli_result;
use mysqli_stmt;

/**
 * Credential check aligned with legacy Login.php (bcrypt password_hash or legacy PaSS).
 */
final class AuthService
{
	/**
	 * @return array{id:int,UserName:string,role:string}|null
	 */
	public static function verifyPassword(mysqli $con, string $username, string $password): ?array
	{
		$username = trim($username);
		if ($username === '' || $password === '') {
			return null;
		}

		$sql = 'SELECT `id`, `UserName`, `PaSS`, `role`, `password_hash` FROM `users` WHERE `UserName` = ? LIMIT 1';
		$stmt = mysqli_prepare($con, $sql);
		if ($stmt === false) {
			return null;
		}

		mysqli_stmt_bind_param($stmt, 's', $username);
		mysqli_stmt_execute($stmt);
		$result = mysqli_stmt_get_result($stmt);
		if (!($result instanceof mysqli_result)) {
			mysqli_stmt_close($stmt);
			return null;
		}

		$row = mysqli_fetch_assoc($result);
		mysqli_free_result($result);
		mysqli_stmt_close($stmt);

		if (!is_array($row) || !isset($row['id'], $row['UserName'])) {
			return null;
		}

		$bcryptOk = !empty($row['password_hash'])
			&& function_exists('password_verify')
			&& password_verify($password, (string) $row['password_hash']);

		$legacyOk = isset($row['PaSS'])
			&& (string) $row['PaSS'] !== ''
			&& (string) $row['PaSS'] === $password;

		if (!$bcryptOk && !$legacyOk) {
			return null;
		}

		$role = isset($row['role']) && (string) $row['role'] !== ''
			? (string) $row['role']
			: 'staff';

		return [
			'id' => (int) $row['id'],
			'UserName' => (string) $row['UserName'],
			'role' => $role,
		];
	}
}
