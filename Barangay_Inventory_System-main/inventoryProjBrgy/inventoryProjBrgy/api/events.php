<?php
declare(strict_types=1);

function handle_get_integration_events(): void
{
    $claims = api_require_jwt();
    api_require_role($claims, ['staff', 'admin']);

    global $con;
    $stmt = mysqli_prepare($con, 'SELECT id, event_type, payload, status, created_at, processed_at FROM integration_events ORDER BY created_at DESC LIMIT 100');
    if ($stmt === false) {
        api_error('Unable to prepare integration events query.', 500);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result === false) {
        mysqli_stmt_close($stmt);
        api_error('Unable to retrieve integration events.', 500);
    }

    $events = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['payload'] = json_decode($row['payload'] ?? '[]', true);
        $events[] = $row;
    }
    mysqli_stmt_close($stmt);

    api_json_response(['events' => $events]);
}

function handle_process_integration_events(): void
{
    $claims = api_require_jwt();
    api_require_role($claims, ['staff', 'admin']);

    $summary = process_pending_events();
    api_json_response(['summary' => $summary]);
}

function process_pending_events(): array
{
    global $con;
    $stmt = mysqli_prepare($con, 'SELECT id, event_type, payload FROM integration_events WHERE status = ? ORDER BY created_at ASC LIMIT 20');
    if ($stmt === false) {
        return ['processed' => 0, 'sent' => 0, 'failed' => 0, 'error' => 'Unable to prepare event query.'];
    }
    $status = 'pending';
    mysqli_stmt_bind_param($stmt, 's', $status);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result === false) {
        mysqli_stmt_close($stmt);
        return ['processed' => 0, 'sent' => 0, 'failed' => 0, 'error' => 'Unable to retrieve events.'];
    }

    $events = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $events[] = $row;
    }
    mysqli_stmt_close($stmt);

    $summary = ['processed' => 0, 'sent' => 0, 'failed' => 0];

    foreach ($events as $event) {
        $summary['processed']++;
        $success = process_integration_event((int) $event['id'], $event['event_type'], $event['payload']);
        if ($success) {
            update_integration_event_status((int) $event['id'], 'sent');
            $summary['sent']++;
        } else {
            update_integration_event_status((int) $event['id'], 'failed');
            $summary['failed']++;
        }
    }

    return $summary;
}

function update_integration_event_status(int $eventId, string $status): void
{
    global $con;
    $stmt = mysqli_prepare($con, 'UPDATE integration_events SET status = ?, processed_at = NOW() WHERE id = ?');
    if ($stmt === false) {
        return;
    }
    mysqli_stmt_bind_param($stmt, 'si', $status, $eventId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function process_integration_event(int $eventId, string $eventType, string $payloadJson): bool
{
    $payload = json_decode($payloadJson, true);
    if (!is_array($payload)) {
        return false;
    }

    if ($eventType === 'permit.created') {
        return process_permit_event($eventType, $payload);
    }
    if ($eventType === 'permit.status_changed') {
        return process_permit_event($eventType, $payload);
    }

    return true;
}

function process_permit_event(string $eventType, array $payload): bool
{
    $permitId = isset($payload['permit_id']) ? (int) $payload['permit_id'] : 0;
    if ($permitId < 1) {
        return false;
    }

    $permit = get_permit_row_by_id($permitId);
    if (!is_array($permit) || empty($permit['email'])) {
        return false;
    }

    $subject = api_build_permit_email_subject($eventType);
    $body = api_build_permit_email_body($eventType, array_merge($payload, [
        'reference_no' => $permit['reference_no'],
        'resident_email' => $permit['email'],
    ]));

    return api_send_email($permit['email'], $subject, $body);
}

function get_permit_row_by_id(int $id): ?array
{
    global $con;
    $stmt = mysqli_prepare($con, 'SELECT p.reference_no, p.status, r.id AS resident_id, r.first_name, r.last_name, r.email FROM permits p LEFT JOIN residents r ON p.resident_id = r.id WHERE p.id = ? LIMIT 1');
    if ($stmt === false) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result === false) {
        mysqli_stmt_close($stmt);
        return null;
    }
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return is_array($row) ? $row : null;
}
