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
	private static ?string $lastLoginFailureReason = null;

	/** @var array<string, int|string>|null */
	private static ?array $lastLoginFailureMeta = null;

	public static function getLastLoginFailureReason(): ?string
	{
		return self::$lastLoginFailureReason;
	}

	/** @return array<string, int|string>|null */
	public static function getLastLoginFailureMeta(): ?array
	{
		return self::$lastLoginFailureMeta;
	}

	/**
	 * Clear per-request failure state. Call before verifyPassword in HTTP handlers — the PHP
	 * built-in server may reuse the same process; statics can otherwise leak between requests.
	 */
	public static function resetLoginDebugState(): void
	{
		self::$lastLoginFailureReason = null;
		self::$lastLoginFailureMeta = null;
	}

	private static function failLogin(string $reason, ?array $meta = null): null
	{
		self::$lastLoginFailureReason = $reason;
		self::$lastLoginFailureMeta = $meta;
		return null;
	}

	/**
	 * @return array{id:int,UserName:string,role:string}|null
	 */
	public static function verifyPassword(mysqli $con, string $username, string $password): ?array
	{
		self::$lastLoginFailureReason = null;
		self::$lastLoginFailureMeta = null;

		$username = trim($username);
		if ($username === '' || $password === '') {
			return self::failLogin('empty_input');
		}

		$sql = 'SELECT `id`, `UserName`, `PaSS`, `role`, `password_hash` FROM `users` WHERE `UserName` = ? LIMIT 1';
		$stmt = mysqli_prepare($con, $sql);
		if ($stmt === false) {
			return self::failLogin('stmt_prepare_failed', ['mysqli_error' => mysqli_error($con)]);
		}

		mysqli_stmt_bind_param($stmt, 's', $username);
		if (!mysqli_stmt_execute($stmt)) {
			$err = mysqli_stmt_error($stmt);
			error_log('AUTH_DEBUG_LOGIN: mysqli_stmt_execute failed: ' . $err);
			mysqli_stmt_close($stmt);
			return self::failLogin('stmt_execute_failed', ['stmt_error' => $err]);
		}

		$result = mysqli_stmt_get_result($stmt);
		if ($result instanceof mysqli_result) {
			$row = mysqli_fetch_assoc($result);
			mysqli_free_result($result);
			mysqli_stmt_close($stmt);
		} else {
			if (getenv('AUTH_DEBUG_LOGIN') === '1') {
				error_log(
					'AUTH_DEBUG_LOGIN: mysqli_stmt_get_result failed (using bind_result fallback): '
					. mysqli_stmt_error($stmt)
				);
			}
			mysqli_stmt_store_result($stmt);
			if (mysqli_stmt_num_rows($stmt) < 1) {
				mysqli_stmt_close($stmt);
				error_log('AUTH_DEBUG_LOGIN: no user row for UserName=' . $username);
				return self::failLogin('no_row');
			}
			$id = 0;
			$dbUserName = '';
			$dbPaSS = '';
			$dbRole = '';
			$dbHash = '';
			mysqli_stmt_bind_result($stmt, $id, $dbUserName, $dbPaSS, $dbRole, $dbHash);
			if (!mysqli_stmt_fetch($stmt)) {
				mysqli_stmt_close($stmt);
				return self::failLogin('no_row');
			}
			mysqli_stmt_close($stmt);
			$row = [
				'id' => $id,
				'UserName' => $dbUserName,
				'PaSS' => $dbPaSS,
				'role' => $dbRole,
				'password_hash' => $dbHash,
			];
		}

		if (!is_array($row) || !isset($row['id'], $row['UserName'])) {
			error_log('AUTH_DEBUG_LOGIN: no user row for UserName=' . $username);
			return self::failLogin('no_row');
		}

		$bcryptOk = !empty($row['password_hash'])
			&& function_exists('password_verify')
			&& password_verify($password, (string) $row['password_hash']);

		$legacyOk = isset($row['PaSS'])
			&& (string) $row['PaSS'] !== ''
			&& (string) $row['PaSS'] === $password;

		if (!$bcryptOk && !$legacyOk) {
			$h = isset($row['password_hash']) ? (string) $row['password_hash'] : '';
			$hashLen = strlen($h);
			$pv = $hashLen > 0 && function_exists('password_verify')
				? (password_verify($password, $h) ? 'true' : 'false')
				: 'n/a';
			error_log(sprintf(
				'AUTH_DEBUG_LOGIN: fail user=%s pwd_len=%d hash_len=%d password_verify=%s',
				$username,
				strlen($password),
				$hashLen,
				$pv
			));
			return self::failLogin('bad_password', [
				'pwd_len' => strlen($password),
				'hash_len' => $hashLen,
				'password_verify' => $pv,
			]);
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
