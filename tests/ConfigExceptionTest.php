<?php

declare(strict_types=1);

namespace PhilipRehberger\ConfigLoader\Tests;

use PhilipRehberger\ConfigLoader\Exceptions\ConfigException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ConfigExceptionTest extends TestCase
{
    #[Test]
    public function test_extends_runtime_exception(): void
    {
        $exception = ConfigException::fileNotFound('/some/path');

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    #[Test]
    public function test_file_not_found_message_contains_path(): void
    {
        $exception = ConfigException::fileNotFound('/etc/config/app.json');

        $this->assertSame("Config file not found: '/etc/config/app.json'.", $exception->getMessage());
    }

    #[Test]
    public function test_directory_not_found_message_contains_dir(): void
    {
        $exception = ConfigException::directoryNotFound('/etc/config');

        $this->assertSame("Config directory not found: '/etc/config'.", $exception->getMessage());
    }

    #[Test]
    public function test_unsupported_format_message_contains_extension(): void
    {
        $exception = ConfigException::unsupportedFormat('yaml');

        $this->assertSame("Unsupported config format: 'yaml'. Supported: php, json.", $exception->getMessage());
    }

    #[Test]
    public function test_invalid_format_message_contains_path_and_reason(): void
    {
        $exception = ConfigException::invalidFormat('/config/app.php', 'PHP config must return an array');

        $this->assertSame("Invalid config file '/config/app.php': PHP config must return an array.", $exception->getMessage());
    }

    #[Test]
    public function test_read_failed_message_contains_path(): void
    {
        $exception = ConfigException::readFailed('/config/broken.json');

        $this->assertSame("Failed to read config file: '/config/broken.json'.", $exception->getMessage());
    }

    #[Test]
    public function test_all_factory_methods_return_config_exception_instance(): void
    {
        $this->assertInstanceOf(ConfigException::class, ConfigException::fileNotFound('/path'));
        $this->assertInstanceOf(ConfigException::class, ConfigException::directoryNotFound('/dir'));
        $this->assertInstanceOf(ConfigException::class, ConfigException::unsupportedFormat('xml'));
        $this->assertInstanceOf(ConfigException::class, ConfigException::invalidFormat('/path', 'reason'));
        $this->assertInstanceOf(ConfigException::class, ConfigException::readFailed('/path'));
    }

    #[Test]
    public function test_unsupported_format_with_empty_extension(): void
    {
        $exception = ConfigException::unsupportedFormat('');

        $this->assertSame("Unsupported config format: ''. Supported: php, json.", $exception->getMessage());
    }

    #[Test]
    public function test_file_not_found_with_relative_path(): void
    {
        $exception = ConfigException::fileNotFound('config/app.json');

        $this->assertSame("Config file not found: 'config/app.json'.", $exception->getMessage());
    }
}
