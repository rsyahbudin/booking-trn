<?php

use App\Models\Booking;
use Livewire\Volt\Component;

new class extends Component {
    public Booking $booking;

    public function mount(Booking $booking): void
    {
        $this->booking = $booking->load('seatingSpot', 'items.menu');
    }

    public function updateStatus(string $status): void
    {
        $this->booking->update(['status' => $status]);
        session()->flash('message', 'Status booking berhasil diperbarui!');
    }
}; ?>

<div>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <flux:heading size="xl">Detail Booking</flux:heading>
                <flux:subheading>Kode: {{ $booking->booking_code }}</flux:subheading>
            </div>
            <div class="flex gap-2">
                <flux:button href="{{ route('admin.bookings.edit', $booking) }}" icon="pencil">Edit</flux:button>
                <flux:button href="{{ route('admin.bookings.index') }}" variant="ghost">Kembali</flux:button>
            </div>
        </div>

        @if (session('message'))
            <flux:callout variant="success" icon="check-circle">
                {{ session('message') }}
            </flux:callout>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Customer Info -->
            <x-card class="lg:col-span-2">
                <flux:heading size="lg" class="mb-4">Informasi Pelanggan</flux:heading>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:subheading>Nama</flux:subheading>
                        <p class="font-medium">{{ $booking->customer_name }}</p>
                    </div>
                    <div>
                        <flux:subheading>Tanggal Booking</flux:subheading>
                        <p class="font-medium">{{ $booking->booking_date->format('d F Y') }}</p>
                    </div>
                    <div>
                        <flux:subheading>Jumlah Tamu</flux:subheading>
                        <p class="font-medium">{{ $booking->guest_count }} orang</p>
                    </div>
                    <div>
                        <flux:subheading>WhatsApp</flux:subheading>
                        <p class="font-medium">
                            <a href="https://wa.me/{{ $booking->whatsapp }}" target="_blank" class="text-green-600 hover:underline">
                                {{ $booking->whatsapp }}
                            </a>
                        </p>
                    </div>
                    <div>
                        <flux:subheading>Instagram</flux:subheading>
                        <p class="font-medium">{{ $booking->instagram ?: '-' }}</p>
                    </div>
                    <div>
                        <flux:subheading>Spot Duduk</flux:subheading>
                        <p class="font-medium">{{ $booking->seatingSpot->name }}</p>
                    </div>
                </div>

                @if ($booking->notes)
                    <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <flux:subheading>Catatan</flux:subheading>
                        <p>{{ $booking->notes }}</p>
                    </div>
                @endif
            </x-card>

            <!-- Status & Payment -->
            <x-card>
                <flux:heading size="lg" class="mb-4">Status & Pembayaran</flux:heading>
                
                <div class="space-y-4">
                    <div>
                        <flux:subheading>Status</flux:subheading>
                        <flux:badge size="lg" :color="$booking->status_color">{{ $booking->status_label }}</flux:badge>
                    </div>
                    
                    <div>
                        <flux:subheading>Total Pesanan</flux:subheading>
                        <p class="text-xl font-bold">Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</p>
                    </div>
                    
                    <div>
                        <flux:subheading>DP (50%)</flux:subheading>
                        <p class="text-lg font-medium text-green-600">Rp {{ number_format($booking->dp_amount, 0, ',', '.') }}</p>
                    </div>

                    @if ($booking->payment_proof)
                        <div>
                            <flux:subheading>Bukti Pembayaran</flux:subheading>
                            <a href="{{ $booking->payment_proof_url }}" target="_blank">
                                <img src="{{ $booking->payment_proof_url }}" alt="Bukti Pembayaran" class="mt-2 w-full max-w-xs rounded-lg border border-zinc-200 dark:border-zinc-700" />
                            </a>
                        </div>
                    @endif

                    @if ($booking->status === 'pending')
                        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700 space-y-2">
                            <flux:button wire:click="updateStatus('confirmed')" variant="primary" class="w-full" icon="check">
                                Konfirmasi Booking
                            </flux:button>
                            <flux:button wire:click="updateStatus('cancelled')" wire:confirm="Yakin ingin membatalkan?" variant="danger" class="w-full" icon="x-mark">
                                Batalkan Booking
                            </flux:button>
                        </div>
                    @endif
                </div>
            </x-card>
        </div>

        <!-- Order Items -->
        <x-card>
            <flux:heading size="lg" class="mb-4">Detail Pesanan</flux:heading>
            
            <x-table>
                <x-columns>
                    <x-column>Menu</x-column>
                    <x-column>Opsi</x-column>
                    <x-column>Harga</x-column>
                    <x-column>Qty</x-column>
                    <x-column>Subtotal</x-column>
                </x-columns>

                <x-rows>
                    @foreach ($booking->items as $item)
                        <x-row>
                            <x-cell class="font-medium">{{ $item->menu->name }}</x-cell>
                            <x-cell>{{ $item->selected_options_text }}</x-cell>
                            <x-cell>Rp {{ number_format($item->unit_price, 0, ',', '.') }}</x-cell>
                            <x-cell>{{ $item->quantity }}</x-cell>
                            <x-cell class="font-medium">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</x-cell>
                        </x-row>
                    @endforeach
                </x-rows>
            </x-table>

            <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700 flex justify-end">
                <div class="text-right">
                    <p class="text-zinc-600 dark:text-zinc-400">Total</p>
                    <p class="text-2xl font-bold">Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</p>
                </div>
            </div>
        </x-card>
    </div>
</div>
