<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('requested_queries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('sources')->nullable();
            $table->string('status')->default('pendiente');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('requested_queries');
    }
};