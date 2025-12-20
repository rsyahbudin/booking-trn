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
        $targetDate = Carbon::parse($date);
        $now = Carbon::now();
        $cutoffTime = Carbon::today()->setHour(15)->setMinute(0);

        // Check if target date is today and current time is past 15:00
        if ($targetDate->isToday() && $now->gte($cutoffTime)) {
            // Check if admin has force opened this date
            $bookingDate = self::where('date', $targetDate->toDateString())->first();
            
            if ($bookingDate && $bookingDate->force_open) {
                return true;
            }
            
            return false;
        }

        // Check if date exists in booking_dates and is marked as closed
        $bookingDate = self::where('date', $targetDate->toDateString())->first();
        
        if ($bookingDate) {
            return $bookingDate->is_open || $bookingDate->force_open;
        }

        // Default: date is available
        return true;
    }

    /**
     * Force open today's date for booking
     */
    public static function forceOpenToday(): void
    {
        self::updateOrCreate(
            ['date' => Carbon::today()->toDateString()],
            ['force_open' => true, 'is_open' => true]
        );
    }

    /**
     * Close today's date for booking
     */
    public static function closeToday(): void
    {
        self::updateOrCreate(
            ['date' => Carbon::today()->toDateString()],
            ['force_open' => false]
        );
    }

    /**
     * Check if today is currently force opened
     */
    public static function isTodayForceOpen(): bool
    {
        $today = self::where('date', Carbon::today()->toDateString())->first();
        return $today ? $today->force_open : false;
    }
}
