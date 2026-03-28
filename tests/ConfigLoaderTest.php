<?php

declare(strict_types=1);

namespace PhilipRehberger\ConfigLoader\Tests;

use PhilipRehberger\ConfigLoader\Config;
use PhilipRehberger\ConfigLoader\ConfigLoader;
use PhilipRehberger\ConfigLoader\Exceptions\ConfigException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConfigLoaderTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/config_loader_test_'.uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tmpDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.'/'.$item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    #[Test]
    public function test_load_php_config_file(): void
    {
        $file = $this->tmpDir.'/app.php';
        file_put_contents($file, "<?php\nreturn ['name' => 'MyApp', 'version' => '1.0.0'];");

        $config = ConfigLoader::load($file);

        $this->assertSame('MyApp', $config->get('name'));
        $this->assertSame('1.0.0', $config->get('version'));
    }

    #[Test]
    public function test_load_json_config_file(): void
    {
        $file = $this->tmpDir.'/app.json';
        file_put_contents($file, json_encode(['name' => 'MyApp', 'debug' => true]));

        $config = ConfigLoader::load($file);

        $this->assertSame('MyApp', $config->get('name'));
        $this->assertTrue($config->get('debug'));
    }

    #[Test]
    public function test_unsupported_format_throws(): void
    {
        $file = $this->tmpDir.'/config.xml';
        file_put_contents($file, '<config><key>value</key></config>');

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage("Unsupported config format: 'xml'");

        ConfigLoader::load($file);
    }

    #[Test]
    public function test_missing_file_throws(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Config file not found');

        ConfigLoader::load($this->tmpDir.'/nonexistent.json');
    }

    #[Test]
    public function test_dot_notation_access(): void
    {
        $file = $this->tmpDir.'/db.json';
        file_put_contents($file, json_encode([
            'database' => [
                'host' => 'localhost',
                'credentials' => [
                    'user' => 'admin',
                    'pass' => 'secret',
                ],
            ],
        ]));

        $config = ConfigLoader::load($file);

        $this->assertSame('localhost', $config->get('database.host'));
        $this->assertSame('admin', $config->get('database.credentials.user'));
        $this->assertSame('secret', $config->get('database.credentials.pass'));
    }

    #[Test]
    public function test_typed_getters_string_int_bool_float_array(): void
    {
        $file = $this->tmpDir.'/types.php';
        file_put_contents($file, "<?php\nreturn [
            'name' => 'App',
            'port' => 8080,
            'debug' => true,
            'rate' => 3.14,
            'tags' => ['web', 'api'],
        ];");

        $config = ConfigLoader::load($file);

        $this->assertSame('App', $config->string('name'));
        $this->assertSame(8080, $config->int('port'));
        $this->assertTrue($config->bool('debug'));
        $this->assertSame(3.14, $config->float('rate'));
        $this->assertSame(['web', 'api'], $config->array('tags'));
    }

    #[Test]
    public function test_has_returns_correct_boolean(): void
    {
        $file = $this->tmpDir.'/check.json';
        file_put_contents($file, json_encode([
            'exists' => 'yes',
            'nested' => ['key' => 'value'],
        ]));

        $config = ConfigLoader::load($file);

        $this->assertTrue($config->has('exists'));
        $this->assertTrue($config->has('nested.key'));
        $this->assertFalse($config->has('missing'));
        $this->assertFalse($config->has('nested.missing'));
    }

    #[Test]
    public function test_default_values_returned_for_missing_keys(): void
    {
        $file = $this->tmpDir.'/empty.json';
        file_put_contents($file, json_encode(['key' => 'value']));

        $config = ConfigLoader::load($file);

        $this->assertSame('fallback', $config->get('missing', 'fallback'));
        $this->assertSame('default', $config->string('missing', 'default'));
        $this->assertSame(42, $config->int('missing', 42));
        $this->assertTrue($config->bool('missing', true));
        $this->assertSame(1.5, $config->float('missing', 1.5));
        $this->assertSame(['a'], $config->array('missing', ['a']));
    }

    #[Test]
    public function test_load_directory_merges_files(): void
    {
        file_put_contents(
            $this->tmpDir.'/app.json',
            json_encode(['name' => 'MyApp'])
        );
        file_put_contents(
            $this->tmpDir.'/database.php',
            "<?php\nreturn ['host' => 'localhost', 'port' => 3306];"
        );

        $config = ConfigLoader::loadDirectory($this->tmpDir);

        $this->assertSame('MyApp', $config->get('app.name'));
        $this->assertSame('localhost', $config->get('database.host'));
        $this->assertSame(3306, $config->get('database.port'));
    }

    #[Test]
    public function test_env_variable_substitution(): void
    {
        putenv('CONFIG_TEST_HOST=production.example.com');

        $file = $this->tmpDir.'/env.json';
        file_put_contents($file, json_encode(['host' => '${CONFIG_TEST_HOST}']));

        $config = ConfigLoader::load($file);

        $this->assertSame('production.example.com', $config->get('host'));

        putenv('CONFIG_TEST_HOST');
    }

    #[Test]
    public function test_env_variable_with_default(): void
    {
        putenv('CONFIG_TEST_UNDEFINED_VAR');

        $file = $this->tmpDir.'/env_default.json';
        file_put_contents($file, json_encode(['host' => '${CONFIG_TEST_UNDEFINED_VAR:localhost}']));

        $config = ConfigLoader::load($file);

        $this->assertSame('localhost', $config->get('host'));
    }

    #[Test]
    public function test_deep_merge(): void
    {
        $base = new Config([
            'database' => [
                'host' => 'localhost',
                'port' => 3306,
                'options' => ['timeout' => 30],
            ],
            'app' => 'base',
        ]);

        $override = new Config([
            'database' => [
                'host' => 'production.example.com',
                'options' => ['timeout' => 60, 'retry' => true],
            ],
            'cache' => 'redis',
        ]);

        $merged = $base->merge($override);

        $this->assertSame('production.example.com', $merged->get('database.host'));
        $this->assertSame(3306, $merged->get('database.port'));
        $this->assertSame(60, $merged->get('database.options.timeout'));
        $this->assertTrue($merged->get('database.options.retry'));
        $this->assertSame('base', $merged->get('app'));
        $this->assertSame('redis', $merged->get('cache'));
    }

    #[Test]
    public function test_merge_with_overlapping_scalar_keys(): void
    {
        $base = new Config(['name' => 'original', 'port' => 3306]);
        $override = new Config(['name' => 'overridden', 'port' => 5432]);

        $merged = $base->merge($override);

        $this->assertSame('overridden', $merged->get('name'));
        $this->assertSame(5432, $merged->get('port'));
    }

    #[Test]
    public function test_merge_with_non_overlapping_keys(): void
    {
        $base = new Config(['alpha' => 1]);
        $override = new Config(['beta' => 2]);

        $merged = $base->merge($override);

        $this->assertSame(1, $merged->get('alpha'));
        $this->assertSame(2, $merged->get('beta'));
    }

    #[Test]
    public function test_merge_with_nested_configs(): void
    {
        $base = new Config([
            'services' => [
                'mail' => ['driver' => 'smtp', 'host' => 'mail.example.com'],
                'queue' => ['driver' => 'sync'],
            ],
        ]);

        $override = new Config([
            'services' => [
                'mail' => ['host' => 'smtp.production.com', 'port' => 587],
                'cache' => ['driver' => 'redis'],
            ],
        ]);

        $merged = $base->merge($override);

        $this->assertSame('smtp', $merged->get('services.mail.driver'));
        $this->assertSame('smtp.production.com', $merged->get('services.mail.host'));
        $this->assertSame(587, $merged->get('services.mail.port'));
        $this->assertSame('sync', $merged->get('services.queue.driver'));
        $this->assertSame('redis', $merged->get('services.cache.driver'));
    }

    #[Test]
    public function test_merge_returns_new_instance(): void
    {
        $base = new Config(['key' => 'base']);
        $override = new Config(['key' => 'override']);

        $merged = $base->merge($override);

        $this->assertNotSame($base, $merged);
        $this->assertNotSame($override, $merged);
        $this->assertSame('base', $base->get('key'));
        $this->assertSame('override', $override->get('key'));
        $this->assertSame('override', $merged->get('key'));
    }

    #[Test]
    public function test_flatten_simple_nested(): void
    {
        $config = new Config([
            'database' => [
                'host' => 'localhost',
                'port' => 3306,
            ],
        ]);

        $flat = $config->flatten();

        $this->assertSame([
            'database.host' => 'localhost',
            'database.port' => 3306,
        ], $flat);
    }

    #[Test]
    public function test_flatten_deeply_nested_three_plus_levels(): void
    {
        $config = new Config([
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => 'deep_value',
                        'other' => true,
                    ],
                    'sibling' => 42,
                ],
            ],
            'top' => 'flat',
        ]);

        $flat = $config->flatten();

        $this->assertSame([
            'level1.level2.level3.level4' => 'deep_value',
            'level1.level2.level3.other' => true,
            'level1.level2.sibling' => 42,
            'top' => 'flat',
        ], $flat);
    }

    #[Test]
    public function test_flatten_with_custom_separator(): void
    {
        $config = new Config([
            'database' => [
                'host' => 'localhost',
            ],
        ]);

        $flat = $config->flatten('/');

        $this->assertSame(['database/host' => 'localhost'], $flat);
    }

    #[Test]
    public function test_flatten_empty_config(): void
    {
        $config = new Config([]);

        $this->assertSame([], $config->flatten());
    }

    #[Test]
    public function test_keys_returns_top_level_keys(): void
    {
        $config = new Config([
            'app' => 'MyApp',
            'database' => ['host' => 'localhost'],
            'cache' => 'redis',
        ]);

        $this->assertSame(['app', 'database', 'cache'], $config->keys());
    }

    #[Test]
    public function test_keys_returns_empty_for_empty_config(): void
    {
        $config = new Config([]);

        $this->assertSame([], $config->keys());
    }
}
