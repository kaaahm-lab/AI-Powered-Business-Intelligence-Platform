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
       Schema::create('property_price_analyses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('idea_id')->constrained()->cascadeOnDelete();

    $table->string('type'); // Rent | Sale
    $table->string('region');
    $table->string('furnishing_status')->nullable();
    $table->string('size_description');

    // Price prediction
    $table->decimal('price_min', 12, 2)->nullable();
    $table->decimal('price_max', 12, 2)->nullable();
    $table->string('price_unit')->nullable();
    $table->string('price_text')->nullable();
    $table->string('price_label')->nullable();
    $table->float('price_confidence')->nullable();
    $table->json('price_top_k')->nullable();

    // Size prediction
    $table->decimal('size_min', 12, 2)->nullable();
    $table->decimal('size_max', 12, 2)->nullable();
    $table->string('size_unit')->nullable();
    $table->string('size_text')->nullable();
    $table->string('size_label')->nullable();
    $table->float('size_confidence')->nullable();
    $table->json('size_top_k')->nullable();

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
        Schema::dropIfExists('property_price_analyses');
    }
};
