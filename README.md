# PHP Config Loader

[![Tests](https://github.com/philiprehberger/php-config-loader/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/php-config-loader/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/philiprehberger/php-config-loader.svg)](https://packagist.org/packages/philiprehberger/php-config-loader)
[![License](https://img.shields.io/github/license/philiprehberger/php-config-loader)](LICENSE)

Load configuration from JSON and PHP files with environment variable substitution.

## Requirements

- PHP 8.2+

## Installation

```bash
composer require philiprehberger/php-config-loader
```

## Usage

### Loading a Single File

```php
use PhilipRehberger\ConfigLoader\ConfigLoader;

// Load a PHP config file
$config = ConfigLoader::load(__DIR__ . '/config/app.php');

// Load a JSON config file
$config = ConfigLoader::load(__DIR__ . '/config/database.json');
```

**PHP config file** (`app.php`):
```php
<?php
return [
    'name' => 'MyApp',
    'debug' => true,
    'version' => '1.0.0',
];
```

**JSON config file** (`database.json`):
```json
{
    "host": "localhost",
    "port": 3306,
    "credentials": {
        "user": "admin",
        "pass": "secret"
    }
}
```

### Dot-Notation Access

```php
$config->get('credentials.user');          // 'admin'
$config->get('missing.key', 'default');    // 'default'
```

### Typed Getters

```php
$config->string('name');          // string, default ''
$config->int('port', 3306);       // int, default 0
$config->bool('debug', false);    // bool, default false
$config->float('rate', 1.0);      // float, default 0.0
$config->array('tags', []);       // array, default []
```

### Checking Key Existence

```php
$config->has('credentials.user');  // true
$config->has('nonexistent');       // false
```

### Environment Variable Substitution

Config values containing `${VAR}` are resolved from environment variables at load time. Use `${VAR:default}` to provide a fallback.

```json
{
    "host": "${DB_HOST:localhost}",
    "api_key": "${API_KEY}"
}
```

```php
putenv('DB_HOST=production.example.com');
$config = ConfigLoader::load('config.json');

$config->get('host');     // 'production.example.com'
$config->get('api_key');  // '' (env var not set, no default)
```

### Loading a Directory

Load all `.php` and `.json` files from a directory. Each file's basename (without extension) becomes a top-level key.

```php
// config/
//   app.php      -> keyed as 'app'
//   database.json -> keyed as 'database'

$config = ConfigLoader::loadDirectory(__DIR__ . '/config');

$config->get('app.name');           // from app.php
$config->get('database.host');      // from database.json
```

### Deep Merge

Combine two configurations with deep merging. Values from the second config override the first; nested arrays are merged recursively.

```php
$base = ConfigLoader::load('config/defaults.php');
$local = ConfigLoader::load('config/local.php');

$merged = $base->merge($local);
```

## API

| Method | Return Type | Description |
|---|---|---|
| `ConfigLoader::load(string $path)` | `Config` | Load a single PHP or JSON config file |
| `ConfigLoader::loadDirectory(string $dir)` | `Config` | Load all config files from a directory |
| `Config::get(string $key, mixed $default = null)` | `mixed` | Get value by dot-notation key |
| `Config::string(string $key, string $default = '')` | `string` | Get string value |
| `Config::int(string $key, int $default = 0)` | `int` | Get integer value |
| `Config::bool(string $key, bool $default = false)` | `bool` | Get boolean value |
| `Config::float(string $key, float $default = 0.0)` | `float` | Get float value |
| `Config::array(string $key, array $default = [])` | `array` | Get array value |
| `Config::has(string $key)` | `bool` | Check if key exists |
| `Config::all()` | `array` | Get all config data |
| `Config::merge(Config $other)` | `Config` | Deep merge with another config |

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

## License

MIT
