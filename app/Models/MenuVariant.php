<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuVariant extends Model
{
    protected $fillable = [
        'menu_id',
        'name',
        'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(VariantOption::class);
    }
}
