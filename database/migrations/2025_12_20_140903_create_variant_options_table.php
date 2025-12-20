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
        Schema::create('variant_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_variant_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "Paha", "Dada", "Sambal Korek"
            $table->decimal('price_adjustment', 12, 2)->default(0); // Additional cost if any
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_options');
    }
};
