<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VariantOption extends Model
{
    protected $fillable = [
        'menu_variant_id',
        'name',
        'price_adjustment',
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(MenuVariant::class, 'menu_variant_id');
    }
}
