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
		$sql = 'SELECT p.*, r.`last_name`, r.`first_name`, r.`barangay_id` AS `resident_barangay_id`, pt.`name` AS `permit_type_name`
			FROM `permits` p
			JOIN `residents` r ON r.`id` = p.`resident_id`
			JOIN `permit_types` pt ON pt.`id` = p.`permit_type_id`
			WHERE p.`id` = ? LIMIT 1';
		$st = mysqli_prepare($con, $sql);
		mysqli_stmt_bind_param($st, 'i', $id);
		mysqli_stmt_execute($st);
		$res = mysqli_stmt_get_result($st);
		$row = $res ? mysqli_fetch_assoc($res) : null;
		mysqli_stmt_close($st);
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
		mysqli_stmt_bind_param($st, 'iisi', $residentId, $permitTypeId, $ref, $ctx->userId);
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
				throw new \InvalidArgumentException('Only draft permits can be submitted.');
			}
			$sql = 'UPDATE `permits` SET `status` = \'submitted\', `submitted_by` = ?, `submitted_at` = NOW() WHERE `id` = ? AND `status` = \'draft\'';
			$st = mysqli_prepare($con, $sql);
			$uid = $ctx->userId;
			mysqli_stmt_bind_param($st, 'ii', $uid, $permitId);
			mysqli_stmt_execute($st);
			$ok = mysqli_affected_rows($con) > 0;
			mysqli_stmt_close($st);
			if (!$ok) {
				throw new \InvalidArgumentException('Submit failed.');
			}
			return self::getById($con, $ctx, $permitId) ?? [];
		}

		if ($action === 'approve' || $action === 'reject') {
			if (!$ctx->isAdmin()) {
				throw new \InvalidArgumentException('Only admins can approve or reject.');
			}
			if ($p['status'] !== 'submitted') {
				throw new \InvalidArgumentException('Only submitted permits can be approved or rejected.');
			}
			$newStatus = $action === 'approve' ? 'approved' : 'rejected';
			$residentId = (int) $p['resident_id'];
			mysqli_begin_transaction($con);
			try {
				$sql = 'UPDATE `permits` SET `status` = ?, `approved_by` = ?, `approved_at` = NOW(), `remarks` = ? WHERE `id` = ? AND `status` = \'submitted\'';
				$st = mysqli_prepare($con, $sql);
				$adminId = $ctx->userId;
				mysqli_stmt_bind_param($st, 'sisi', $newStatus, $adminId, $remarks, $permitId);
				mysqli_stmt_execute($st);
				$ok = mysqli_affected_rows($con) > 0;
				mysqli_stmt_close($st);
				if (!$ok) {
					mysqli_rollback($con);
					throw new \InvalidArgumentException('Decision failed.');
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
		if (($p['status'] ?? '') !== 'draft') {
			throw new \InvalidArgumentException('Only draft permits can be deleted.');
		}
		$sql = 'DELETE FROM `permits` WHERE `id` = ? AND `status` = \'draft\'';
		$st = mysqli_prepare($con, $sql);
		mysqli_stmt_bind_param($st, 'i', $id);
		mysqli_stmt_execute($st);
		$ok = mysqli_affected_rows($con) > 0;
		mysqli_stmt_close($st);
		return $ok;
	}

	private static function residentBarangayId(mysqli $con, int $residentId): int
	{
		$sql = 'SELECT `barangay_id` FROM `residents` WHERE `id` = ? LIMIT 1';
		$st = mysqli_prepare($con, $sql);
		mysqli_stmt_bind_param($st, 'i', $residentId);
		mysqli_stmt_execute($st);
		$res = mysqli_stmt_get_result($st);
		$row = $res ? mysqli_fetch_assoc($res) : null;
		mysqli_stmt_close($st);
		return is_array($row) ? (int) $row['barangay_id'] : 0;
	}
}
