<?php
declare(strict_types=1);

function handle_get_residents(): void
{
    $claims = api_require_jwt();
    api_require_role($claims, ['staff', 'admin']);

    global $con;
    $queryParts = ['SELECT id, first_name, last_name, barangay_id, email, status, created_at, updated_at FROM residents'];
    $params = [];
    $types = '';

    $filters = [];
    if (isset($_GET['q']) && trim($_GET['q']) !== '') {
        $q = '%' . trim((string) $_GET['q']) . '%';
        $filters[] = '(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)';
        $params[] = $q;
        $params[] = $q;
        $params[] = $q;
        $types .= 'sss';
    }
    if (isset($_GET['email']) && trim((string) $_GET['email']) !== '') {
        $filters[] = 'email = ?';
        $params[] = trim((string) $_GET['email']);
        $types .= 's';
    }
    if (isset($_GET['barangay_id']) && trim((string) $_GET['barangay_id']) !== '') {
        $filters[] = 'barangay_id = ?';
        $params[] = (int) $_GET['barangay_id'];
        $types .= 'i';
    }

    if ($filters !== []) {
        $queryParts[] = 'WHERE ' . implode(' AND ', $filters);
    }
    $queryParts[] = 'ORDER BY created_at DESC';
    $query = implode(' ', $queryParts);

    $stmt = mysqli_prepare($con, $query);
    if ($stmt === false) {
        api_error('Unable to prepare residents query.', 500);
    }
    if ($types !== '') {
        if (!mysqli_bind_params($stmt, $types, $params)) {
            mysqli_stmt_close($stmt);
            api_error('Unable to bind query parameters.', 500);
        }
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result === false) {
        mysqli_stmt_close($stmt);
        api_error('Unable to retrieve residents.', 500);
    }

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = resident_from_row($row);
    }
    mysqli_stmt_close($stmt);

    api_json_response(['residents' => $rows]);
}

function handle_get_resident(int $id): void
{
    $claims = api_require_jwt();
    api_require_role($claims, ['staff', 'admin']);

    global $con;
    $stmt = mysqli_prepare($con, 'SELECT id, first_name, last_name, barangay_id, email, status, created_at, updated_at FROM residents WHERE id = ? LIMIT 1');
    if ($stmt === false) {
        api_error('Unable to prepare resident query.', 500);
    }
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $resident = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!is_array($resident)) {
        api_error('Resident not found.', 404);
    }

    api_json_response(['resident' => resident_from_row($resident)]);
}

function handle_create_resident(): void
{
    $claims = api_require_jwt();
    api_require_role($claims, ['staff', 'admin']);

    $payload = get_request_data();
    $data = validate_resident_payload($payload, false);

    global $con;
    $stmt = mysqli_prepare($con, 'SELECT 1 FROM residents WHERE barangay_id = ? AND email = ? LIMIT 1');
    if ($stmt === false) {
        api_error('Unable to prepare duplicate check.', 500);
    }
    mysqli_stmt_bind_param($stmt, 'is', $data['barangay_id'], $data['email']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_fetch_assoc($result)) {
        mysqli_stmt_close($stmt);
        api_error('Resident with that barangay and email already exists.', 409);
    }
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($con, 'INSERT INTO residents (first_name, last_name, barangay_id, email, status) VALUES (?, ?, ?, ?, ?)');
    if ($stmt === false) {
        api_error('Unable to prepare create resident statement.', 500);
    }
    mysqli_stmt_bind_param($stmt, 'ssiss', $data['first_name'], $data['last_name'], $data['barangay_id'], $data['email'], $data['status']);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        api_error('Unable to create resident.', 500);
    }

    $id = mysqli_insert_id($con);
    mysqli_stmt_close($stmt);

    http_response_code(201);
    api_json_response(['resident' => handle_get_resident_response($id)], 201);
}

function handle_update_resident(int $id): void
{
    $claims = api_require_jwt();
    api_require_role($claims, ['staff', 'admin']);

    $payload = get_request_data();
    $data = validate_resident_payload($payload, true);

    global $con;
    $stmt = mysqli_prepare($con, 'UPDATE residents SET first_name = ?, last_name = ?, barangay_id = ?, email = ?, status = ? WHERE id = ?');
    if ($stmt === false) {
        api_error('Unable to prepare update resident statement.', 500);
    }
    mysqli_stmt_bind_param($stmt, 'ssissi', $data['first_name'], $data['last_name'], $data['barangay_id'], $data['email'], $data['status'], $id);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        api_error('Unable to update resident.', 500);
    }
    if (mysqli_stmt_affected_rows($stmt) < 1) {
        mysqli_stmt_close($stmt);
        api_error('Resident not found or no change detected.', 404);
    }
    mysqli_stmt_close($stmt);

    api_json_response(['resident' => handle_get_resident_response($id)]);
}

