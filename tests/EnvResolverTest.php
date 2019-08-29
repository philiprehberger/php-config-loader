<?php

declare(strict_types=1);

namespace PhilipRehberger\ConfigLoader\Tests;

use PhilipRehberger\ConfigLoader\EnvResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EnvResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('ENV_RESOLVER_TEST_A');
        putenv('ENV_RESOLVER_TEST_B');
        putenv('ENV_RESOLVER_TEST_C');
    }

    #[Test]
    public function test_resolves_single_env_variable(): void
    {
        putenv('ENV_RESOLVER_TEST_A=hello');

        $result = EnvResolver::resolve(['key' => '${ENV_RESOLVER_TEST_A}']);

        $this->assertSame(['key' => 'hello'], $result);
    }

    #[Test]
    public function test_resolves_multiple_placeholders_in_single_value(): void
    {
        putenv('ENV_RESOLVER_TEST_A=localhost');
        putenv('ENV_RESOLVER_TEST_B=3306');

        $result = EnvResolver::resolve(['dsn' => '${ENV_RESOLVER_TEST_A}:${ENV_RESOLVER_TEST_B}']);

        $this->assertSame(['dsn' => 'localhost:3306'], $result);
    }

    #[Test]
    public function test_missing_env_variable_resolves_to_empty_string(): void
    {
        putenv('ENV_RESOLVER_TEST_A');

        $result = EnvResolver::resolve(['key' => '${ENV_RESOLVER_TEST_A}']);

        $this->assertSame(['key' => ''], $result);
    }

    #[Test]
    public function test_missing_env_variable_uses_default_value(): void
    {
        putenv('ENV_RESOLVER_TEST_A');

        $result = EnvResolver::resolve(['key' => '${ENV_RESOLVER_TEST_A:fallback}']);

        $this->assertSame(['key' => 'fallback'], $result);
    }

    #[Test]
    public function test_set_env_variable_ignores_default_value(): void
    {
        putenv('ENV_RESOLVER_TEST_A=actual');

        $result = EnvResolver::resolve(['key' => '${ENV_RESOLVER_TEST_A:fallback}']);

        $this->assertSame(['key' => 'actual'], $result);
    }

    #[Test]
    public function test_default_value_can_be_empty_string(): void
    {
        putenv('ENV_RESOLVER_TEST_A');

        $result = EnvResolver::resolve(['key' => '${ENV_RESOLVER_TEST_A:}']);

        $this->assertSame(['key' => ''], $result);
    }

    #[Test]
    public function test_placeholder_embedded_in_text(): void
    {
        putenv('ENV_RESOLVER_TEST_A=world');

        $result = EnvResolver::resolve(['key' => 'hello ${ENV_RESOLVER_TEST_A}!']);

        $this->assertSame(['key' => 'hello world!'], $result);
    }

    #[Test]
    public function test_multiple_placeholders_with_defaults(): void
    {
        putenv('ENV_RESOLVER_TEST_A');
        putenv('ENV_RESOLVER_TEST_B');

        $result = EnvResolver::resolve(['key' => '${ENV_RESOLVER_TEST_A:host}:${ENV_RESOLVER_TEST_B:5432}']);

        $this->assertSame(['key' => 'host:5432'], $result);
    }

    #[Test]
    public function test_resolves_nested_array_values(): void
    {
        putenv('ENV_RESOLVER_TEST_A=prod');

        $result = EnvResolver::resolve([
            'database' => [
                'host' => '${ENV_RESOLVER_TEST_A}',
                'nested' => [
                    'deep' => '${ENV_RESOLVER_TEST_A}',
                ],
            ],
        ]);

        $this->assertSame([
            'database' => [
                'host' => 'prod',
                'nested' => [
                    'deep' => 'prod',
                ],
            ],
        ], $result);
    }

    #[Test]
    public function test_non_string_values_pass_through_unchanged(): void
    {
        $result = EnvResolver::resolve([
            'int' => 42,
            'float' => 3.14,
            'bool' => true,
            'null' => null,
        ]);

        $this->assertSame([
            'int' => 42,
            'float' => 3.14,
            'bool' => true,
            'null' => null,
        ], $result);
    }

    #[Test]
    public function test_string_without_placeholder_passes_through(): void
    {
        $result = EnvResolver::resolve(['key' => 'no placeholders here']);

        $this->assertSame(['key' => 'no placeholders here'], $result);
    }

    #[Test]
    public function test_malformed_placeholder_missing_closing_brace_passes_through(): void
    {
        $result = EnvResolver::resolve(['key' => '${UNCLOSED']);

        $this->assertSame(['key' => '${UNCLOSED'], $result);
    }

    #[Test]
    public function test_dollar_sign_without_brace_passes_through(): void
    {
        $result = EnvResolver::resolve(['key' => '$NOT_A_PLACEHOLDER']);

        $this->assertSame(['key' => '$NOT_A_PLACEHOLDER'], $result);
    }

    #[Test]
    public function test_empty_braces_pass_through(): void
    {
        $result = EnvResolver::resolve(['key' => '${}']);

        $this->assertSame(['key' => '${}'], $result);
    }

    #[Test]
    public function test_resolves_empty_array(): void
    {
        $result = EnvResolver::resolve([]);

        $this->assertSame([], $result);
    }

    #[Test]
    public function test_mixed_resolved_and_unresolved_in_same_array(): void
    {
        putenv('ENV_RESOLVER_TEST_A=resolved');

        $result = EnvResolver::resolve([
            'dynamic' => '${ENV_RESOLVER_TEST_A}',
            'static' => 'plain value',
            'number' => 100,
        ]);

        $this->assertSame([
            'dynamic' => 'resolved',
            'static' => 'plain value',
            'number' => 100,
        ], $result);
    }
}
