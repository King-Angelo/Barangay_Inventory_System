<?php

declare(strict_types=1);

namespace App\Tests;

use App\Api\JwtIssuer;
use PHPUnit\Framework\TestCase;

final class JwtRoundTripTest extends TestCase
{
	public function testIssueAndDecodeContainsSubAndRole(): void
	{
		$user = [
			'id' => 42,
			'UserName' => 'staff_dev',
			'role' => 'staff',
		];
		$token = JwtIssuer::issue($user, 3600);
		$this->assertNotSame('', $token);

		$claims = JwtIssuer::decode($token);
		$this->assertSame('42', $claims['sub']);
		$this->assertSame('staff', $claims['role']);
		$this->assertSame('staff_dev', $claims['username']);
	}
}
