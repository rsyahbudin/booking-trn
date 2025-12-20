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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique();
            $table->string('customer_name');
            $table->date('booking_date');
            $table->integer('guest_count');
            $table->string('whatsapp');
            $table->string('instagram')->nullable();
            $table->foreignId('seating_spot_id')->constrained()->onDelete('restrict');
            $table->decimal('total_amount', 12, 2);
            $table->decimal('dp_amount', 12, 2);
            $table->string('payment_proof')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
