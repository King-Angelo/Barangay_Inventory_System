<?php
declare(strict_types=1);

function handle_get_permits(): void
{
    $claims = api_require_jwt();
    api_require_role($claims, ['staff', 'admin']);

    global $con;
    $queryParts = [
        'SELECT p.id, p.reference_no, p.status, p.amount, p.created_at, p.updated_at,',
        'r.id AS resident_id, r.first_name, r.last_name, r.email,',
        't.id AS permit_type_id, t.name AS permit_type_name',
        'FROM permits p',
        'LEFT JOIN residents r ON p.resident_id = r.id',
        'LEFT JOIN permit_types t ON p.permit_type_id = t.id',
    ];

    $params = [];
    $types = '';
    $filters = [];

    if (isset($_GET['resident_id']) && trim((string) $_GET['resident_id']) !== '') {
        $filters[] = 'p.resident_id = ?';
        $params[] = (int) $_GET['resident_id'];
        $types .= 'i';
    }
    if (isset($_GET['status']) && trim((string) $_GET['status']) !== '') {
        $filters[] = 'p.status = ?';
        $params[] = trim((string) $_GET['status']);
        $types .= 's';
    }
    if (isset($_GET['permit_type_id']) && trim((string) $_GET['permit_type_id']) !== '') {
        $filters[] = 'p.permit_type_id = ?';
        $params[] = (int) $_GET['permit_type_id'];
        $types .= 'i';
    }
    if (isset($_GET['q']) && trim((string) $_GET['q']) !== '') {
        $search = '%' . trim((string) $_GET['q']) . '%';
        $filters[] = '(p.reference_no LIKE ? OR r.first_name LIKE ? OR r.last_name LIKE ? OR r.email LIKE ?)';
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= 'ssss';
    }

    if ($filters !== []) {
        $queryParts[] = 'WHERE ' . implode(' AND ', $filters);
    }
    $queryParts[] = 'ORDER BY p.created_at DESC';
    $query = implode(' ', $queryParts);

    $stmt = mysqli_prepare($con, $query);
    if ($stmt === false) {
        api_error('Unable to prepare permits query.', 500);
    }
    if ($types !== '' && !mysqli_bind_params($stmt, $types, $params)) {
        mysqli_stmt_close($stmt);
        api_error('Unable to bind permits query parameters.', 500);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result === false) {
        mysqli_stmt_close($stmt);
        api_error('Unable to retrieve permits.', 500);
    }

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = permit_from_row($row);
    }
    mysqli_stmt_close($stmt);

    api_json_response(['permits' => $rows]);
}

function handle_get_permit(int $id): void
{
    $claims = api_require_jwt();
    api_require_role($claims, ['staff', 'admin']);

    global $con;
    $stmt = mysqli_prepare($con, 'SELECT p.id, p.reference_no, p.status, p.amount, p.created_at, p.updated_at, r.id AS resident_id, r.first_name, r.last_name, r.email, t.id AS permit_type_id, t.name AS permit_type_name FROM permits p LEFT JOIN residents r ON p.resident_id = r.id LEFT JOIN permit_types t ON p.permit_type_id = t.id WHERE p.id = ? LIMIT 1');
    if ($stmt === false) {
        api_error('Unable to prepare permit query.', 500);
    }
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $permit = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!is_array($permit)) {
        api_error('Permit not found.', 404);
    }

    api_json_response(['permit' => permit_from_row($permit)]);
}

