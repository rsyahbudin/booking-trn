<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingItem extends Model
{
    protected $fillable = [
        'booking_id',
        'menu_id',
        'quantity',
        'unit_price',
        'subtotal',
        'selected_options',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'selected_options' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function getSelectedOptionsTextAttribute(): string
    {
        if (empty($this->selected_options)) {
            return '-';
        }

        $options = [];
        foreach ($this->selected_options as $variantName => $optionName) {
            $options[] = "{$variantName}: {$optionName}";
        }

        return implode(', ', $options);
    }
}
