<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('results_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detail_query_id')->constrained('details_queries')->onDelete('cascade');
            $table->string('source')->nullable();
            $table->string('status_response')->nullable();
            $table->json('response_json')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('results_queries');
    }
};