function handle_create_permit(): void
{
    $claims = api_require_jwt();
    api_require_role($claims, ['staff', 'admin']);

    $payload = get_request_data();
    $data = validate_permit_payload($payload, false);

    global $con;
    $referenceNo = generate_reference_no();
    $createdBy = $claims['sub'];

    $stmt = mysqli_prepare($con, 'INSERT INTO permits (resident_id, permit_type_id, reference_no, status, amount, created_by_user) VALUES (?, ?, ?, ?, ?, ?)');
    if ($stmt === false) {
        api_error('Unable to prepare create permit statement.', 500);
    }
    mysqli_stmt_bind_param($stmt, 'iisdss', $data['resident_id'], $data['permit_type_id'], $referenceNo, $data['status'], $data['amount'], $createdBy);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        api_error('Unable to create permit.', 500);
    }

    $id = mysqli_insert_id($con);
    mysqli_stmt_close($stmt);

    insert_integration_event('permit.created', [
        'permit_id' => $id,
        'resident_id' => $data['resident_id'],
        'status' => $data['status'],
    ]);

    http_response_code(201);
    api_json_response(['permit' => permit_from_row_by_id($id)], 201);
}

function handle_update_permit(int $id): void
{
    $claims = api_require_jwt();
    api_require_role($claims, ['admin']);

    $payload = get_request_data();
    $data = validate_permit_payload($payload, true);

    $oldPermit = permit_from_row_by_id($id);

    global $con;
    $stmt = mysqli_prepare($con, 'UPDATE permits SET resident_id = ?, permit_type_id = ?, status = ?, amount = ? WHERE id = ?');
    if ($stmt === false) {
        api_error('Unable to prepare update permit statement.', 500);
    }
    mysqli_stmt_bind_param($stmt, 'iisdi', $data['resident_id'], $data['permit_type_id'], $data['status'], $data['amount'], $id);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        api_error('Unable to update permit.', 500);
    }
    if (mysqli_stmt_affected_rows($stmt) < 1) {
        mysqli_stmt_close($stmt);
        api_error('Permit not found or no change detected.', 404);
    }
    mysqli_stmt_close($stmt);

    if ($oldPermit['status'] !== $data['status']) {
        insert_integration_event('permit.status_changed', [
            'permit_id' => $id,
            'resident_id' => $data['resident_id'],
            'previous_status' => $oldPermit['status'],
            'status' => $data['status'],
        ]);
    }

    api_json_response(['permit' => permit_from_row_by_id($id)]);
}

function handle_patch_permit(int $id): void
{
    $claims = api_require_jwt();
    api_require_role($claims, ['staff', 'admin']);

    $oldPermit = permit_from_row_by_id($id);
    $payload = get_request_data();
    if ($payload === []) {
        api_error('At least one field is required.', 400);
    }

    $allowed = ['status', 'amount', 'permit_type_id'];
    $updates = [];
    $types = '';
    $params = [];
    $newStatus = $oldPermit['status'];

    foreach ($allowed as $field) {
        if (!array_key_exists($field, $payload)) {
            continue;
        }
        $value = $payload[$field];
        if ($field === 'permit_type_id') {
            $value = (int) $value;
            if ($value < 1) {
                api_error('permit_type_id must be a positive integer.', 400);
            }
            $types .= 'i';
        } elseif ($field === 'amount') {
            $value = (float) $value;
            if ($value < 0) {
                api_error('amount must be zero or positive.', 400);
            }
            $types .= 'd';
        } else {
            $value = trim((string) $value);
            if ($value === '') {
                api_error('status cannot be blank.', 400);
            }
            if (!in_array($value, permit_statuses(), true)) {
                api_error('Invalid permit status.', 400);
            }
            if (in_array($value, ['approved', 'rejected', 'ready_for_payment', 'paid', 'issued'], true) && !in_array(strtolower($claims['role'] ?? ''), ['admin'], true)) {
                api_error('Forbidden: only admin can move permit to this status.', 403);
            }
            $newStatus = $value;
            $types .= 's';
        }
        $updates[] = sprintf('%s = ?', $field);
        $params[] = $value;
    }

    if ($updates === []) {
        api_error('No valid fields provided for update.', 400);
    }

    global $con;
    $sql = 'UPDATE permits SET ' . implode(', ', $updates) . ' WHERE id = ?';
    $types .= 'i';
    $params[] = $id;

    $stmt = mysqli_prepare($con, $sql);
    if ($stmt === false) {
        api_error('Unable to prepare patch permit statement.', 500);
    }
    if (!mysqli_bind_params($stmt, $types, $params)) {
        mysqli_stmt_close($stmt);
        api_error('Unable to bind permit update parameters.', 500);
    }
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        api_error('Unable to update permit.', 500);
    }
    if (mysqli_stmt_affected_rows($stmt) < 1) {
        mysqli_stmt_close($stmt);
        api_error('Permit not found or no change detected.', 404);
    }
    mysqli_stmt_close($stmt);

    if ($oldPermit['status'] !== $newStatus) {
        insert_integration_event('permit.status_changed', [
            'permit_id' => $id,
            'resident_id' => $oldPermit['resident']['id'],
            'previous_status' => $oldPermit['status'],
            'status' => $newStatus,
        ]);
    }

    api_json_response(['permit' => permit_from_row_by_id($id)]);
}

