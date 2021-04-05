<?php

namespace Dskripchenko\Schemify\Providers;

use Dskripchenko\LaravelApi\Providers\BaseServiceProvider;
use Dskripchenko\Schemify\Console\Commands\ApiInstall;
use Dskripchenko\Schemify\Console\Commands\MigrateCommand;
use Dskripchenko\Schemify\Console\Commands\PackagePostInstall;
use Dskripchenko\Schemify\Console\Commands\PackagePreUninstall;
use Dskripchenko\Schemify\Interfaces\ConnectorInterface;
use Dskripchenko\Schemify\Models\LayerItem;

/**
 * Class SchemifyServiceProvider
 * @package Dskripchenko\Schemify\Providers
 */
class SchemifyServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        // Регистрируем команды
        $this->commands([
            // установка и удаление компонента
            PackagePostInstall::class,
            // автоприменение миграций по всем слоям
            MigrateCommand::class,
            //расширяем базовую настройку окружения при разворачивании проекта
            ApiInstall::class,
        ]);
    }

    public function register()
    {
        //Добавляем конфигурацию динамического соединения с базой данных
        $this->mergeConfigFrom(dirname(__DIR__, 2) . '/config/database.php', 'database');;

        //Регистрируем переопреджеленные команда работы с базой данных и миграциями
        $this->app->register(ConsoleSupportServiceProvider::class);

        //Регистрируем коннектор который будет переключать динамическое соединение с базой данных
        $this->app->bind(ConnectorInterface::class, LayerItem::class);
        $this->app->bind('layer_item_connector', function ($app) {
            try{
                return $app->make(ConnectorInterface::class);
            }
            catch (\Exception $e){
                $abstract = ConnectorInterface::class;
                throw new \Exception("Не установлена реализация {$abstract}.");
            }
        });

        parent::register();
    }
}
