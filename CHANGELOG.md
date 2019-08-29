# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

## [1.0.3] - 2026-03-20

### Added
- Expanded test suite with dedicated parser, env resolver, and exception tests

## [1.0.2] - 2026-03-17

### Changed
- Standardized package metadata, README structure, and CI workflow per package guide

## [1.0.1] - 2026-03-16

### Changed
- Standardize composer.json: add type, homepage, scripts

## [1.0.0] - 2026-03-13

### Added

- Load configuration from PHP and JSON files
- Dot-notation key access with typed getters (`string`, `int`, `bool`, `float`, `array`)
- Environment variable substitution (`${VAR}` and `${VAR:default}`)
- Directory loading with automatic file merging
- Deep merge support for combining configurations
- `Config::has()` for checking key existence
- `ConfigException` with descriptive factory methods

[1.0.0]: https://github.com/philiprehberger/php-config-loader/releases/tag/v1.0.0
