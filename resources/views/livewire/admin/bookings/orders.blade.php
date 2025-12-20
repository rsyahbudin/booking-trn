<?php

use App\Models\Booking;
use Carbon\Carbon;
use Livewire\Volt\Component;

new class extends Component {
    public string $dateFilter = '';
    public string $statusFilter = 'confirmed';

    public function mount(): void
    {
        $this->dateFilter = Carbon::today()->format('Y-m-d');
    }

    public function with(): array
    {
        $query = Booking::query()
            ->with(['seatingSpot', 'items.menu'])
            ->when($this->dateFilter, fn($q) => $q->whereDate('booking_date', $this->dateFilter))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->orderBy('booking_date')
            ->orderBy('created_at');

        $bookings = $query->get();
        
        // Calculate summary
        $totalPax = $bookings->sum('guest_count');
        $totalBookings = $bookings->count();
        
        // Aggregate all menu items
        $menuSummary = [];
        foreach ($bookings as $booking) {
            foreach ($booking->items as $item) {
                $menuName = $item->menu->name ?? 'Unknown';
                $options = !empty($item->selected_options) ? ' (' . implode(', ', $item->selected_options) . ')' : '';
                $key = $menuName . $options;
                
                if (!isset($menuSummary[$key])) {
                    $menuSummary[$key] = [
                        'name' => $menuName,
                        'options' => $options,
                        'quantity' => 0,
                    ];
                }
                $menuSummary[$key]['quantity'] += $item->quantity;
            }
        }
        
        // Sort by quantity descending
        uasort($menuSummary, fn($a, $b) => $b['quantity'] <=> $a['quantity']);

        return [
            'bookings' => $bookings,
            'totalPax' => $totalPax,
            'totalBookings' => $totalBookings,
            'menuSummary' => $menuSummary,
        ];
    }
}; ?>

