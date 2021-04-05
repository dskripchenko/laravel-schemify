<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use \Illuminate\Support\Facades\DB;

class CreateCoreTablesStruct extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('db_connections', function (Blueprint $table) {
            $table->increments('id');
            $table->string('driver', 16);
            $table->string('host', 128);
            $table->string('port', 16);
            $table->string('database', 128);
            $table->string('username', 128);
            $table->string('password', 128);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('layer_items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('layer', 32);
            $table->string('name', 32);
            $table->string('schema_name', 255);
            $table->integer('db_connection_id');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('layer_items', function (Blueprint $table) {
            $table->unique('name');
            $table->unique(['schema_name', 'db_connection_id']);
        });

        DB::table('db_connections')->insert([
            'id' => 1,
            'driver' => 'pgsql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 5432),
            'database' => env('DB_DATABASE', 'main'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', 'postgres'),
        ]);

        DB::table('layer_items')->insert([
            'id' => 1,
            'layer' => 'main',
            'name' => 'main',
            'schema_name' => 'main',
            'db_connection_id' => 1,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('layer_items');
        Schema::dropIfExists('db_connections');
    }
}
