<?php

declare(strict_types=1);

namespace App\Api;

use App\Integration\OutboxService;
use mysqli;

/**
 * Mock payment provider — records a paid row and moves permit to `paid` (rubric / Phase 3).
 */
final class PaymentApiService
{
	/**
	 * @param array{permit_id?:int, amount?:float|string, idempotency_key?:string} $body
	 * @return array<string, mixed>
	 */
	public static function createMockPayment(mysqli $con, AuthContext $ctx, array $body): array
	{
		$permitId = isset($body['permit_id']) ? (int) $body['permit_id'] : 0;
		if ($permitId < 1) {
			throw new \InvalidArgumentException('permit_id is required.');
		}

		$amount = isset($body['amount']) ? (float) $body['amount'] : 100.0;
		if ($amount <= 0 || $amount > 999999.99) {
			throw new \InvalidArgumentException('amount must be between 0.01 and 999999.99.');
		}

		$idemp = isset($body['idempotency_key']) ? trim((string) $body['idempotency_key']) : '';
		if ($idemp !== '') {
			$existing = self::findByIdempotency($con, $idemp);
			if ($existing !== null) {
				return $existing;
			}
		}

		$p = PermitApiService::getById($con, $ctx, $permitId);
		if ($p === null) {
			throw new \InvalidArgumentException('Permit not found.');
		}

		$stPerm = (string) $p['status'];
		if (!in_array($stPerm, ['approved', 'ready_for_payment'], true)) {
			throw new \InvalidArgumentException('Mock payment allowed only when permit is approved or ready_for_payment.');
		}

		$residentId = (int) $p['resident_id'];
		$providerRef = 'MOCK-' . strtoupper(bin2hex(random_bytes(8)));

		mysqli_begin_transaction($con);
		try {
			$sql = 'INSERT INTO `payments` (`permit_id`, `amount`, `currency`, `status`, `provider`, `provider_ref`, `idempotency_key`, `paid_at`)
				VALUES (?, ?, \'PHP\', \'paid\', \'mock\', ?, ?, NOW())';
			$st = mysqli_prepare($con, $sql);
			if ($st === false) {
				throw new \RuntimeException(mysqli_error($con));
			}
			$idempOrNull = $idemp !== '' ? $idemp : null;
			mysqli_stmt_bind_param($st, 'idss', $permitId, $amount, $providerRef, $idempOrNull);
			if (!mysqli_stmt_execute($st)) {
				$err = mysqli_stmt_error($st);
				mysqli_stmt_close($st);
				if (str_contains($err, 'Duplicate') || str_contains($err, 'uq_permit')) {
					throw new \InvalidArgumentException('Payment already exists for this permit.');
				}
				throw new \RuntimeException($err);
			}
			$paymentId = (int) mysqli_insert_id($con);
			mysqli_stmt_close($st);

			$sql2 = 'UPDATE `permits` SET `status` = \'paid\' WHERE `id` = ? AND `status` IN (\'approved\', \'ready_for_payment\')';
			$st2 = mysqli_prepare($con, $sql2);
			mysqli_stmt_bind_param($st2, 'i', $permitId);
			mysqli_stmt_execute($st2);
			if (mysqli_affected_rows($con) < 1) {
				mysqli_stmt_close($st2);
				mysqli_rollback($con);
				throw new \InvalidArgumentException('Could not update permit status to paid.');
			}
			mysqli_stmt_close($st2);

			OutboxService::enqueuePaymentPaid($con, $paymentId, $permitId, $residentId);
			mysqli_commit($con);
		} catch (\Throwable $e) {
			mysqli_rollback($con);
			throw $e;
		}

		return self::getPaymentById($con, $paymentId) ?? ['id' => $paymentId, 'permit_id' => $permitId];
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private static function findByIdempotency(mysqli $con, string $key): ?array
	{
		$sql = 'SELECT * FROM `payments` WHERE `idempotency_key` = ? LIMIT 1';
		$st = mysqli_prepare($con, $sql);
		mysqli_stmt_bind_param($st, 's', $key);
		mysqli_stmt_execute($st);
		$res = mysqli_stmt_get_result($st);
		$row = $res ? mysqli_fetch_assoc($res) : null;
		mysqli_stmt_close($st);
		return is_array($row) ? $row : null;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private static function getPaymentById(mysqli $con, int $id): ?array
	{
		$sql = 'SELECT * FROM `payments` WHERE `id` = ? LIMIT 1';
		$st = mysqli_prepare($con, $sql);
		mysqli_stmt_bind_param($st, 'i', $id);
		mysqli_stmt_execute($st);
		$res = mysqli_stmt_get_result($st);
		$row = $res ? mysqli_fetch_assoc($res) : null;
		mysqli_stmt_close($st);
		return is_array($row) ? $row : null;
	}
}
