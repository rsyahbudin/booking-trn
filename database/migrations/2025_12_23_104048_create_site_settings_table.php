<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('label');
            $table->enum('type', ['text', 'textarea', 'url'])->default('text');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Seed default settings
        $settings = [
            ['key' => 'cafe_name', 'value' => 'Teras Rumah Nenek', 'label' => 'Nama Cafe', 'type' => 'text', 'order' => 1],
            ['key' => 'tagline', 'value' => 'Tempat Buka Puasa Keluarga yang Nyaman', 'label' => 'Tagline', 'type' => 'text', 'order' => 2],
            ['key' => 'address', 'value' => 'Jl. Contoh No. 123, Kota', 'label' => 'Alamat', 'type' => 'textarea', 'order' => 3],
            ['key' => 'gmaps_link', 'value' => 'https://maps.google.com', 'label' => 'Link Google Maps', 'type' => 'url', 'order' => 4],
            ['key' => 'operating_hours', 'value' => 'Senin - Minggu: 16:00 - 23:00', 'label' => 'Jam Operasional', 'type' => 'text', 'order' => 5],
            ['key' => 'whatsapp', 'value' => '6285813035292', 'label' => 'Nomor WhatsApp', 'type' => 'text', 'order' => 6],
            ['key' => 'instagram', 'value' => 'terasrumahnenek', 'label' => 'Username Instagram', 'type' => 'text', 'order' => 7],
            ['key' => 'wa_template_customer', 'value' => "Halo, saya ingin konfirmasi booking:\n\nKode: {booking_code}\nNama: {customer_name}\nTanggal: {booking_date}\nJumlah Tamu: {guest_count}\nSpot: {spot_name}\n\n*Pesanan:*\n{menu_items}\n\n*Pembayaran:*\nSubtotal: {subtotal}\nPPN 10%: {tax}\nTotal: {total}\nDP (50%): {dp_amount}\n\nTerima kasih!", 'label' => 'Template WA Customer', 'type' => 'textarea', 'order' => 8],
            ['key' => 'wa_template_confirm', 'value' => "Halo {customer_name}!\n\nBooking Anda telah *DIKONFIRMASI*\n\n*Detail Booking:*\nKode: {booking_code}\nTanggal: {booking_date}\nSpot: {spot_name}\n\n*Pesanan:*\n{menu_items}\n\n*Pembayaran:*\nTotal: {total}\nDibayar: {paid_amount}\n{remaining_text}\n\nSampai jumpa di Teras Rumah Nenek!", 'label' => 'Template WA Konfirmasi Admin', 'type' => 'textarea', 'order' => 9],
        ];

        foreach ($settings as $setting) {
            DB::table('site_settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
