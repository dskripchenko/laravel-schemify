## Installation

Run
```
php composer.phar require dskripchenko/laravel-schemify "^2.0.0"
```

or add

```
"dskripchenko/laravel-schemify": "^2.0.0"
```

to the ```require``` section of your `composer.json` file.

### This component adds to ```db``` and ```migration``` artisan command option ```--layer``` for specify dynamic db connection

Layers struct in ```database.php```
```php
return [
    'layersStruct' => [
        'core' => [
            'main' => true
        ],
    ],
];
```

## Components
* ModelWithDynamicConnection
    * getConnection
    * getLayerItemName
    
## Console

### Commands
* ApiInstall
    * getEnvConfig
    * getEnvConfig    
    * onEndSetup
    
* Automigrate
    * applyMigrations
    
* BaseCommand
    * getNewMigrationName
    * getMigrationClassNameFromFile
    * isMigrationClassNameExists
    * preloadMigrationFiles
    * copyMigrations
    * getMigrationsByDir
    * getTargetMigrationsDir
    * getMigrationFilePathMap
    
* InstallCommand
    * installMigrations
    * getMigrationsDir
    * setupMigrations
    
* PackagePostInstall
* PackagePreUninstall
* UninstallCommand

### Components
* PathByLayer
    * getMigrationPath
    
* RunByLayer
    * runByLayer
    
### Database
* SeedCommand
* WipeCommand

### Migrations
* FreshCommand
* InstallCommand
* MigrateCommand
* MigrateMakeCommand
* RefreshCommand
* ResetCommand
* RollbackCommand
* StatusCommand

## Facades
* LayerItemConnector
    * getLayerItemByName
    * getAllLayerItems

## Interfaces
* ConnectorInterface
    * refreshConnection
    * getPreparedConnection
    * getLayerItemByName
    * getAllLayerItems

## Models
* DbConnection
* LayerItem

## Providers
* ArtisanServiceProvider
* ConsoleSupportServiceProvider
* LayerDBServiceProvider
* MigrationServiceProvider

## Services
* ConnectionHelper
