# Terminus Aliases Plugin

[![CircleCI](https://circleci.com/gh/pantheon-systems/terminus-aliases-plugin.svg?style=shield)](https://circleci.com/gh/pantheon-systems/terminus-aliases-plugin)
[![Terminus v1.x Compatible](https://img.shields.io/badge/terminus-v1.x-green.svg)](https://github.com/pantheon-systems/terminus-secrets-plugin/tree/1.x)

Adds prototype command to replace the `terminus aliases` command.

## Configuration

These commands require no configuration.

## Usage
```
$ terminus alpha:aliases
```
This command writes Drush aliases for both Drush 8 and Drush 9.

## Installation
To install this plugin place it in `~/.terminus/plugins/`.

On Mac OS/Linux:
```
mkdir -p ~/.terminus/plugins
cd ~/.terminus/plugins
git clone git@github.com:pantheon-systems/terminus-aliases-plugin.git
cd terminus-aliases-plugin
composer install --no-dev
```

This will be replaced with a `composer create-project` method in the future.

## Testing
This example project includes four testing targets:

* `composer lint`: Syntax-check all php source files.
* `composer cs`: Code-style check.
* `composer unit`: Run unit tests with phpunit
* `composer functional`: Run functional test with bats

To run all tests together, use `composer test`.

Note that prior to running the tests, you should first run:
* `composer install`
* `composer install-tools`

## Help
Run `terminus help alpha:aliases` for help.
