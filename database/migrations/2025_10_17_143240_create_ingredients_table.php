<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit', 32); // e.g., g, kg, ml, l, unit
            $table->boolean('is_perishable')->default(false);
            $table->unsignedInteger('shelf_life_days')->nullable();
            $table->decimal('min_stock', 12, 3)->default(0); // reorder point in base unit
            $table->timestamps();
            $table->unique(['name', 'unit']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
