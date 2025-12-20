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
        'Booking untuk hari yang sama hanya bisa dilakukan sebelum jam 15:00 WIB.',
        'Pembayaran DP minimal 50% dari total pesanan untuk konfirmasi booking.',
        'Sisa pembayaran dilunasi pada saat buka puasa.',
        'Pembatalan booking maksimal H-1 sebelum tanggal booking.',
        'Menu yang sudah dipesan tidak dapat diubah pada hari H.',
        'Waktu buka puasa mengikuti jadwal yang telah ditentukan.',
    ],
];
