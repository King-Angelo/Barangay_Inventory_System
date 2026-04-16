<?php

declare(strict_types=1);

namespace App\Api;

use mysqli;

/**
 * Residents API — prepared statements, RBAC per RESIDENT_ROADMAP.md.
 */
final class ResidentApiService
{
	/**
	 * Resolve which barangay_id to list. Staff scoped to their users.barangay_id when set.
	 */
	public static function resolveListBarangayId(AuthContext $ctx, ?int $requested): int
	{
		if ($ctx->isAdmin()) {
			return $requested !== null && $requested > 0 ? $requested : 1;
		}
		if ($ctx->barangayId !== null) {
			if ($requested !== null && $requested > 0 && $requested !== $ctx->barangayId) {
				throw new \InvalidArgumentException('You may only list residents in your assigned barangay.');
			}
			return $ctx->barangayId;
		}
		if ($requested === null || $requested < 1) {
			throw new \InvalidArgumentException('Query parameter barangay_id is required.');
		}
		return $requested;
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public static function list(
		mysqli $con,
		AuthContext $ctx,
		int $barangayId,
		?string $q,
		bool $includeArchived,
	): array {
		if (!$ctx->isAdmin() && $includeArchived) {
			throw new \InvalidArgumentException('Only admins can list archived residents.');
		}

		$statusClause = $includeArchived ? '1=1' : "r.`status` = 'active'";
		$params = [$barangayId];
		$types = 'i';

		$searchClause = '';
		if ($q !== null && trim($q) !== '') {
			$like = '%' . trim($q) . '%';
			$searchClause = ' AND (r.`last_name` LIKE ? OR r.`first_name` LIKE ? OR r.`email` LIKE ?)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$types .= 'sss';
		}

		$sql = "SELECT r.*, b.`brgy` AS `barangay_name`
			FROM `residents` r
			LEFT JOIN `barangays` b ON b.`n` = r.`barangay_id`
			WHERE r.`barangay_id` = ? AND ($statusClause)$searchClause
			ORDER BY r.`last_name`, r.`first_name`";

		$st = mysqli_prepare($con, $sql);
		if ($st === false) {
			throw new \RuntimeException(mysqli_error($con));
		}
		mysqli_stmt_bind_param($st, $types, ...$params);
		mysqli_stmt_execute($st);
		$res = mysqli_stmt_get_result($st);
		$rows = [];
		if ($res) {
			while ($row = mysqli_fetch_assoc($res)) {
				$rows[] = $row;
			}
		}
		mysqli_stmt_close($st);
		return $rows;
	}

	public static function getById(mysqli $con, AuthContext $ctx, int $id): ?array
	{
		$sql = 'SELECT * FROM `residents` WHERE `id` = ? LIMIT 1';
		$st = mysqli_prepare($con, $sql);
		mysqli_stmt_bind_param($st, 'i', $id);
		mysqli_stmt_execute($st);
		$res = mysqli_stmt_get_result($st);
		$row = $res ? mysqli_fetch_assoc($res) : null;
		mysqli_stmt_close($st);
		if (!is_array($row)) {
			return null;
		}
		self::assertCanAccessResident($ctx, (int) $row['barangay_id']);
		return $row;
	}

	public static function create(mysqli $con, AuthContext $ctx, array $data): int
	{
		$bid = isset($data['barangay_id']) ? (int) $data['barangay_id'] : 0;
		if ($bid < 1) {
			throw new \InvalidArgumentException('barangay_id is required.');
		}
		if (!$ctx->isAdmin() && $ctx->barangayId !== null && $bid !== $ctx->barangayId) {
			throw new \InvalidArgumentException('Staff cannot create residents outside their barangay.');
		}
		if (!$ctx->isAdmin() && $ctx->barangayId === null) {
			// staff with no assignment: allow explicit barangay_id
		}

		$last = trim((string) ($data['last_name'] ?? ''));
		$first = trim((string) ($data['first_name'] ?? ''));
		$middle = trim((string) ($data['middle_name'] ?? ''));
		$email = trim((string) ($data['email'] ?? ''));
		if ($last === '' || $first === '' || $email === '') {
			throw new \InvalidArgumentException('last_name, first_name, and email are required.');
		}

		$phone = trim((string) ($data['phone'] ?? ''));
		$birth = trim((string) ($data['birthdate'] ?? ''));
		$gender = trim((string) ($data['gender'] ?? ''));
		$addr = trim((string) ($data['address_line'] ?? ''));
		$birthSql = $birth === '' ? null : $birth;
		$phone = $phone === '' ? null : $phone;
		$middle = $middle === '' ? null : $middle;
		$gender = $gender === '' ? null : $gender;
		$addr = $addr === '' ? null : $addr;

		$sql = 'INSERT INTO `residents` (`barangay_id`, `last_name`, `first_name`, `middle_name`, `email`, `phone`, `birthdate`, `gender`, `address_line`, `status`, `created_by_user_id`)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, \'active\', ?)';
		$st = mysqli_prepare($con, $sql);
		if ($st === false) {
			throw new \RuntimeException(mysqli_error($con));
		}
		// Types: i + 8 strings + i
		mysqli_stmt_bind_param(
			$st,
			'issssssssi',
			$bid,
			$last,
			$first,
			$middle,
			$email,
			$phone,
			$birthSql,
			$gender,
			$addr,
			$ctx->userId,
		);
		if (!mysqli_stmt_execute($st)) {
			$err = mysqli_stmt_error($st);
			mysqli_stmt_close($st);
			if (str_contains($err, 'Duplicate') || str_contains($err, 'uq_resident')) {
				throw new \InvalidArgumentException('Email already registered for this barangay.');
			}
			throw new \RuntimeException($err);
		}
		$newId = (int) mysqli_insert_id($con);
		mysqli_stmt_close($st);
		return $newId;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function patch(mysqli $con, AuthContext $ctx, int $id, array $data): bool
	{
		$row = self::getByIdUnscoped($con, $id);
		if ($row === null) {
			return false;
		}
		self::assertCanAccessResident($ctx, (int) $row['barangay_id']);

		$updates = [];
		$params = [];
		$types = '';

		$fields = ['last_name', 'first_name', 'middle_name', 'email', 'phone', 'birthdate', 'gender', 'address_line'];
		foreach ($fields as $f) {
			if (!array_key_exists($f, $data)) {
				continue;
			}
			$updates[] = "`$f` = ?";
			$params[] = (string) $data[$f];
			$types .= 's';
		}

		if ($ctx->isAdmin() && array_key_exists('barangay_id', $data)) {
			$updates[] = '`barangay_id` = ?';
			$params[] = (int) $data['barangay_id'];
			$types .= 'i';
		}

		if ($ctx->isAdmin() && array_key_exists('status', $data)) {
			$stVal = (string) $data['status'];
			if (!in_array($stVal, ['active', 'archived'], true)) {
				throw new \InvalidArgumentException('status must be active or archived.');
			}
			$updates[] = '`status` = ?';
			$params[] = $stVal;
			$types .= 's';
		}

		if ($updates === []) {
			return true;
		}

		$params[] = $id;
		$types .= 'i';

		$sql = 'UPDATE `residents` SET ' . implode(', ', $updates) . ' WHERE `id` = ?';
		$st = mysqli_prepare($con, $sql);
		if ($st === false) {
			throw new \RuntimeException(mysqli_error($con));
		}
		$bind = array_merge([$types], $params);
		$refs = [];
		foreach ($bind as $i => $_) {
			$refs[$i] = &$bind[$i];
		}
		call_user_func_array([$st, 'bind_param'], $refs);
		$ok = mysqli_stmt_execute($st);
		if (!$ok && mysqli_errno($con) && str_contains(mysqli_error($con), 'Duplicate')) {
			mysqli_stmt_close($st);
			throw new \InvalidArgumentException('Email already registered for this barangay.');
		}
		mysqli_stmt_close($st);
		return $ok && mysqli_affected_rows($con) >= 0;
	}

	/**
	 * Full replace (PUT) — all fields required per RESIDENT_ROADMAP.md PUT semantics.
	 *
	 * @param array<string, mixed> $data
	 */
	public static function put(mysqli $con, AuthContext $ctx, int $id, array $data): bool
	{
		$existing = self::getByIdUnscoped($con, $id);
		if ($existing === null) {
			return false;
		}
		self::assertCanAccessResident($ctx, (int) $existing['barangay_id']);

		$required = ['last_name', 'first_name', 'email', 'phone', 'birthdate', 'gender', 'address_line', 'middle_name'];
		foreach ($required as $f) {
			if (!array_key_exists($f, $data)) {
				throw new \InvalidArgumentException('Field "' . $f . '" is required for PUT.');
			}
		}

		$bid = isset($data['barangay_id']) ? (int) $data['barangay_id'] : 0;
		if ($ctx->isAdmin()) {
			if ($bid < 1) {
				throw new \InvalidArgumentException('barangay_id is required for PUT.');
			}
		} else {
			$bid = $ctx->barangayId !== null ? (int) $ctx->barangayId : 1;
		}

		if (!$ctx->isAdmin() && $ctx->barangayId !== null && $bid !== $ctx->barangayId) {
			throw new \InvalidArgumentException('Staff cannot assign residents outside their barangay.');
		}

		$status = (string) ($existing['status'] ?? 'active');
		if ($ctx->isAdmin() && array_key_exists('status', $data)) {
			$status = (string) $data['status'];
			if (!in_array($status, ['active', 'archived'], true)) {
				throw new \InvalidArgumentException('status must be active or archived.');
			}
		}

		$last = trim((string) $data['last_name']);
		$first = trim((string) $data['first_name']);
		$middle = trim((string) $data['middle_name']);
		$email = trim((string) $data['email']);
		if ($last === '' || $first === '' || $email === '') {
			throw new \InvalidArgumentException('last_name, first_name, and email cannot be empty.');
		}

		$phone = trim((string) $data['phone']);
		$birth = trim((string) $data['birthdate']);
		$gender = trim((string) $data['gender']);
		$addr = trim((string) $data['address_line']);

		$birthSql = $birth === '' ? null : $birth;
		$phone = $phone === '' ? null : $phone;
		$middle = $middle === '' ? null : $middle;
		$gender = $gender === '' ? null : $gender;
		$addr = $addr === '' ? null : $addr;

		$sql = 'UPDATE `residents` SET `barangay_id` = ?, `last_name` = ?, `first_name` = ?, `middle_name` = ?, `email` = ?, `phone` = ?, `birthdate` = ?, `gender` = ?, `address_line` = ?, `status` = ? WHERE `id` = ?';
		$st = mysqli_prepare($con, $sql);
		if ($st === false) {
			throw new \RuntimeException(mysqli_error($con));
		}
		mysqli_stmt_bind_param($st, 'isssssssssi', $bid, $last, $first, $middle, $email, $phone, $birthSql, $gender, $addr, $status, $id);
		$ok = mysqli_stmt_execute($st);
		if (!$ok && mysqli_errno($con) && str_contains(mysqli_error($con), 'Duplicate')) {
			mysqli_stmt_close($st);
			throw new \InvalidArgumentException('Email already registered for this barangay.');
		}
		mysqli_stmt_close($st);
		return $ok && mysqli_affected_rows($con) >= 0;
	}

	private static function getByIdUnscoped(mysqli $con, int $id): ?array
	{
		$sql = 'SELECT * FROM `residents` WHERE `id` = ? LIMIT 1';
		$st = mysqli_prepare($con, $sql);
		mysqli_stmt_bind_param($st, 'i', $id);
		mysqli_stmt_execute($st);
		$res = mysqli_stmt_get_result($st);
		$row = $res ? mysqli_fetch_assoc($res) : null;
		mysqli_stmt_close($st);
		return is_array($row) ? $row : null;
	}

	private static function assertCanAccessResident(AuthContext $ctx, int $residentBarangayId): void
	{
		if ($ctx->isAdmin()) {
			return;
		}
		if ($ctx->barangayId !== null && $residentBarangayId !== $ctx->barangayId) {
			throw new \InvalidArgumentException('Forbidden: resident is outside your barangay.');
		}
	}
}
