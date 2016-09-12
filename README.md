# Migrate controller for Yii 2

This extension provides no-more-butthurt components autocomplete generator command for Yii 2.

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
	],
],
```

```
# Run as usual
php yii migrate

```

### Available options

**configs** - array. Application configuration files with 'modules' section

**onlyModules** - array. Scan new migrations only in modules listed here

**exceptModules** -array. Do not scan new migrations in modules listed here

**additionalPaths** -array. You can specify additional paths
