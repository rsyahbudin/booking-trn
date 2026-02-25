<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class BookingDate extends Model
{
    protected $fillable = [
        'date',
        'is_open',
        'force_open',
        'note',
    ];

    protected $casts = [
        'date' => 'date',
        'is_open' => 'boolean',
        'force_open' => 'boolean',
    ];

    /**
     * Check if a specific date is available for booking
     */
    public static function isAvailableForBooking(string $date): bool
    {
        // Check if date exists in booking_dates and is marked as closed
        $bookingDate = self::where('date', $date)->first();
        
        if ($bookingDate) {
            return $bookingDate->is_open;
        }

        // Default: date is available
        return true;
    }

    /**
     * Open today's date for booking
     */
    public static function openToday(): void
    {
        self::updateOrCreate(
            ['date' => Carbon::today()->toDateString()],
            ['is_open' => true]
        );
    }

    /**
     * Close today's date for booking
     */
    public static function closeToday(): void
    {
        self::updateOrCreate(
            ['date' => Carbon::today()->toDateString()],
            ['is_open' => false]
        );
    }

    /**
     * Check if today is currently open
     */
    public static function isTodayOpen(): bool
    {
        $today = self::where('date', Carbon::today()->toDateString())->first();
        return $today ? $today->is_open : true; // Default true if no record exists
    }
}
