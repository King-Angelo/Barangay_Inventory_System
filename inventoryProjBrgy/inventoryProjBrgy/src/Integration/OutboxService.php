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
		try {
			if (!mysqli_stmt_execute($st)) {
				$err = mysqli_stmt_error($st);
				mysqli_stmt_close($st);
				throw new \RuntimeException('Outbox insert failed: ' . $err);
			}
		} catch (\mysqli_sql_exception $e) {
			mysqli_stmt_close($st);
			self::rethrowOutboxFailure($con, $e);
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
		try {
			if (!mysqli_stmt_execute($st)) {
				$err = mysqli_stmt_error($st);
				mysqli_stmt_close($st);
				throw new \RuntimeException('Outbox insert failed: ' . $err);
			}
		} catch (\mysqli_sql_exception $e) {
			mysqli_stmt_close($st);
			self::rethrowOutboxFailure($con, $e);
		}
		mysqli_stmt_close($st);
	}

	private static function rethrowOutboxFailure(mysqli $con, \mysqli_sql_exception $e): void
	{
		$msg = $e->getMessage();
		$hint = '';
		$no = mysqli_errno($con);
		if ($no === 1146 || str_contains($msg, '1146') || str_contains($msg, "doesn't exist")) {
			$hint = ' Apply migration `005_integration_events_and_notification_log.sql` (table `integration_events`).';
		}
		throw new \RuntimeException('Outbox insert failed: ' . $msg . $hint, 0, $e);
	}
}
