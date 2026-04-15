<?php

declare(strict_types=1);

namespace App\Api;

/** Authenticated API user (from JWT + users row). */
final class AuthContext
{
	public function __construct(
		public readonly int $userId,
		public readonly string $role,
		public readonly string $username,
		public readonly ?int $barangayId,
	) {
	}

	public function isAdmin(): bool
	{
		return $this->role === 'admin';
	}
}
