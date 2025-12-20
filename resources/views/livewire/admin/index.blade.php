<?php

use App\Models\Booking;
use App\Models\BookingDate;
use App\Models\Category;
use App\Models\Menu;
use Carbon\Carbon;
use Livewire\Volt\Component;

new class extends Component {
    public int $totalBookings = 0;
    public int $pendingBookings = 0;
    public int $confirmedBookings = 0;
    public int $todayBookings = 0;
    public float $totalRevenue = 0;
    public int $totalMenus = 0;
    public int $totalCategories = 0;
    public bool $todayForceOpen = false;
    public bool $isPastCutoff = false;

    public function mount(): void
    {
        $this->loadStats();
        $this->loadTodayStatus();
    }

    public function loadStats(): void
    {
        $this->totalBookings = Booking::count();
        $this->pendingBookings = Booking::pending()->count();
        $this->confirmedBookings = Booking::confirmed()->count();
        $this->todayBookings = Booking::whereDate('booking_date', Carbon::today())->count();
        $this->totalRevenue = Booking::confirmed()->sum('total_amount');
        $this->totalMenus = Menu::count();
        $this->totalCategories = Category::count();
    }

    public function loadTodayStatus(): void
    {
        $cutoffHour = config('booking.cutoff_hour', 15);
        $this->isPastCutoff = Carbon::now()->hour >= $cutoffHour;
        $this->todayForceOpen = BookingDate::isTodayForceOpen();
    }

    public function toggleTodayBooking(): void
    {
        if ($this->todayForceOpen) {
            BookingDate::closeToday();
            $this->todayForceOpen = false;
        } else {
            BookingDate::forceOpenToday();
            $this->todayForceOpen = true;
        }
    }
}; ?>

<div>
    <div class="space-y-6">
        <div>
            <flux:heading size="xl">Dashboard</flux:heading>
            <flux:subheading>Selamat datang di Admin Booking Buka Puasa</flux:subheading>
        </div>

        <!-- Today Booking Status (shown after cutoff time) -->
        @if ($isPastCutoff)
            <x-card class="border-2 {{ $todayForceOpen ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-red-500 bg-red-50 dark:bg-red-900/20' }}">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="p-3 {{ $todayForceOpen ? 'bg-green-100 dark:bg-green-800' : 'bg-red-100 dark:bg-red-800' }} rounded-lg">
                            <flux:icon.calendar-days class="size-8 {{ $todayForceOpen ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}" />
                        </div>
                        <div>
                            <flux:heading size="lg">Booking Hari Ini ({{ now()->translatedFormat('d F Y') }})</flux:heading>
                            <p class="text-sm {{ $todayForceOpen ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                                @if ($todayForceOpen)
                                    ✅ Booking DIBUKA (force open oleh admin)
                                @else
                                    ❌ Booking DITUTUP (sudah lewat jam {{ config('booking.cutoff_hour', 15) }}:00)
                                @endif
                            </p>
                        </div>
                    </div>
                    <flux:button wire:click="toggleTodayBooking" variant="{{ $todayForceOpen ? 'danger' : 'primary' }}">
                        {{ $todayForceOpen ? 'Tutup Booking' : 'Buka Booking' }}
                    </flux:button>
                </div>
            </x-card>
        @endif

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Today Bookings -->
            <x-card class="space-y-2 border-2 border-amber-500 bg-amber-50 dark:bg-amber-900/20">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-amber-100 dark:bg-amber-800 rounded-lg">
                        <flux:icon.sun class="size-6 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div>
                        <flux:subheading>Booking Hari Ini</flux:subheading>
                        <flux:heading size="xl">{{ $todayBookings }}</flux:heading>
                    </div>
                </div>
            </x-card>

            <!-- Total Booking -->
            <x-card class="space-y-2">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <flux:icon.calendar-days class="size-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <flux:subheading>Total Booking</flux:subheading>
                        <flux:heading size="xl">{{ $totalBookings }}</flux:heading>
                    </div>
                </div>
            </x-card>

            <!-- Pending -->
            <x-card class="space-y-2">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <flux:icon.clock class="size-6 text-yellow-600 dark:text-yellow-400" />
                    </div>
                    <div>
                        <flux:subheading>Menunggu Konfirmasi</flux:subheading>
                        <flux:heading size="xl">{{ $pendingBookings }}</flux:heading>
                    </div>
                </div>
            </x-card>

            <!-- Confirmed -->
            <x-card class="space-y-2">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                        <flux:icon.check-circle class="size-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <flux:subheading>Dikonfirmasi</flux:subheading>
                        <flux:heading size="xl">{{ $confirmedBookings }}</flux:heading>
                    </div>
                </div>
            </x-card>

            <!-- Revenue -->
            <x-card class="space-y-2">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <flux:icon.banknotes class="size-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <flux:subheading>Total Pendapatan</flux:subheading>
                        <flux:heading size="lg">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</flux:heading>
                    </div>
                </div>
            </x-card>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-card>
                <flux:heading size="lg" class="mb-4">Ringkasan Menu</flux:heading>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-600 dark:text-zinc-400">Total Kategori</span>
                        <flux:badge>{{ $totalCategories }}</flux:badge>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-600 dark:text-zinc-400">Total Menu</span>
                        <flux:badge>{{ $totalMenus }}</flux:badge>
                    </div>
                </div>
            </x-card>

            <x-card>
                <flux:heading size="lg" class="mb-4">Aksi Cepat</flux:heading>
                <div class="flex flex-wrap gap-2">
                    <flux:button href="{{ route('admin.bookings.index') }}" variant="primary" icon="calendar-days">
                        Lihat Booking
                    </flux:button>
                    <flux:button href="{{ route('admin.menus.create') }}" icon="plus">
                        Tambah Menu
                    </flux:button>
                </div>
            </x-card>
        </div>
    </div>
</div>
