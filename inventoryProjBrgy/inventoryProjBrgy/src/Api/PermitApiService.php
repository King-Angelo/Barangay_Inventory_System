<?php

declare(strict_types=1);

namespace App\Api;

use App\Integration\OutboxService;
use mysqli;

final class PermitApiService
{
	/**
	 * @return list<array<string, mixed>>
	 */
	public static function list(mysqli $con, AuthContext $ctx, ?int $residentId): array
	{
		if ($residentId !== null && $residentId > 0) {
			$r = ResidentApiService::getById($con, $ctx, $residentId);
			if ($r === null) {
				throw new \InvalidArgumentException('Resident not found.');
			}
		}

		if ($residentId !== null && $residentId > 0) {
			$sql = 'SELECT p.*, r.`last_name`, r.`first_name`, pt.`name` AS `permit_type_name`,
				su.`UserName` AS `submitted_by_name`, au.`UserName` AS `approved_by_name`
				FROM `permits` p
				JOIN `residents` r ON r.`id` = p.`resident_id`
				JOIN `permit_types` pt ON pt.`id` = p.`permit_type_id`
				LEFT JOIN `users` su ON su.`id` = p.`submitted_by`
				LEFT JOIN `users` au ON au.`id` = p.`approved_by`
				WHERE p.`resident_id` = ?
				ORDER BY p.`updated_at` DESC';
			$st = mysqli_prepare($con, $sql);
			mysqli_stmt_bind_param($st, 'i', $residentId);
		} else {
			$sql = 'SELECT p.*, r.`last_name`, r.`first_name`, pt.`name` AS `permit_type_name`,
				su.`UserName` AS `submitted_by_name`, au.`UserName` AS `approved_by_name`
				FROM `permits` p
				JOIN `residents` r ON r.`id` = p.`resident_id`
				JOIN `permit_types` pt ON pt.`id` = p.`permit_type_id`
				LEFT JOIN `users` su ON su.`id` = p.`submitted_by`
				LEFT JOIN `users` au ON au.`id` = p.`approved_by`
				ORDER BY p.`updated_at` DESC';
			$st = mysqli_prepare($con, $sql);
		}

		mysqli_stmt_execute($st);
		$res = mysqli_stmt_get_result($st);
		$rows = [];
		if ($res) {
			while ($row = mysqli_fetch_assoc($res)) {
				// staff: only permits for residents in their barangay
				if (!$ctx->isAdmin() && $ctx->barangayId !== null) {
					$rid = (int) $row['resident_id'];
					$rb = self::residentBarangayId($con, $rid);
					if ($rb !== $ctx->barangayId) {
						continue;
					}
				}
				$rows[] = $row;
			}
		}
		mysqli_stmt_close($st);
		return $rows;
	}

	public static function getById(mysqli $con, AuthContext $ctx, int $id): ?array
	{
		if ($id < 1) {
			return null;
		}
		// Integer id only — stable without mysqlnd `mysqli_stmt_get_result`.
		$sql = 'SELECT p.*, r.`last_name`, r.`first_name`, r.`barangay_id` AS `resident_barangay_id`, pt.`name` AS `permit_type_name`
			FROM `permits` p
			JOIN `residents` r ON r.`id` = p.`resident_id`
			JOIN `permit_types` pt ON pt.`id` = p.`permit_type_id`
			WHERE p.`id` = ' . $id . ' LIMIT 1';
		$result = mysqli_query($con, $sql);
		if ($result === false) {
			return null;
		}
		$row = mysqli_fetch_assoc($result);
		mysqli_free_result($result);
		if (!is_array($row)) {
			return null;
		}
		if (!$ctx->isAdmin() && $ctx->barangayId !== null && (int) $row['resident_barangay_id'] !== $ctx->barangayId) {
			throw new \InvalidArgumentException('Forbidden.');
		}
		unset($row['resident_barangay_id']);
		return $row;
	}

