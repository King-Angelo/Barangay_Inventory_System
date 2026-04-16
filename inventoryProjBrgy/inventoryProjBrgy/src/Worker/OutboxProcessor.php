<?php

declare(strict_types=1);

namespace App\Worker;

use mysqli;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

/**
 * Polls pending integration_events and sends email to residents.email via SMTP.
 */
final class OutboxProcessor
{
	public const BATCH_LIMIT = 50;

	public static function run(mysqli $con): int
	{
		$smtpHost = getenv('SMTP_HOST');
		if ($smtpHost === false || trim((string) $smtpHost) === '') {
			if (!self::hasPending($con)) {
				echo "No pending integration events.\n";
				return 0;
			}
			fwrite(STDERR, "SMTP_HOST is not set. Add SMTP_* variables to .env.local (see migrations/PRODUCTION_SETUP.md).\n");
			return 2;
		}

		$processed = 0;
		$hadFailure = false;

		$sql = 'SELECT `id`, `event_type`, `payload`, `attempts` FROM `integration_events`
			WHERE `status` = ? ORDER BY `id` ASC LIMIT ' . (int) self::BATCH_LIMIT;
		$st = mysqli_prepare($con, $sql);
		if ($st === false) {
			fwrite(STDERR, mysqli_error($con) . "\n");
			return 1;
		}
		$pending = 'pending';
		mysqli_stmt_bind_param($st, 's', $pending);
		mysqli_stmt_execute($st);
		$res = mysqli_stmt_get_result($st);
		$rows = [];
		if ($res) {
			while ($row = mysqli_fetch_assoc($res)) {
				$rows[] = $row;
			}
		}
		mysqli_stmt_close($st);

		foreach ($rows as $row) {
			$id = (int) $row['id'];
			mysqli_begin_transaction($con);
			try {
				$lock = mysqli_prepare($con, 'UPDATE `integration_events` SET `status` = ?, `attempts` = `attempts` + 1
					WHERE `id` = ? AND `status` = ?');
				if ($lock === false) {
					throw new \RuntimeException(mysqli_error($con));
				}
				$processing = 'processing';
				$pend = 'pending';
				mysqli_stmt_bind_param($lock, 'sis', $processing, $id, $pend);
				mysqli_stmt_execute($lock);
				$claimed = mysqli_affected_rows($con) > 0;
				mysqli_stmt_close($lock);
				if (!$claimed) {
					mysqli_commit($con);
					continue;
				}
				mysqli_commit($con);
			} catch (\Throwable $e) {
				mysqli_rollback($con);
				$hadFailure = true;
				error_log('[outbox] claim ' . $id . ': ' . $e->getMessage());
				continue;
			}

			try {
				self::processOne($con, $id, (string) $row['event_type'], (string) $row['payload']);
				$processed++;
			} catch (\Throwable $e) {
				$hadFailure = true;
				error_log('[outbox] event ' . $id . ': ' . $e->getMessage());
				self::markEventFailed($con, $id, $e->getMessage());
			}
		}

		echo "Sent notifications for {$processed} event(s).\n";
		return $hadFailure ? 1 : 0;
	}

	private static function hasPending(mysqli $con): bool
	{
		$r = mysqli_query($con, "SELECT 1 FROM `integration_events` WHERE `status` = 'pending' LIMIT 1");
		return $r !== false && mysqli_num_rows($r) > 0;
	}

	private static function processOne(mysqli $con, int $eventId, string $eventType, string $payloadJson): void
	{
		$data = json_decode($payloadJson, true);
		if (!is_array($data)) {
			self::finalizeFailure($con, $eventId, 0, '', 'Invalid payload JSON', 'outbox_invalid_payload');
			return;
		}

		$residentId = isset($data['resident_id']) ? (int) $data['resident_id'] : 0;
		$permitId = isset($data['permit_id']) ? (int) $data['permit_id'] : 0;
		if ($residentId <= 0 || $permitId <= 0) {
			self::finalizeFailure($con, $eventId, 0, '', 'Missing resident_id or permit_id in payload', 'outbox_bad_payload');
			return;
		}

		if (!str_starts_with($eventType, 'permit.') && !str_starts_with($eventType, 'payment.')) {
			self::finalizeFailure($con, $eventId, $residentId, '', 'Unsupported event_type: ' . $eventType, 'outbox_unsupported');
			return;
		}

		$st = mysqli_prepare($con, 'SELECT `email`, `first_name`, `last_name` FROM `residents` WHERE `id` = ? LIMIT 1');
		if ($st === false) {
			throw new \RuntimeException(mysqli_error($con));
		}
		mysqli_stmt_bind_param($st, 'i', $residentId);
		mysqli_stmt_execute($st);
		$res = mysqli_stmt_get_result($st);
		$resident = $res ? mysqli_fetch_assoc($res) : null;
		mysqli_stmt_close($st);
		if (!is_array($resident)) {
			self::finalizeFailure($con, $eventId, 0, '', 'Resident not found', 'outbox_no_resident');
			return;
		}

		$email = trim((string) ($resident['email'] ?? ''));
		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			self::finalizeFailure($con, $eventId, $residentId, $email, 'Invalid or missing resident email', 'outbox_bad_email');
			return;
		}

		$st2 = mysqli_prepare($con, 'SELECT `reference_no`, `status` FROM `permits` WHERE `id` = ? LIMIT 1');
		if ($st2 === false) {
			throw new \RuntimeException(mysqli_error($con));
		}
		mysqli_stmt_bind_param($st2, 'i', $permitId);
		mysqli_stmt_execute($st2);
		$res2 = mysqli_stmt_get_result($st2);
		$permit = $res2 ? mysqli_fetch_assoc($res2) : null;
		mysqli_stmt_close($st2);
		if (!is_array($permit)) {
			self::finalizeFailure($con, $eventId, $residentId, $email, 'Permit not found', 'outbox_no_permit');
			return;
		}

		$ref = (string) ($permit['reference_no'] ?? '');
		$subject = self::subjectFor($eventType, $ref);
		$body = self::bodyFor($eventType, $resident, $ref, (string) ($permit['status'] ?? ''));

		try {
			self::sendSmtp($email, $subject, $body);
		} catch (MailException $e) {
			self::finalizeFailure($con, $eventId, $residentId, $email, self::truncateErr($e->getMessage()), 'smtp_error');
			return;
		}

		mysqli_begin_transaction($con);
		try {
			$ins = mysqli_prepare($con, 'INSERT INTO `notification_log` (`integration_event_id`, `resident_id`, `recipient_email`, `subject`, `status`, `sent_at`)
				VALUES (?, ?, ?, ?, \'sent\', NOW())');
			if ($ins === false) {
				throw new \RuntimeException(mysqli_error($con));
			}
			mysqli_stmt_bind_param($ins, 'iiss', $eventId, $residentId, $email, $subject);
			mysqli_stmt_execute($ins);
			mysqli_stmt_close($ins);

			$upd = mysqli_prepare($con, 'UPDATE `integration_events` SET `status` = \'processed\', `processed_at` = NOW(), `last_error` = NULL WHERE `id` = ?');
			if ($upd === false) {
				throw new \RuntimeException(mysqli_error($con));
			}
			mysqli_stmt_bind_param($upd, 'i', $eventId);
			mysqli_stmt_execute($upd);
			mysqli_stmt_close($upd);
			mysqli_commit($con);
		} catch (\Throwable $e) {
			mysqli_rollback($con);
			throw $e;
		}
	}

	private static function finalizeFailure(
		mysqli $con,
		int $eventId,
		int $residentId,
		string $email,
		string $err,
		string $subjectTag
	): void {
		mysqli_begin_transaction($con);
		try {
			if ($residentId > 0 && self::residentExists($con, $residentId)) {
				$ins = mysqli_prepare($con, 'INSERT INTO `notification_log` (`integration_event_id`, `resident_id`, `recipient_email`, `subject`, `status`, `error_message`, `sent_at`)
					VALUES (?, ?, ?, ?, \'failed\', ?, NULL)');
				if ($ins !== false) {
					$subj = '[' . $subjectTag . '] Barangay permit notification';
					$em = $email !== '' ? $email : 'unknown';
					mysqli_stmt_bind_param($ins, 'iisss', $eventId, $residentId, $em, $subj, $err);
					mysqli_stmt_execute($ins);
					mysqli_stmt_close($ins);
				}
			}

			$upd = mysqli_prepare($con, 'UPDATE `integration_events` SET `status` = \'failed\', `processed_at` = NOW(), `last_error` = ? WHERE `id` = ?');
			if ($upd === false) {
				throw new \RuntimeException(mysqli_error($con));
			}
			$e2 = self::truncateErr($err);
			mysqli_stmt_bind_param($upd, 'si', $e2, $eventId);
			mysqli_stmt_execute($upd);
			mysqli_stmt_close($upd);
			mysqli_commit($con);
		} catch (\Throwable $e) {
			mysqli_rollback($con);
			throw $e;
		}
	}

	private static function residentExists(mysqli $con, int $residentId): bool
	{
		$st = mysqli_prepare($con, 'SELECT 1 FROM `residents` WHERE `id` = ? LIMIT 1');
		if ($st === false) {
			return false;
		}
		mysqli_stmt_bind_param($st, 'i', $residentId);
		mysqli_stmt_execute($st);
		$res = mysqli_stmt_get_result($st);
		$ok = $res && mysqli_fetch_row($res) !== null;
		mysqli_stmt_close($st);
		return $ok;
	}

	private static function markEventFailed(mysqli $con, int $eventId, string $message): void
	{
		$upd = mysqli_prepare($con, 'UPDATE `integration_events` SET `status` = \'failed\', `processed_at` = NOW(), `last_error` = ? WHERE `id` = ?');
		if ($upd === false) {
			return;
		}
		$msg = self::truncateErr($message);
		mysqli_stmt_bind_param($upd, 'si', $msg, $eventId);
		mysqli_stmt_execute($upd);
		mysqli_stmt_close($upd);
	}

	private static function truncateErr(string $msg): string
	{
		if (strlen($msg) > 2000) {
			return substr($msg, 0, 1997) . '...';
		}
		return $msg;
	}

	private static function subjectFor(string $eventType, string $referenceNo): string
	{
		$ref = $referenceNo !== '' ? $referenceNo : 'permit';
		return match ($eventType) {
			'permit.approved' => 'Permit approved — ' . $ref,
			'permit.rejected' => 'Permit decision — ' . $ref,
			'payment.paid' => 'Payment received — ' . $ref,
			default => 'Permit update — ' . $ref,
		};
	}

	/**
	 * @param array<string, mixed> $resident
	 */
	private static function bodyFor(string $eventType, array $resident, string $referenceNo, string $permitStatus): string
	{
		$name = trim((string) ($resident['first_name'] ?? '') . ' ' . (string) ($resident['last_name'] ?? ''));
		$lines = [
			'Dear ' . ($name !== '' ? $name : 'resident') . ',',
			'',
		];
		if ($eventType === 'permit.approved') {
			$lines[] = 'Your barangay permit application has been approved.';
		} elseif ($eventType === 'permit.rejected') {
			$lines[] = 'Your barangay permit application was not approved.';
		} elseif ($eventType === 'payment.paid') {
			$lines[] = 'We have recorded your mock payment for this permit. Thank you.';
		} else {
			$lines[] = 'There is an update regarding your permit application.';
		}
		$lines[] = '';
		$lines[] = 'Reference: ' . ($referenceNo !== '' ? $referenceNo : '(see office)');
		if ($permitStatus !== '') {
			$lines[] = 'Status: ' . $permitStatus;
		}
		$lines[] = '';
		$lines[] = 'This is an automated message from the Barangay Inventory system.';
		return implode("\n", $lines);
	}

	/**
	 * @throws MailException
	 */
	private static function sendSmtp(string $to, string $subject, string $body): void
	{
		$mail = new PHPMailer(true);
		$mail->CharSet = PHPMailer::CHARSET_UTF8;
		$mail->isSMTP();
		$mail->Host = (string) getenv('SMTP_HOST');
		$port = getenv('SMTP_PORT');
		$mail->Port = ($port !== false && $port !== '') ? (int) $port : 587;

		$secure = strtolower(trim((string) (getenv('SMTP_SECURE') ?: '')));
		if ($secure === 'ssl') {
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
		} elseif ($secure === 'tls' || $secure === '') {
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
		} elseif ($secure === 'none' || $secure === 'off') {
			$mail->SMTPSecure = '';
			$mail->SMTPAutoTLS = false;
		} else {
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
		}

		$user = getenv('SMTP_USER');
		$pass = getenv('SMTP_PASS');
		if ($user !== false && (string) $user !== '') {
			$mail->SMTPAuth = true;
			$mail->Username = (string) $user;
			$mail->Password = $pass !== false ? (string) $pass : '';
		} else {
			$mail->SMTPAuth = false;
		}

		$from = getenv('MAIL_FROM');
		$mail->setFrom(
			($from !== false && (string) $from !== '') ? (string) $from : 'noreply@localhost',
			(string) (getenv('MAIL_FROM_NAME') ?: 'Barangay Inventory')
		);
		$mail->addAddress($to);
		$mail->Subject = $subject;
		$mail->Body = $body;

		$mail->send();
	}
}
