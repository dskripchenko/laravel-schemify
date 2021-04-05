<?php

namespace Dskripchenko\Schemify\Console\Commands;

use Dskripchenko\Schemify\Models\DbConnection;
use Dskripchenko\Schemify\Models\LayerItem;
use Illuminate\Support\Facades\Artisan;
use Dskripchenko\LaravelApi\Console\Commands\ApiInstall as BaseApiInstall;

/**
 * Class ApiInstall
 * @package Dskripchenko\Schemify\Console\Commands
 */
class ApiInstall extends BaseApiInstall
{
    protected function getEnvConfig() : array
    {
        return array_merge_deep(parent::getEnvConfig(), [
            'Параметры подключения к базе данных' => [
                '{{DB_CONNECTION}}' => [
                    'default' => 'pgsql',
                ],
                '{{DB_PORT}}' => [
                    'default' => 5432,
                ],
                '{{DB_SCHEMA}}' => [
                    'name' => 'Схема',
                    'default' => 'core',
                ],
            ]
        ]);
    }

    protected function onEndSetup(): void
    {
        Artisan::call('migrate', ['--layer' => env('LAYER_ROOT', 'core')]);

        $main = env('LAYER_MAIN', 'main');
        $mainLayer = LayerItem::query()->where('schema_name', $main)->first();
        if (!$mainLayer) {
            $this->error("Не найден слой '{$main}'");
            return;
        }

        $connection = DbConnection::query()->where('id', $mainLayer->db_connection_id)->first();
        if($connection) {
            $connection->driver = env('DB_CONNECTION');
            $connection->host = env('DB_HOST');
            $connection->port = env('DB_PORT');
            $connection->database = env('DB_DATABASE');
            $connection->username = env('DB_USERNAME');
            $connection->password = env('DB_PASSWORD');
            $connection->save();
        }

        Artisan::call('automigrate');
    }
}