	public static function create(mysqli $con, AuthContext $ctx, int $residentId, int $permitTypeId): int
	{
		$resident = ResidentApiService::getById($con, $ctx, $residentId);
		if ($resident === null) {
			throw new \InvalidArgumentException('Resident not found.');
		}

		$ref = 'REF-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
		$sql = 'INSERT INTO `permits` (`resident_id`, `permit_type_id`, `reference_no`, `status`, `submitted_by`)
			VALUES (?, ?, ?, \'draft\', ?)';
		$st = mysqli_prepare($con, $sql);
		if ($st === false) {
			throw new \RuntimeException(mysqli_error($con));
		}
		$submittedBy = $ctx->userId;
		mysqli_stmt_bind_param($st, 'iisi', $residentId, $permitTypeId, $ref, $submittedBy);
		if (!mysqli_stmt_execute($st)) {
			mysqli_stmt_close($st);
			throw new \RuntimeException('Could not create permit.');
		}
		$newId = (int) mysqli_insert_id($con);
		mysqli_stmt_close($st);
		return $newId;
	}

	/**
	 * @param array{action?:string, remarks?:string} $body
	 */
	public static function patch(mysqli $con, AuthContext $ctx, int $permitId, array $body): array
	{
		$action = isset($body['action']) ? strtolower(trim((string) $body['action'])) : '';
		$remarks = isset($body['remarks']) ? trim((string) $body['remarks']) : '';

		$p = self::getById($con, $ctx, $permitId);
		if ($p === null) {
			throw new \InvalidArgumentException('Permit not found.');
		}

		if ($action === 'submit') {
			if ($p['status'] !== 'draft') {
				throw new \InvalidArgumentException('Only draft permits can be submitted. Current status is ' . (string) $p['status'] . '. Create a new draft or use the permit id from POST /v1/permits (see List).');
			}
			$sql = 'UPDATE `permits` SET `status` = \'submitted\', `submitted_by` = ?, `submitted_at` = NOW() WHERE `id` = ? AND `status` = \'draft\'';
			$st = mysqli_prepare($con, $sql);
			if ($st === false) {
				throw new \RuntimeException(mysqli_error($con));
			}
			$uid = $ctx->userId;
			mysqli_stmt_bind_param($st, 'ii', $uid, $permitId);
			try {
				if (!mysqli_stmt_execute($st)) {
					$err = mysqli_stmt_error($st);
					mysqli_stmt_close($st);
					throw new \InvalidArgumentException('Submit failed: ' . ($err !== '' ? $err : 'UPDATE failed.'));
				}
			} catch (\mysqli_sql_exception $e) {
				mysqli_stmt_close($st);
				throw new \RuntimeException($e->getMessage(), 0, $e);
			}
			$aff = mysqli_stmt_affected_rows($st);
			mysqli_stmt_close($st);
			if ($aff < 1) {
				throw new \InvalidArgumentException('Submit failed (no row updated). The permit may have changed; GET /v1/permits/{id} and retry.');
			}
			return self::getById($con, $ctx, $permitId) ?? [];
		}

		if ($action === 'approve' || $action === 'reject') {
			if (!$ctx->isAdmin()) {
				throw new \InvalidArgumentException('Only admins can approve or reject.');
			}
			if ($p['status'] !== 'submitted') {
				throw new \InvalidArgumentException(
					'Only submitted permits can be approved (current status: ' . (string) $p['status'] . ').'
					. ' Send PATCH submit while draft, or List permits and pick id with status submitted.'
				);
			}
			$newStatus = $action === 'approve' ? 'approved' : 'rejected';
			$residentId = (int) $p['resident_id'];
			mysqli_begin_transaction($con);
			try {
				$sql = 'UPDATE `permits` SET `status` = ?, `approved_by` = ?, `approved_at` = NOW(), `remarks` = ? WHERE `id` = ? AND `status` = \'submitted\'';
				$st = mysqli_prepare($con, $sql);
				if ($st === false) {
					throw new \RuntimeException(mysqli_error($con));
				}
				$adminId = $ctx->userId;
				mysqli_stmt_bind_param($st, 'sisi', $newStatus, $adminId, $remarks, $permitId);
				try {
					if (!mysqli_stmt_execute($st)) {
						$updErr = mysqli_stmt_error($st);
						mysqli_stmt_close($st);
						throw new \InvalidArgumentException('Decision failed: ' . ($updErr !== '' ? $updErr : 'UPDATE failed.'));
					}
				} catch (\mysqli_sql_exception $e) {
					mysqli_stmt_close($st);
					throw new \RuntimeException('Permit decision update failed: ' . $e->getMessage(), 0, $e);
				}
				$ok = mysqli_stmt_affected_rows($st) > 0;
				mysqli_stmt_close($st);
				if (!$ok) {
					throw new \InvalidArgumentException('Decision failed (no row updated). Reload the permit and try again.');
				}
				$eventType = $action === 'approve' ? 'permit.approved' : 'permit.rejected';
				OutboxService::enqueuePermitDecision($con, $eventType, $permitId, $residentId);
				mysqli_commit($con);
			} catch (\Throwable $e) {
				mysqli_rollback($con);
				throw $e;
			}
			return self::getById($con, $ctx, $permitId) ?? [];
		}

		throw new \InvalidArgumentException('Unknown action. Use submit, approve, or reject.');
	}

	/**
	 * Remove a draft permit (DELETE verb for rubric). Not allowed once submitted.
	 */
	public static function deleteDraft(mysqli $con, AuthContext $ctx, int $id): bool
	{
		$p = self::getById($con, $ctx, $id);
		if ($p === null) {
			throw new \InvalidArgumentException('Permit not found.');
		}
		$stat = (string) ($p['status'] ?? '');
		if ($stat !== 'draft') {
			throw new \InvalidArgumentException('Only draft permits can be deleted (current status: ' . $stat . '). Cancel from the list only while status is draft.');
		}
		$sql = 'DELETE FROM `permits` WHERE `id` = ? AND `status` = \'draft\'';
		$st = mysqli_prepare($con, $sql);
		if ($st === false) {
			throw new \RuntimeException(mysqli_error($con));
		}
		mysqli_stmt_bind_param($st, 'i', $id);
		try {
			if (!mysqli_stmt_execute($st)) {
				mysqli_stmt_close($st);
				throw new \InvalidArgumentException('Delete failed: ' . mysqli_error($con));
			}
		} catch (\mysqli_sql_exception $e) {
			mysqli_stmt_close($st);
			throw new \RuntimeException('Delete failed: ' . $e->getMessage(), 0, $e);
		}
		$ok = mysqli_stmt_affected_rows($st) > 0;
		mysqli_stmt_close($st);
		return $ok;
	}

	private static function residentBarangayId(mysqli $con, int $residentId): int
	{
		if ($residentId < 1) {
			return 0;
		}
		$sql = 'SELECT `barangay_id` FROM `residents` WHERE `id` = ' . $residentId . ' LIMIT 1';
		$res = mysqli_query($con, $sql);
		if ($res === false) {
			return 0;
		}
		$row = mysqli_fetch_assoc($res);
		mysqli_free_result($res);
		return is_array($row) ? (int) $row['barangay_id'] : 0;
	}
}
