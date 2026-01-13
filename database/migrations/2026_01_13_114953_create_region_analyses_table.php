<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('region_analyses', function (Blueprint $table) {
    $table->id();

    $table->foreignId('idea_id')
          ->constrained('ideas')
          ->onDelete('cascade');

    $table->string('predicted_region')->nullable();
    $table->float('confidence')->nullable();
    $table->boolean('is_ambiguous')->default(false);
    $table->json('top_k')->nullable();

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('region_analyses');
    }
};
