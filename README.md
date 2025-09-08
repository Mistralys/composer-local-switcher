# Composer local switcher

PHP library that handles switching between live and local Composer dependencies using 
Composer scripts.

## Local development with symlinks

Using a list of local packages and their paths, the library will replace the packages
in the `composer.json` file with path repositories that use symlinks to work directly 
with the local packages. When switching back to production mode, the original 
`composer.json` file is restored.

## Library refactoring made easy

The library is intended to make local development of interdependent Composer packages easier,
especially when coupled with an IDE like PHPStorm that can work with multiple projects
at the same time. Refactoring classes in a library can then be done in the library project,
and the changes will be immediately available in the project that uses the library.

## Setup

### 1. Local repository configuration

To specify which repositories to switch in the configuration, create a
JSON file anywhere you like in your project with the following structure:

```json
{
  "local-repositories": [
    {
      "package-name": "vendor/package-name",
      "path": "/path/to/package"
    },
    {
      "package-name": "vendor/other-package",
      "path": "/path/to/other-package"
    }
  ]
}
```

All packages listed here will be replaced with path repositories when switching to
development mode, and restored to their original configuration when switching back
to production mode.

### 2. Production configuration

Copy your existing `composer.json` file to a new file, for example `composer-production.json`.
This file will be used as the base configuration when switching back to production mode.

**WARNING**: From now on, only edit the `composer-production.json` file. The `composer.json`
file will be modified automatically when switching between configurations.

### 3. Composer script handler class

Composer scripts are typically static methods in a class, which are called by
Composer when the script is executed.

You can use the following class as a starting point for your project:

```php
declare(strict_types=1);

use Mistralys\ComposerSwitcher\ConfigSwitcher;

class ComposerScripts
{
    public static function switchToDEV() : void
    {
        self::createSwitcher()->switchToDevelopment();
    }
    
    public static function switchToPROD() : void
    {
        self::createSwitcher()->switchToProduction();
    }

    private static $initialized = false;

    /**
     * Initializes the Composer autoloader. Scripts do not
     * automatically load it, so we need to do it manually.
     * Additionally, we ensure that this is only done once
     * across multiple script calls.
     */
    private static function initAutoloader() : void
    {
        if(self::$initialized) {
            return;
        }
        
        self::$initialized = true;
        
        require_once __DIR__ . '/vendor/autoload.php';
    }
    
    /**
     * @var ConfigSwitcher|null 
     */
    private static $switcher = null;
    
    /**
     * Create/get the configuration switcher instance.
     * This automatically initializes the Composer
     * autoloader as well.
     * 
     * @return ConfigSwitcher
     */
    private function createSwitcher() : ConfigSwitcher
    {
        if(self::$switcher !== null) {
            return self::$switcher;
        }
    
        self::initAutoloader();
        
        $switcher = new ConfigSwitcher(
            '/path/to/composer-production.json',
            '/path/to/composer-local-repositories.json',
            '/path/to/composer.json'
        );
        
        self::$switcher = $switcher;
        
        return $switcher;
    }
}
```

### 2. Set up switching scripts

We will be adding two scripts to the `composer.json` file: One for switching to
development mode, and one for switching back to production mode.

```json
{
  "scripts": {
    "switch-dev": "ComposerScripts::switchToDEV",
    "switch-prod": "ComposerScripts::switchToPROD"
  }
}
```

> NOTE: It's good practice to also have a `build` script that ensures the
> configuration is set to production mode before deploying the project to
> minimize the risk of accidentally deploying with development dependencies.

## Script usage

Once the setup is complete, you can use the following Composer commands to switch
between the configurations.

### Switch to development mode

```bash
composer switch-dev
composer update
```

### Switch to production mode

```bash
composer switch-prod
composer update
```

## Why PHP v7.3?

The library is currently used in a legacy Composer environment that is still running 
PHP v7.3. It is planned to be modernized, but until then this package will remain 
compatible with PHP v7.3.
