<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('details_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_query_id')->constrained('requested_queries')->onDelete('cascade');
            $table->string('full_name');
            $table->string('document_type')->nullable();
            $table->string('document_number')->nullable();
            $table->string('status')->default('pendiente');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('details_queries');
    }
};
