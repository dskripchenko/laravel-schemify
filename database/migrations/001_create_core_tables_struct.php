<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('db_connections', function (Blueprint $table) {
            $table->increments('id');
            $table->string('driver', 16);
            $table->string('host', 128);
            $table->string('port', 16);
            $table->string('database', 128);
            $table->string('username', 128);
            // Stored encrypted via the DbConnection model cast — text holds the ciphertext.
            $table->text('password');
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

            $table->unique('name');
            $table->unique(['schema_name', 'db_connection_id']);
            $table->index('db_connection_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('layer_items');
        Schema::dropIfExists('db_connections');
    }
};
