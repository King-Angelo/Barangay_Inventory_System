<?php
declare(strict_types=1);

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function api_jwt_secret(): string
{
    $secret = getenv('JWT_SECRET');
    if ($secret === false || $secret === '') {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode(['error' => 'JWT_SECRET is not configured. Add JWT_SECRET to .env.local.']));
    }
    return $secret;
}

function api_jwt_ttl(): int
{
    $ttl = getenv('JWT_TTL_SECONDS');
    if ($ttl === false || $ttl === '') {
        return 3600;
    }
    return max(60, (int)$ttl);
}

function api_admin_usernames(): array
{
    $raw = getenv('API_ADMIN_USERS');
    if ($raw === false || $raw === '') {
        return ['admin'];
    }

    return array_filter(array_map('trim', explode(',', $raw)), static fn($value) => $value !== '');
}

function api_role_for_username(string $username): string
{
    $normalized = strtolower(trim($username));
    $adminUsers = array_map(static fn($value) => strtolower($value), api_admin_usernames());
    return in_array($normalized, $adminUsers, true) ? 'admin' : 'staff';
}

function api_jwt_encode(array $claims): string
{
    $payload = array_merge([
        'iat' => time(),
        'exp' => time() + api_jwt_ttl(),
        'iss' => getenv('JWT_ISSUER') ?: 'barangay-inventory-api',
    ], $claims);

    return JWT::encode($payload, api_jwt_secret(), 'HS256');
}

function api_jwt_decode(string $token): array
{
    try {
        $decoded = JWT::decode($token, new Key(api_jwt_secret(), 'HS256'));
        return json_decode(json_encode($decoded), true) ?: [];
    } catch (Throwable $exception) {
        api_error('Invalid or expired token.', 401);
    }
}

function api_require_jwt(): array
{
    $token = get_bearer_token();
    if ($token === null) {
        api_error('Authorization header with Bearer token required.', 401);
    }
    $claims = api_jwt_decode($token);
    if (!isset($claims['sub']) || !is_string($claims['sub'])) {
        api_error('Invalid token claims.', 401);
    }
    return $claims;
}

function api_require_role(array $claims, array $allowedRoles): void
{
    $role = strtolower((string) ($claims['role'] ?? 'staff'));
    if (!in_array($role, array_map('strtolower', $allowedRoles), true)) {
        api_error('Forbidden: insufficient role.', 403);
    }
}
