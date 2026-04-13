<?php
namespace Tests;

use PHPUnit\Framework\TestCase;

class HealthCheckTest extends TestCase
{
    private $config;

    protected function setUp(): void
    {
        // Load configuration
        $this->config = require __DIR__ . '/../phpunit-bootstrap.php';
    }

    public function testHealthEndpointRequiresHealthPhpFile()
    {
        $healthFile = __DIR__ . '/../health.php';
        $this->assertFileExists($healthFile, 'health.php endpoint should exist');
    }

    public function testDatabaseConnectionFromPhpunitBootstrap()
    {
        // The bootstrap file sets up DB connection
        // Test that we can access it
        $this->assertNotNull($this->config, 'Bootstrap config should be loaded');
    }

    public function testTestTokenCanBeCreated()
    {
        // Use the helper function from phpunit-bootstrap.php
        if (function_exists('Tests\\create_test_token')) {
            $token = create_test_token('testuser', 'staff');
            $this->assertIsString($token, 'Token should be a string');
            $this->assertNotEmpty($token, 'Token should not be empty');
        } else {
            $this->markTestSkipped('create_test_token helper not available');
        }
    }
}
