<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tax Rate
    |--------------------------------------------------------------------------
    | 
    | Percentage tax rate applied to all bookings
    |
    */
    'tax_rate' => 10,

    /*
    |--------------------------------------------------------------------------
    | Booking Cutoff Time
    |--------------------------------------------------------------------------
    |
    | Hour of the day when same-day booking is closed (24-hour format)
    |
    */
    'cutoff_hour' => 15,

    /*
    |--------------------------------------------------------------------------
    | Minimum DP Percentage  
    |--------------------------------------------------------------------------
    |
    | Minimum down payment percentage required
    |
    */
    'dp_percentage' => 50,

    /*
    |--------------------------------------------------------------------------
    | Booking Rules
    |--------------------------------------------------------------------------
    |
    | List of booking rules/terms displayed to customers
    |
    */
    'rules' => [
        'Jumlah pemesanan paket wajib disesuaikan dengan jumlah tamu yang hadir (1 orang = 1 paket).',
        'Harga paket belum termasuk menu Snack.',
        'Pembayaran DP minimal 50% dari total pesanan untuk konfirmasi booking.',
        'Sisa pembayaran dilunasi pada saat dikasir.',
        'Pemesanan menu tambahan di luar Paket Ramadhan (ala carte) dapat dilakukan di tempat mulai pukul 19.00 WIB.',
        'Harga paket sudah mencakup biaya Booking Fee, Takjil, dan Free Flow Es Teh dan Infused Water / Mineral water',
        'Batas waktu reservasi untuk hari yang sama adalah pukul 15.00 WIB. (Untuk pengecekan ketersediaan tempat setelah jam tersebut, silakan hubungi WhatsApp kami di +62 858-1303-5292.',
        'Pembatalan booking maksimal H-1 sebelum tanggal booking.',
    ],
];
