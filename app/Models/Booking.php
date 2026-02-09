<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Booking extends Model
{
    protected $fillable = [
        'booking_code',
        'customer_name',
        'booking_date',
        'guest_count',
        'whatsapp',
        'instagram',
        'seating_spot_id',
        'alternative_seating_spot_id',
        'subtotal_amount',
        'tax_amount',
        'total_amount',
        'dp_amount',
        'payment_proof',
        'status',
        'payment_status',
        'paid_amount',
        'confirmed_at',
        'notes',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'subtotal_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'dp_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->booking_code)) {
                $booking->booking_code = self::generateBookingCode();
            }
        });
    }

    public static function generateBookingCode(): string
    {
        do {
            $code = 'BK' . date('Ymd') . strtoupper(Str::random(4));
        } while (self::where('booking_code', $code)->exists());

        return $code;
    }

    public function seatingSpot(): BelongsTo
    {
        return $this->belongsTo(SeatingSpot::class);
    }

    public function alternativeSeatingSpot(): BelongsTo
    {
        return $this->belongsTo(SeatingSpot::class, 'alternative_seating_spot_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function getPaymentProofUrlAttribute(): ?string
    {
        if ($this->payment_proof) {
            return asset('storage/' . $this->payment_proof);
        }
        return null;
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Menunggu Konfirmasi',
            'confirmed' => 'Dikonfirmasi',
            'cancelled' => 'Dibatalkan',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'confirmed' => 'green',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Recalculate booking totals with tax
     */
    public function recalculateTotals(): void
    {
        // Refresh to get latest items
        $this->load('items');
        
        $subtotal = $this->items->sum(function ($item) {
            return $item->unit_price * $item->quantity;
        });

        $taxRate = config('booking.tax_rate', 10) / 100;
        $tax = $subtotal * $taxRate;
        $total = $subtotal + $tax;
        $dpPercentage = config('booking.dp_percentage', 50) / 100;

        $this->update([
            'subtotal_amount' => $subtotal,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'dp_amount' => $total * $dpPercentage,
        ]);
    }
}
