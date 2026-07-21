<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('layer')->create('tenant_probe', function (Blueprint $table) {
            $table->increments('id');
        });
    }

    public function down(): void
    {
        Schema::connection('layer')->dropIfExists('tenant_probe');
    }
};
