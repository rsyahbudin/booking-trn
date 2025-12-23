<?php

use App\Models\Booking;
use Livewire\Volt\Component;

new class extends Component {
    public Booking $booking;

    public function mount(Booking $booking): void
    {
        $this->booking = $booking->load('seatingSpot', 'items.menu');
    }

    public bool $showConfirmModal = false;
    public string $paymentStatus = 'dp';
    public string $paidAmount = '';

    public function updatedPaymentStatus($value): void
    {
        if ($value === 'lunas') {
            $this->paidAmount = number_format($this->booking->total_amount, 0, '', '');
        } else {
            $this->paidAmount = number_format($this->booking->dp_amount, 0, '', '');
        }
    }

    public function openConfirmModal(): void
    {
        $this->paymentStatus = 'dp';
        $this->paidAmount = number_format($this->booking->dp_amount, 0, '', '');
        $this->showConfirmModal = true;
    }

    public function closeConfirmModal(): void
    {
        $this->showConfirmModal = false;
        $this->paymentStatus = 'dp';
        $this->paidAmount = '';
    }

    public function confirmBooking(): void
    {
        $this->validate([
            'paymentStatus' => 'required|in:dp,lunas',
            'paidAmount' => 'required|numeric|min:0',
        ]);

        $this->booking->update([
            'status' => 'confirmed',
            'payment_status' => $this->paymentStatus,
            'paid_amount' => $this->paidAmount,
        ]);

        $this->closeConfirmModal();
        session()->flash('message', 'Booking berhasil dikonfirmasi!');
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

                    @if ($booking->payment_status)
                        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <flux:subheading class="mb-2">Info Pembayaran</flux:subheading>
                            <div class="space-y-3">
                                <div>
                                    <div class="text-sm text-zinc-500">Status Pembayaran</div>
                                    <flux:badge :color="$booking->payment_status === 'lunas' ? 'green' : 'yellow'">
                                        {{ $booking->payment_status === 'lunas' ? 'LUNAS' : 'DP' }}
                                    </flux:badge>
                                </div>
                                @if ($booking->paid_amount)
                                    <div>
                                        <div class="text-sm text-zinc-500">Nominal Dibayar</div>
                                        <div class="font-bold text-green-600">Rp {{ number_format($booking->paid_amount, 0, ',', '.') }}</div>
                                    </div>
                                    @if ($booking->payment_status === 'dp')
                                        <div>
                                            <div class="text-sm text-zinc-500">Sisa Pembayaran</div>
                                            <div class="font-bold text-red-500">Rp {{ number_format($booking->total_amount - $booking->paid_amount, 0, ',', '.') }}</div>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($booking->payment_proof)
                        <div>
                            <flux:subheading>Bukti Pembayaran</flux:subheading>
                            <a href="{{ $booking->payment_proof_url }}" target="_blank">
                                <img src="{{ $booking->payment_proof_url }}" alt="Bukti Pembayaran" class="mt-2 w-full max-w-xs rounded-lg border border-zinc-200 dark:border-zinc-700" />
                            </a>
                        </div>
                    @endif

                    @if ($booking->status === 'confirmed')
                        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            @php
                                $confirmMessage = "Halo {$booking->customer_name}!\n\n";
                                $confirmMessage .= "Booking Anda telah *DIKONFIRMASI* \n\n";
                                $confirmMessage .= "*Detail Booking:*\n";
                                $confirmMessage .= "Kode: {$booking->booking_code}\n";
                                $confirmMessage .= "Tanggal: " . $booking->booking_date->format('d F Y') . "\n";
                                $confirmMessage .= "Spot: {$booking->seatingSpot->name}\n\n";
                                
                                // Menu items
                                $confirmMessage .= "*Pesanan:*\n";
                                foreach ($booking->items as $item) {
                                    $confirmMessage .= "• {$item->menu->name}";
                                    if ($item->selected_options_text) {
                                        $confirmMessage .= " ({$item->selected_options_text})";
                                    }
                                    $confirmMessage .= " x{$item->quantity}\n";
                                }
                                $confirmMessage .= "\n";
                                
                                // Payment info
                                $confirmMessage .= "*Pembayaran:*\n";
                                $confirmMessage .= "Total: Rp " . number_format($booking->total_amount, 0, ',', '.') . "\n";
                                $confirmMessage .= "Dibayar: Rp " . number_format($booking->paid_amount, 0, ',', '.') . "\n";
                                if ($booking->payment_status === 'dp') {
                                    $confirmMessage .= "Sisa: Rp " . number_format($booking->total_amount - $booking->paid_amount, 0, ',', '.') . "\n";
                                }
                                $confirmMessage .= "\nSampai jumpa di Teras Rumah Nenek! ";
                                $waUrl = "https://wa.me/{$booking->whatsapp}?text=" . urlencode($confirmMessage);
                            @endphp
                            <a href="{{ $waUrl }}" target="_blank" class="w-full flex items-center justify-center gap-2 bg-green-500 hover:bg-green-600 text-white py-3 px-4 rounded-lg font-medium transition">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                Kirim Konfirmasi via WhatsApp
                            </a>
                        </div>
                    @endif

                    @if ($booking->status === 'pending')
                        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700 space-y-2">
                            <flux:button wire:click="openConfirmModal" variant="primary" class="w-full" icon="check">
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
                <div class="text-right space-y-1">
                    <div class="flex justify-between md:justify-end gap-8 text-sm text-zinc-600 dark:text-zinc-400">
                        <span>Subtotal</span>
                        <span>Rp {{ number_format($booking->subtotal_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between md:justify-end gap-8 text-sm text-zinc-600 dark:text-zinc-400">
                        <span>PPN (10%)</span>
                        <span>Rp {{ number_format($booking->tax_amount, 0, ',', '.') }}</span>
                    </div>
                    <div>
                        <p class="text-zinc-600 dark:text-zinc-400 mt-2 pt-2 border-t border-zinc-200 dark:border-zinc-700">Total</p>
                        <p class="text-2xl font-bold">Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </x-card>
    </div>
    <!-- Confirmation Modal -->
    @if ($showConfirmModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="closeConfirmModal"></div>
            <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-zinc-800 dark:text-white">Konfirmasi Booking</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Pilih status pembayaran dan nominal yang dibayar</p>
                        </div>
                    </div>
                    
                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Status Pembayaran *</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex items-center gap-3 p-4 border-2 rounded-xl cursor-pointer transition {{ $paymentStatus === 'dp' ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20' : 'border-zinc-200 dark:border-zinc-700' }}">
                                    <input type="radio" wire:model.live="paymentStatus" value="dp" class="text-amber-500 focus:ring-amber-500">
                                    <div>
                                        <div class="font-semibold text-zinc-800 dark:text-white">DP</div>
                                        <div class="text-xs text-zinc-500">Uang Muka 50%</div>
                                    </div>
                                </label>
                                <label class="flex items-center gap-3 p-4 border-2 rounded-xl cursor-pointer transition {{ $paymentStatus === 'lunas' ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-zinc-200 dark:border-zinc-700' }}">
                                    <input type="radio" wire:model.live="paymentStatus" value="lunas" class="text-green-500 focus:ring-green-500">
                                    <div>
                                        <div class="font-semibold text-zinc-800 dark:text-white">LUNAS</div>
                                        <div class="text-xs text-zinc-500">Pembayaran Penuh</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Nominal yang Ditransfer (Rp) *</label>
                            <input type="number" wire:model="paidAmount" placeholder="Contoh: 150000" class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-700 text-zinc-800 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                            @error('paidAmount') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <button wire:click="closeConfirmModal" class="px-4 py-2 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition">
                            Batal
                        </button>
                        <button wire:click="confirmBooking" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition font-medium">
                            ✓ Konfirmasi Booking
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
