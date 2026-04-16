<?php

declare(strict_types=1);

namespace App\Integration;

use mysqli;

/**
 * DB outbox: enqueue integration_events for async processing (e.g. SMTP worker).
 */
final class OutboxService
{
	/**
	 * @param 'permit.approved'|'permit.rejected' $eventType
	 */
	public static function enqueuePermitDecision(mysqli $con, string $eventType, int $permitId, int $residentId): void
	{
		if (!in_array($eventType, ['permit.approved', 'permit.rejected'], true)) {
			throw new \InvalidArgumentException('Invalid permit outbox event type.');
		}

		$payload = json_encode([
			'resident_id' => $residentId,
			'permit_id' => $permitId,
		], JSON_UNESCAPED_SLASHES);

		if ($payload === false) {
			throw new \RuntimeException('Could not encode outbox payload.');
		}

		$sql = 'INSERT INTO `integration_events` (`event_type`, `aggregate_id`, `aggregate_type`, `payload`, `status`)
			VALUES (?, ?, \'permit\', ?, \'pending\')';
		$st = mysqli_prepare($con, $sql);
		if ($st === false) {
			throw new \RuntimeException(mysqli_error($con));
		}
		mysqli_stmt_bind_param($st, 'sis', $eventType, $permitId, $payload);
		if (!mysqli_stmt_execute($st)) {
			$err = mysqli_stmt_error($st);
			mysqli_stmt_close($st);
			throw new \RuntimeException('Outbox insert failed: ' . $err);
		}
		mysqli_stmt_close($st);
	}

	/**
	 * After mock payment succeeds — worker sends receipt email using resident email.
	 */
	public static function enqueuePaymentPaid(mysqli $con, int $paymentId, int $permitId, int $residentId): void
	{
		$payload = json_encode([
			'resident_id' => $residentId,
			'permit_id' => $permitId,
			'payment_id' => $paymentId,
		], JSON_UNESCAPED_SLASHES);

		if ($payload === false) {
			throw new \RuntimeException('Could not encode outbox payload.');
		}

		$sql = 'INSERT INTO `integration_events` (`event_type`, `aggregate_id`, `aggregate_type`, `payload`, `status`)
			VALUES (\'payment.paid\', ?, \'payment\', ?, \'pending\')';
		$st = mysqli_prepare($con, $sql);
		if ($st === false) {
			throw new \RuntimeException(mysqli_error($con));
		}
		mysqli_stmt_bind_param($st, 'is', $paymentId, $payload);
		if (!mysqli_stmt_execute($st)) {
			$err = mysqli_stmt_error($st);
			mysqli_stmt_close($st);
			throw new \RuntimeException('Outbox insert failed: ' . $err);
		}
		mysqli_stmt_close($st);
	}
}
