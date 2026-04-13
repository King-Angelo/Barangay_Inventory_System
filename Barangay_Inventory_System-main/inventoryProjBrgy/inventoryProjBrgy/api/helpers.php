<?php
declare(strict_types=1);

function api_json_response(mixed $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_error(string $message, int $status = 400): void
{
    api_json_response(['error' => $message], $status);
}

function get_request_data(): array
{
    $rawBody = file_get_contents('php://input');
    if ($rawBody !== false && $rawBody !== '') {
        $decoded = json_decode($rawBody, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    return $_POST;
}

function get_authorization_header(): ?string
{
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        return trim((string) $_SERVER['HTTP_AUTHORIZATION']);
    }
    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        return trim((string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (!empty($headers['Authorization'])) {
            return trim((string) $headers['Authorization']);
        }
    }
    return null;
}

function get_bearer_token(): ?string
{
    $header = get_authorization_header();
    if ($header === null) {
        return null;
    }
    if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
        return trim($matches[1]);
    }
    return null;
}

function mysqli_bind_params(mysqli_stmt $stmt, string $types, array $params): bool
{
    $refs = [];
    foreach ($params as $key => $value) {
        $refs[$key] = &$params[$key];
    }
    return mysqli_stmt_bind_param($stmt, $types, ...$refs);
}

function normalize_path(string $path): string
{
    $path = trim($path);
    $path = preg_replace('#/+#', '/', $path);
    if ($path === '') {
        return '/';
    }
    return '/' . ltrim($path, '/');
}

function api_get_route_path(): string
{
    if (!empty($_SERVER['PATH_INFO'])) {
        return normalize_path($_SERVER['PATH_INFO']);
    }

    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $path = $requestUri;

    if ($scriptName !== '' && str_starts_with($path, $scriptName)) {
        $path = substr($path, strlen($scriptName));
    } else {
        $scriptDir = dirname($scriptName);
        if ($scriptDir !== '/' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir));
        }
    }

    if ($path === false) {
        $path = '/';
    }

    return normalize_path($path);
}

function verify_password(string $plainPassword, string $storedPassword): bool
{
    if ($storedPassword !== '' && (str_starts_with($storedPassword, '$2y$') || str_starts_with($storedPassword, '$2a$') || str_starts_with($storedPassword, '$argon2i$') || str_starts_with($storedPassword, '$argon2id$'))) {
        return password_verify($plainPassword, $storedPassword);
    }
    return hash_equals($storedPassword, $plainPassword);
}

function insert_integration_event(string $eventType, array $payload): int
{
    global $con;
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return 0;
    }

    $status = 'pending';
    $stmt = mysqli_prepare($con, 'INSERT INTO integration_events (event_type, payload, status) VALUES (?, ?, ?)');
    if ($stmt === false) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 'sss', $eventType, $json, $status);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return 0;
    }

    $id = mysqli_stmt_insert_id($stmt);
    mysqli_stmt_close($stmt);
    return $id;
}

function api_send_email(string $to, string $subject, string $body): bool
{
    $from = getenv('MAIL_FROM') ?: 'noreply@localhost';
    $headers = "From: {$from}\r\nContent-Type: text/plain; charset=utf-8\r\n";
    return mail($to, $subject, $body, $headers);
}

function api_build_permit_email_subject(string $eventType): string
{
    $prefix = getenv('MAIL_SUBJECT_PREFIX') ?: '[Barangay Clearance]';
    return match ($eventType) {
        'permit.created' => "{$prefix} Permit created",
        'permit.status_changed' => "{$prefix} Permit status update",
        'permit.issued' => "{$prefix} Permit issued",
        default => "{$prefix} Notification",
    };
}

function api_build_permit_email_body(string $eventType, array $payload): string
{
    $lines = [];
    $lines[] = "Event: {$eventType}";
    $lines[] = "Permit ID: " . ($payload['permit_id'] ?? 'unknown');
    if (isset($payload['status'])) {
        $lines[] = "Status: " . $payload['status'];
    }
    if (isset($payload['previous_status'])) {
        $lines[] = "Previous status: " . $payload['previous_status'];
    }
    $lines[] = "";
    $lines[] = "Thank you for using the Barangay clearance system.";
    return implode("\n", $lines);
}