function handle_patch_resident(int $id): void
{
    $claims = api_require_jwt();
    api_require_role($claims, ['staff', 'admin']);

    $payload = get_request_data();
    if ($payload === []) {
        api_error('At least one field is required.', 400);
    }

    $allowed = ['first_name', 'last_name', 'barangay_id', 'email', 'status'];
    $updates = [];
    $types = '';
    $params = [];

    foreach ($allowed as $field) {
        if (array_key_exists($field, $payload)) {
            $value = $payload[$field];
            if ($field === 'barangay_id') {
                $value = (int) $value;
                if ($value < 1) {
                    api_error('barangay_id must be a positive integer.', 400);
                }
                $types .= 'i';
            } else {
                $value = trim((string) $value);
                if ($value === '') {
                    api_error(sprintf('%s cannot be blank.', $field), 400);
                }
                if ($field === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    api_error('A valid email address is required.', 400);
                }
                if ($field === 'status' && !in_array($value, ['active', 'archived'], true)) {
                    api_error('Status must be active or archived.', 400);
                }
                $types .= 's';
            }
            $updates[] = sprintf('%s = ?', $field);
            $params[] = $value;
        }
    }

    if ($updates === []) {
        api_error('No valid fields provided for update.', 400);
    }

    if (isset($payload['barangay_id'], $payload['email'])) {
        global $con;
        $uniqueStmt = mysqli_prepare($con, 'SELECT 1 FROM residents WHERE barangay_id = ? AND email = ? AND id <> ? LIMIT 1');
        if ($uniqueStmt === false) {
            api_error('Unable to prepare duplicate check.', 500);
        }
        mysqli_stmt_bind_param($uniqueStmt, 'isi', $payload['barangay_id'], $payload['email'], $id);
        mysqli_stmt_execute($uniqueStmt);
        $result = mysqli_stmt_get_result($uniqueStmt);
        if (mysqli_fetch_assoc($result)) {
            mysqli_stmt_close($uniqueStmt);
            api_error('Resident with that barangay and email already exists.', 409);
        }
        mysqli_stmt_close($uniqueStmt);
    }

    global $con;
    $sql = 'UPDATE residents SET ' . implode(', ', $updates) . ' WHERE id = ?';
    $types .= 'i';
    $params[] = $id;

    $stmt = mysqli_prepare($con, $sql);
    if ($stmt === false) {
        api_error('Unable to prepare patch resident statement.', 500);
    }
    if (!mysqli_bind_params($stmt, $types, $params)) {
        mysqli_stmt_close($stmt);
        api_error('Unable to bind update parameters.', 500);
    }
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        api_error('Unable to update resident.', 500);
    }
    if (mysqli_stmt_affected_rows($stmt) < 1) {
        mysqli_stmt_close($stmt);
        api_error('Resident not found or no change detected.', 404);
    }
    mysqli_stmt_close($stmt);

    api_json_response(['resident' => handle_get_resident_response($id)]);
}

function handle_delete_resident(int $id): void
{
    $claims = api_require_jwt();
    api_require_role($claims, ['admin']);

    global $con;
    $stmt = mysqli_prepare($con, 'DELETE FROM residents WHERE id = ?');
    if ($stmt === false) {
        api_error('Unable to prepare delete resident statement.', 500);
    }
    mysqli_stmt_bind_param($stmt, 'i', $id);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        api_error('Unable to delete resident.', 500);
    }
    if (mysqli_stmt_affected_rows($stmt) < 1) {
        mysqli_stmt_close($stmt);
        api_error('Resident not found.', 404);
    }
    mysqli_stmt_close($stmt);

    api_json_response(['message' => 'Resident deleted.']);
}

function validate_resident_payload(array $payload, bool $requireAll): array
{
    $firstName = trim((string) ($payload['first_name'] ?? $payload['firstName'] ?? ''));
    $lastName = trim((string) ($payload['last_name'] ?? $payload['lastName'] ?? ''));
    $email = trim((string) ($payload['email'] ?? ''));
    $barangayId = isset($payload['barangay_id']) ? (int) $payload['barangay_id'] : 0;
    $status = trim((string) ($payload['status'] ?? 'active'));

    if ($requireAll) {
        if ($firstName === '' || $lastName === '' || $email === '' || $barangayId < 1) {
            api_error('first_name, last_name, email and barangay_id are required.', 400);
        }
    }

    if ($firstName === '') {
        api_error('first_name is required.', 400);
    }
    if ($lastName === '') {
        api_error('last_name is required.', 400);
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        api_error('A valid email address is required.', 400);
    }
    if ($barangayId < 1) {
        api_error('barangay_id must be a positive integer.', 400);
    }
    if (!in_array($status, ['active', 'archived'], true)) {
        api_error('status must be active or archived.', 400);
    }

    return [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'barangay_id' => $barangayId,
        'status' => $status,
    ];
}

function resident_from_row(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name'],
        'barangay_id' => (int) $row['barangay_id'],
        'email' => $row['email'],
        'status' => $row['status'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
    ];
}

function handle_get_resident_response(int $id): array
{
    global $con;
    $stmt = mysqli_prepare($con, 'SELECT id, first_name, last_name, barangay_id, email, status, created_at, updated_at FROM residents WHERE id = ? LIMIT 1');
    if ($stmt === false) {
        api_error('Unable to prepare resident query.', 500);
    }
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $resident = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!is_array($resident)) {
        api_error('Resident not found.', 404);
    }

    return resident_from_row($resident);
}