<div>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <flux:heading size="xl">📋 Daftar Pesanan</flux:heading>
                <flux:subheading>View untuk Waiter/Dapur - Lihat semua pesanan hari ini</flux:subheading>
            </div>
            <flux:button href="{{ route('admin.bookings.index') }}" variant="ghost" icon="arrow-left">
                Kembali ke Admin
            </flux:button>
        </div>

        <!-- Filters -->
        <x-card>
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <flux:label>Tanggal</flux:label>
                    <flux:input wire:model.live="dateFilter" type="date" />
                </div>
                <div>
                    <flux:label>Status</flux:label>
                    <flux:select wire:model.live="statusFilter">
                        <option value="">Semua Status</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Dikonfirmasi</option>
                        <option value="cancelled">Dibatalkan</option>
                    </flux:select>
                </div>
                <div class="flex items-center gap-4 px-4 py-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-amber-700 dark:text-amber-300">{{ $totalBookings }}</div>
                        <div class="text-xs text-amber-600 dark:text-amber-400">Booking</div>
                    </div>
                    <div class="w-px h-8 bg-amber-300 dark:bg-amber-700"></div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-amber-700 dark:text-amber-300">{{ $totalPax }}</div>
                        <div class="text-xs text-amber-600 dark:text-amber-400">Pax</div>
                    </div>
                </div>
            </div>
        </x-card>

        <!-- Menu Summary -->
        @if (count($menuSummary) > 0)
            <x-card>
                <flux:heading size="lg" class="mb-4">🍽️ Ringkasan Menu Hari Ini</flux:heading>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                    @foreach ($menuSummary as $item)
                        <div class="p-3 bg-zinc-50 dark:bg-zinc-700 rounded-lg text-center">
                            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $item['quantity'] }}x</div>
                            <div class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $item['name'] }}</div>
                            @if ($item['options'])
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $item['options'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endif

        <!-- Bookings List -->
        <div class="space-y-4">
            @forelse ($bookings as $booking)
                <x-card class="border-l-4 {{ $booking->status === 'confirmed' ? 'border-l-green-500' : ($booking->status === 'pending' ? 'border-l-yellow-500' : 'border-l-red-500') }}">
                    <div class="flex flex-col lg:flex-row lg:items-start gap-4">
                        <!-- Customer Info -->
                        <div class="lg:w-1/4">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900 flex items-center justify-center">
                                    <span class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ substr($booking->customer_name, 0, 1) }}</span>
                                </div>
                                <div>
                                    <div class="font-bold text-zinc-800 dark:text-white">{{ $booking->customer_name }}</div>
                                    <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $booking->booking_code }}</div>
                                </div>
                            </div>
                            <div class="text-sm space-y-1 text-zinc-600 dark:text-zinc-400">
                                <div>📞 {{ $booking->whatsapp }}</div>
                                <div>👥 {{ $booking->guest_count }} orang</div>
                                <div>🪑 {{ $booking->seatingSpot->name ?? '-' }}</div>
                                @if ($booking->notes)
                                    <div class="text-amber-600 dark:text-amber-400">📝 {{ $booking->notes }}</div>
                                @endif
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <flux:badge :color="$booking->status_color">{{ $booking->status_label }}</flux:badge>
                                @if ($booking->payment_status)
                                    <flux:badge :color="$booking->payment_status === 'lunas' ? 'green' : 'yellow'">
                                        {{ $booking->payment_status === 'lunas' ? 'LUNAS' : 'DP' }}
                                    </flux:badge>
                                @endif
                            </div>
                            @if ($booking->paid_amount)
                                <div class="mt-1 text-sm text-green-600 dark:text-green-400">
                                    💰 Dibayar: Rp {{ number_format($booking->paid_amount, 0, ',', '.') }}
                                </div>
                                @if ($booking->payment_status === 'dp')
                                    <div class="mt-1 text-sm text-red-500">
                                        ⚠️ Sisa: Rp {{ number_format($booking->total_amount - $booking->paid_amount, 0, ',', '.') }}
                                    </div>
                                @endif
                            @endif
                        </div>

                        <!-- Menu Items -->
                        <div class="lg:w-3/4">
                            <div class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-2">Pesanan Menu:</div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                            <th class="text-left py-2 px-3 font-medium text-zinc-700 dark:text-zinc-300">Menu</th>
                                            <th class="text-center py-2 px-3 font-medium text-zinc-700 dark:text-zinc-300 w-20">Qty</th>
                                            <th class="text-right py-2 px-3 font-medium text-zinc-700 dark:text-zinc-300 w-32">Harga</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($booking->items as $item)
                                            <tr class="border-b border-zinc-100 dark:border-zinc-700">
                                                <td class="py-2 px-3">
                                                    <div class="font-medium text-zinc-800 dark:text-zinc-200">{{ $item->menu->name ?? 'Menu tidak tersedia' }}</div>
                                                    @if (!empty($item->selected_options))
                                                        <div class="text-xs text-amber-600 dark:text-amber-400">({{ implode(', ', $item->selected_options) }})</div>
                                                    @endif
                                                </td>
                                                <td class="py-2 px-3 text-center">
                                                    <span class="inline-flex items-center justify-center w-8 h-8 bg-amber-100 dark:bg-amber-900 rounded-full font-bold text-amber-700 dark:text-amber-300">
                                                        {{ $item->quantity }}
                                                    </span>
                                                </td>
                                                <td class="py-2 px-3 text-right text-zinc-600 dark:text-zinc-400">
                                                    Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-zinc-50 dark:bg-zinc-800">
                                            <td colspan="2" class="py-2 px-3 font-bold text-right">Total:</td>
                                            <td class="py-2 px-3 text-right font-bold text-amber-600 dark:text-amber-400">
                                                Rp {{ number_format($booking->total_amount, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </x-card>
            @empty
                <x-card class="text-center py-12">
                    <div class="text-4xl mb-4">📭</div>
                    <flux:heading>Tidak ada booking</flux:heading>
                    <flux:subheading>Tidak ada booking untuk tanggal dan filter yang dipilih</flux:subheading>
                </x-card>
            @endforelse
        </div>
    </div>
</div>
