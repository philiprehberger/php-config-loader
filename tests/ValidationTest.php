<?php

declare(strict_types=1);

namespace PhilipRehberger\ConfigLoader\Tests;

use PhilipRehberger\ConfigLoader\Config;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ValidationTest extends TestCase
{
    #[Test]
    public function test_validate_returns_empty_for_valid_config(): void
    {
        $config = new Config([
            'database' => [
                'host' => 'localhost',
                'port' => 3306,
                'debug' => true,
                'rate' => 1.5,
            ],
        ]);

        $violations = $config->validate([
            'database.host' => 'required|string',
            'database.port' => 'required|int',
            'database.debug' => 'required|bool',
            'database.rate' => 'required|float',
        ]);

        $this->assertSame([], $violations);
    }

    #[Test]
    public function test_validate_required_missing_key(): void
    {
        $config = new Config(['name' => 'test']);

        $violations = $config->validate([
            'missing_key' => 'required',
        ]);

        $this->assertCount(1, $violations);
        $this->assertStringContainsString("'missing_key' is required", $violations[0]);
    }

    #[Test]
    public function test_validate_required_null_value(): void
    {
        $config = new Config(['key' => null]);

        $violations = $config->validate([
            'key' => 'required',
        ]);

        $this->assertCount(1, $violations);
        $this->assertStringContainsString("'key' is required", $violations[0]);
    }

    #[Test]
    public function test_validate_string_type_mismatch(): void
    {
        $config = new Config(['port' => 3306]);

        $violations = $config->validate([
            'port' => 'required|string',
        ]);

        $this->assertCount(1, $violations);
        $this->assertStringContainsString("'port' must be a string", $violations[0]);
    }

    #[Test]
    public function test_validate_int_type_mismatch(): void
    {
        $config = new Config(['host' => 'localhost']);

        $violations = $config->validate([
            'host' => 'required|int',
        ]);

        $this->assertCount(1, $violations);
        $this->assertStringContainsString("'host' must be an integer", $violations[0]);
    }

    #[Test]
    public function test_validate_bool_type_mismatch(): void
    {
        $config = new Config(['debug' => 'yes']);

        $violations = $config->validate([
            'debug' => 'required|bool',
        ]);

        $this->assertCount(1, $violations);
        $this->assertStringContainsString("'debug' must be a boolean", $violations[0]);
    }

    #[Test]
    public function test_validate_float_accepts_int(): void
    {
        $config = new Config(['rate' => 42]);

        $violations = $config->validate([
            'rate' => 'required|float',
        ]);

        $this->assertSame([], $violations);
    }

    #[Test]
    public function test_validate_float_type_mismatch(): void
    {
        $config = new Config(['rate' => 'fast']);

        $violations = $config->validate([
            'rate' => 'required|float',
        ]);

        $this->assertCount(1, $violations);
        $this->assertStringContainsString("'rate' must be a float", $violations[0]);
    }

    #[Test]
    public function test_validate_skips_type_check_when_key_missing_and_not_required(): void
    {
        $config = new Config([]);

        $violations = $config->validate([
            'optional' => 'string',
        ]);

        $this->assertSame([], $violations);
    }

    #[Test]
    public function test_validate_multiple_violations(): void
    {
        $config = new Config(['name' => 123]);

        $violations = $config->validate([
            'name' => 'required|string',
            'host' => 'required|string',
        ]);

        $this->assertCount(2, $violations);
        $this->assertStringContainsString("'name' must be a string", $violations[0]);
        $this->assertStringContainsString("'host' is required", $violations[1]);
    }

    #[Test]
    public function test_validate_with_dot_notation_keys(): void
    {
        $config = new Config([
            'database' => [
                'host' => 'localhost',
                'port' => 3306,
            ],
        ]);

        $violations = $config->validate([
            'database.host' => 'required|string',
            'database.port' => 'required|int',
            'database.password' => 'required|string',
        ]);

        $this->assertCount(1, $violations);
        $this->assertStringContainsString("'database.password' is required", $violations[0]);
    }

    #[Test]
    public function test_validate_required_stops_further_checks(): void
    {
        $config = new Config([]);

        $violations = $config->validate([
            'missing' => 'required|string|int',
        ]);

        // Should only report "required", not type mismatches
        $this->assertCount(1, $violations);
        $this->assertStringContainsString("'missing' is required", $violations[0]);
    }
}
