# Migrate controller for Yii 2

Automatically scan for new migrations in all available modules.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require webvimark/yii2-migrate
```

or add

```json
"webvimark/yii2-migrate": "^1.0"
```

## Usage

To use this extension, simply add the following code in your application configuration (console.php):

```php
'controllerMap' => [
    'migrate' => [
        'class' => 'webvimark\migrate\Controller',
        'configs' => [
            'config/console.php',
            'config/web.php',
        ],
//        'additionalPaths' => [
//            'some-path/some-dir', //directory
//            '@yii/rbac/migrations', // directory with alias
//            '@yii/web/migrations/m160313_153426_session_init.php', // single file
//        ],
    ],
],
```

```
# Run as usual
php yii migrate
```

### Available options

*configs* - array. Application configuration files with 'modules' section

*onlyModules* - array. Scan for new migrations only in modules listed here

*exceptModules* - array. Do not scan for new migrations in modules listed here

*additionalPaths* - array. You can specify additional paths
