<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConnectorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'connectors',
            function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('connection_id')->nullable();
                $table->string('schema', 128);
                $table->timestamps();
                $table->softDeletes();
                $table->foreign('connection_id')->references('id')->on('connections');
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('connectors');
    }
}