function handle_delete_permit(int $id): void
{
    $claims = api_require_jwt();
    api_require_role($claims, ['admin']);

    global $con;
    $stmt = mysqli_prepare($con, 'DELETE FROM permits WHERE id = ?');
    if ($stmt === false) {
        api_error('Unable to prepare delete permit statement.', 500);
    }
    mysqli_stmt_bind_param($stmt, 'i', $id);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        api_error('Unable to delete permit.', 500);
    }
    if (mysqli_stmt_affected_rows($stmt) < 1) {
        mysqli_stmt_close($stmt);
        api_error('Permit not found.', 404);
    }
    mysqli_stmt_close($stmt);

    api_json_response(['message' => 'Permit deleted.']);
}

function validate_permit_payload(array $payload, bool $requireAll): array
{
    $residentId = isset($payload['resident_id']) ? (int) $payload['resident_id'] : 0;
    $permitTypeId = isset($payload['permit_type_id']) ? (int) $payload['permit_type_id'] : 1;
    $amount = isset($payload['amount']) ? (float) $payload['amount'] : 0.0;
    $status = trim((string) ($payload['status'] ?? 'draft'));

    if ($requireAll && $residentId < 1) {
        api_error('resident_id is required.', 400);
    }
    if ($residentId < 1) {
        api_error('resident_id must be a positive integer.', 400);
    }
    if ($permitTypeId < 1) {
        api_error('permit_type_id must be a positive integer.', 400);
    }
    if ($amount < 0) {
        api_error('amount must be zero or positive.', 400);
    }
    if (!in_array($status, permit_statuses(), true)) {
        api_error('Invalid permit status.', 400);
    }

    return [
        'resident_id' => $residentId,
        'permit_type_id' => $permitTypeId,
        'amount' => $amount,
        'status' => $status,
    ];
}

function permit_from_row(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'reference_no' => $row['reference_no'],
        'status' => $row['status'],
        'amount' => (float) $row['amount'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
        'resident' => [
            'id' => (int) $row['resident_id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'],
        ],
        'permit_type' => [
            'id' => (int) $row['permit_type_id'],
            'name' => $row['permit_type_name'],
        ],
    ];
}

function permit_from_row_by_id(int $id): array
{
    global $con;
    $stmt = mysqli_prepare($con, 'SELECT p.id, p.reference_no, p.status, p.amount, p.created_at, p.updated_at, r.id AS resident_id, r.first_name, r.last_name, r.email, t.id AS permit_type_id, t.name AS permit_type_name FROM permits p LEFT JOIN residents r ON p.resident_id = r.id LEFT JOIN permit_types t ON p.permit_type_id = t.id WHERE p.id = ? LIMIT 1');
    if ($stmt === false) {
        api_error('Unable to prepare permit query.', 500);
    }
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $permit = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!is_array($permit)) {
        api_error('Permit not found.', 404);
    }

    return permit_from_row($permit);
}

function permit_statuses(): array
{
    return ['draft', 'submitted', 'approved', 'rejected', 'ready_for_payment', 'paid', 'issued'];
}

function generate_reference_no(): string
{
    return 'PRT-' . strtoupper(bin2hex(random_bytes(4)));
}
