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
        Schema::create('ingredient_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->decimal('price', 12, 4); // price per purchase_unit_quantity
            $table->decimal('purchase_unit_quantity', 12, 3)->default(1); // e.g., 1 kg bag
            $table->string('purchase_unit', 32)->nullable(); // optional redundancy
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->timestamps();
            $table->unique(['ingredient_id', 'supplier_id', 'valid_from']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_prices');
    }
};
