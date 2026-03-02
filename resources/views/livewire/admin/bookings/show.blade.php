<?php

use App\Models\Booking;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads;

    public Booking $booking;

    public function mount(Booking $booking): void
    {
        $this->booking = $booking->load('seatingSpot', 'alternativeSeatingSpot', 'items.menu');
    }

    public bool $showConfirmModal = false;
    public string $paymentStatus = 'dp';
    public string $paidAmount = '';

    public bool $showEditPaymentModal = false;
    public $new_payment_proof = null;

    public bool $showCancelModal = false;
    public string $cancellationReason = '';

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

    public function openEditPaymentModal(): void
    {
        $this->new_payment_proof = null;
        $this->showEditPaymentModal = true;
    }

    public function closeEditPaymentModal(): void
    {
        $this->showEditPaymentModal = false;
        $this->new_payment_proof = null;
    }

    public function updatePaymentProof(): void
    {
        $this->validate([
            'new_payment_proof' => 'required|image|max:5120',
        ], [
            'new_payment_proof.required' => 'Pilih file bukti pembayaran baru',
            'new_payment_proof.image' => 'File harus berupa gambar',
            'new_payment_proof.max' => 'Ukuran maksimal 5MB',
        ]);

        // Delete old proof if it exists
        if ($this->booking->payment_proof) {
            Storage::disk('public')->delete($this->booking->payment_proof);
        }

        // Store new proof
        $path = $this->new_payment_proof->store('payments', 'public');

        // Update booking
        $this->booking->update([
            'payment_proof' => $path,
        ]);

        $this->closeEditPaymentModal();
        session()->flash('message', 'Bukti pembayaran berhasil diperbarui!');
    }

    public function openCancelModal(): void
    {
        $this->cancellationReason = '';
        $this->showCancelModal = true;
    }

    public function closeCancelModal(): void
    {
        $this->showCancelModal = false;
        $this->cancellationReason = '';
    }

    public function cancelBooking(): void
    {
        $this->validate([
            'cancellationReason' => 'required|min:5',
        ], [
            'cancellationReason.required' => 'Alasan pembatalan harus diisi',
            'cancellationReason.min' => 'Alasan pembatalan minimal 5 karakter',
        ]);

        $this->booking->update([
            'status' => 'cancelled',
            'cancellation_reason' => $this->cancellationReason,
        ]);

        $this->closeCancelModal();
        session()->flash('message', 'Booking berhasil dibatalkan!');
    }

    public function updateStatus(string $status): void
    {
        if ($status === 'cancelled') {
            $this->openCancelModal();
            return;
        }

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
                        <flux:subheading>Spot Prioritas</flux:subheading>
                        <p class="font-medium flex items-center gap-2">
                            <span class="text-amber-600 dark:text-amber-400">⭐</span>
                            {{ $booking->seatingSpot->name }}
                        </p>
                    </div>
                    <div>
                        <flux:subheading>Spot Alternatif</flux:subheading>
                        <p class="font-medium flex items-center gap-2">
                            <span class="text-blue-600 dark:text-blue-400">🔄</span>
                            {{ $booking->alternativeSeatingSpot?->name ?: '-' }}
                        </p>
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
                        @if ($booking->status === 'cancelled' && $booking->cancellation_reason)
                            <div class="mt-2 p-3 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 rounded-lg text-sm">
                                <span class="font-bold text-red-700 dark:text-red-400">Alasan Batal:</span>
                                <p class="text-zinc-700 dark:text-zinc-300">{{ $booking->cancellation_reason }}</p>
                            </div>
                        @endif
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
                            <div class="flex items-center justify-between mb-2">
                                <flux:subheading>Bukti Pembayaran</flux:subheading>
                                <flux:button wire:click="openEditPaymentModal" size="sm" variant="ghost" icon="pencil-square">Edit Bukti</flux:button>
                            </div>
                            <a href="{{ $booking->payment_proof_url }}" target="_blank">
                                <img src="{{ $booking->payment_proof_url }}" alt="Bukti Pembayaran" class="w-full max-w-xs rounded-lg border border-zinc-200 dark:border-zinc-700 hover:opacity-75 transition" />
                            </a>
                        </div>
                    @else
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <flux:subheading>Bukti Pembayaran</flux:subheading>
                                <flux:button wire:click="openEditPaymentModal" size="sm" variant="primary" icon="plus">Upload Bukti</flux:button>
                            </div>
                            <div class="p-4 border border-dashed border-zinc-300 dark:border-zinc-700 rounded-lg text-center text-zinc-500 text-sm">
                                Belum ada bukti pembayaran
                            </div>
                        </div>
                    @endif

                    @if ($booking->status === 'confirmed')
                        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            @php
                                // Build menu items string
                                $menuItemsText = '';
                                foreach ($booking->items as $item) {
                                    $menuItemsText .= "• {$item->menu->name}";
                                    if ($item->selected_options_text) {
                                        $menuItemsText .= " ({$item->selected_options_text})";
                                    }
                                    $menuItemsText .= " x{$item->quantity}\n";
                                }
                                
                                // Build remaining text
                                $remainingText = '';
                                if ($booking->payment_status === 'dp') {
                                    $remainingText = "Sisa: Rp " . number_format($booking->total_amount - $booking->paid_amount, 0, ',', '.');
                                }
                                
                                // Get template from settings
                                $defaultTemplate = "Halo {customer_name}!\n\nBooking Anda telah *DIKONFIRMASI*\n\n*Detail Booking:*\nKode: {booking_code}\nTanggal: {booking_date}\nSpot Prioritas: {spot_name}\nSpot Alternatif: {alternative_spot_name}\n\n*Pesanan:*\n{menu_items}\n*Pembayaran:*\nTotal: {total}\nDibayar: {paid_amount}\n{remaining_text}\n\nSampai jumpa di Teras Rumah Nenek!";
                                $template = \App\Models\SiteSetting::get('wa_template_confirm', $defaultTemplate);
                                
                                // Replace placeholders
                                $confirmMessage = \App\Models\SiteSetting::parseTemplate($template, [
                                    'customer_name' => $booking->customer_name,
                                    'booking_code' => $booking->booking_code,
                                    'booking_date' => $booking->booking_date->format('d F Y'),
                                    'spot_name' => $booking->seatingSpot->name,
                                    'alternative_spot_name' => $booking->alternativeSeatingSpot?->name ?: '-',
                                    'menu_items' => $menuItemsText,
                                    'total' => 'Rp ' . number_format($booking->total_amount, 0, ',', '.'),
                                    'paid_amount' => 'Rp ' . number_format($booking->paid_amount, 0, ',', '.'),
                                    'remaining_text' => $remainingText,
                                    'guest_count' => $booking->guest_count . ' orang',
                                ]);
                                
                                $waUrl = "https://wa.me/{$booking->whatsapp}?text=" . urlencode($confirmMessage);
                            @endphp
                            <a href="{{ $waUrl }}" target="_blank" class="w-full flex items-center justify-center gap-2 bg-green-500 hover:bg-green-600 text-white py-3 px-4 rounded-lg font-medium transition">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                Kirim Konfirmasi via WhatsApp
                            </a>
                        </div>
                    @endif

                    @if ($booking->status !== 'cancelled')
                        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700 space-y-2">
                            @if ($booking->status === 'pending')
                                <flux:button wire:click="openConfirmModal" variant="primary" class="w-full" icon="check">
                                    Konfirmasi Booking
                                </flux:button>
                            @endif
                            <flux:button wire:click="updateStatus('cancelled')" variant="danger" class="w-full" icon="x-mark">
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

    <!-- Edit Payment Proof Modal -->
    @if ($showEditPaymentModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="closeEditPaymentModal"></div>
            <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-3 bg-amber-100 dark:bg-amber-900 rounded-full">
                            <flux:icon.pencil-square class="size-6 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-zinc-800 dark:text-white">Edit Bukti Pembayaran</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Upload ulang bukti pembayaran yang benar</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div 
                            x-data="{ isUploading: false, progress: 0 }"
                            x-on:livewire-upload-start="isUploading = true"
                            x-on:livewire-upload-finish="isUploading = false"
                            x-on:livewire-upload-error="isUploading = false"
                            x-on:livewire-upload-progress="progress = $event.detail.progress"
                            class="relative"
                        >
                            <div class="bg-zinc-50 dark:bg-zinc-900 border-2 border-dashed border-zinc-300 dark:border-zinc-700 rounded-xl p-4 text-center hover:bg-zinc-100 dark:hover:bg-zinc-700 transition relative min-h-[140px] flex flex-col items-center justify-center">
                                <input type="file" wire:model="new_payment_proof" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                
                                @if ($new_payment_proof)
                                    <div class="space-y-3 w-full">
                                        <div class="relative w-full aspect-video rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                                            <img src="{{ $new_payment_proof->temporaryUrl() }}" class="w-full h-full object-contain">
                                        </div>
                                        <p class="text-[10px] text-zinc-500 truncate px-4">{{ $new_payment_proof->getClientOriginalName() }}</p>
                                    </div>
                                @else
                                    <div class="pointer-events-none">
                                        <flux:icon.photo class="size-8 text-zinc-400 mx-auto mb-2" />
                                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Pilih Bukti Baru</p>
                                        <p class="text-xs text-zinc-400 mt-1">Klik atau seret file ke sini</p>
                                    </div>
                                @endif

                                <!-- Loading Bar -->
                                <div x-show="isUploading" class="absolute inset-x-0 bottom-0 p-4 bg-white/80 dark:bg-zinc-800/80 backdrop-blur-sm rounded-b-xl">
                                    <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-1.5 mb-1">
                                        <div class="bg-amber-500 h-1.5 rounded-full" x-bind:style="'width: ' + progress + '%'"></div>
                                    </div>
                                    <div class="text-[10px] text-zinc-500 font-medium">Mengunggah... <span x-text="progress + '%'"></span></div>
                                </div>
                            </div>
                            @error('new_payment_proof') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <flux:button wire:click="closeEditPaymentModal" variant="ghost">Batal</flux:button>
                        <flux:button wire:click="updatePaymentProof" variant="primary" icon="check" wire:loading.attr="disabled">
                            Update Bukti
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Cancellation Modal -->
    @if ($showCancelModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="closeCancelModal"></div>
            <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-3 bg-red-100 dark:bg-red-900 rounded-full">
                            <flux:icon.x-mark class="size-6 text-red-600 dark:text-red-400" />
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-zinc-800 dark:text-white">Batalkan Booking</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Berikan alasan kenapa booking ini dibatalkan</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <flux:textarea 
                                wire:model="cancellationReason" 
                                label="Alasan Pembatalan *" 
                                placeholder="Contoh: Salah pilih tanggal, batal mendadak, dll..." 
                                rows="4"
                            />
                            @error('cancellationReason') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <flux:button wire:click="closeCancelModal" variant="ghost">Batal</flux:button>
                        <flux:button wire:click="cancelBooking" variant="danger" icon="x-mark">
                            Ya, Batalkan Booking
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
