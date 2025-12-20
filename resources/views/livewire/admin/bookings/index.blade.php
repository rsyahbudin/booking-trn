<?php

use App\Models\Booking;
use App\Exports\BookingsExport;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $dateFilter = '';
    
    // Export filters
    public string $exportDateFrom = '';
    public string $exportDateTo = '';
    public string $exportStatus = '';

    // Confirm modal
    public bool $showConfirmModal = false;
    public ?int $confirmingBookingId = null;
    public string $paymentStatus = 'dp';
    public string $paidAmount = '';

    public function updatedPaymentStatus($value): void
    {
        if ($this->confirmingBookingId) {
            $booking = Booking::find($this->confirmingBookingId);
            if ($booking) {
                if ($value === 'lunas') {
                    $this->paidAmount = number_format($booking->total_amount, 0, '', '');
                } else {
                    $this->paidAmount = number_format($booking->dp_amount, 0, '', '');
                }
            }
        }
    }

    public function openConfirmModal(int $id): void
    {
        $booking = Booking::findOrFail($id);
        $this->confirmingBookingId = $id;
        $this->paymentStatus = 'dp';
        $this->paidAmount = number_format($booking->dp_amount, 0, '', '');
        $this->showConfirmModal = true;
    }

    public function closeConfirmModal(): void
    {
        $this->showConfirmModal = false;
        $this->confirmingBookingId = null;
        $this->paymentStatus = 'dp';
        $this->paidAmount = '';
    }

    public function confirmBooking(): void
    {
        $this->validate([
            'paymentStatus' => 'required|in:dp,lunas',
            'paidAmount' => 'required|numeric|min:0',
        ]);

        $booking = Booking::findOrFail($this->confirmingBookingId);
        $booking->update([
            'status' => 'confirmed',
            'payment_status' => $this->paymentStatus,
            'paid_amount' => (float) str_replace(['.', ','], '', $this->paidAmount),
            'confirmed_at' => now(),
        ]);
        
        $this->closeConfirmModal();
        session()->flash('message', 'Booking berhasil dikonfirmasi!');
    }

    // Edit Payment Modal
    public bool $showEditPaymentModal = false;
    public ?int $editingPaymentBookingId = null;
    public string $editPaymentStatus = 'dp';
    public string $editPaidAmount = '';

    public function openEditPaymentModal(int $id): void
    {
        $booking = Booking::findOrFail($id);
        $this->editingPaymentBookingId = $id;
        $this->editPaymentStatus = $booking->payment_status ?? 'dp';
        $this->editPaidAmount = $booking->paid_amount ? number_format($booking->paid_amount, 0, '', '') : '';
        $this->showEditPaymentModal = true;
    }

    public function closeEditPaymentModal(): void
    {
        $this->showEditPaymentModal = false;
        $this->editingPaymentBookingId = null;
        $this->editPaymentStatus = 'dp';
        $this->editPaidAmount = '';
    }

    public function updatePayment(): void
    {
        $this->validate([
            'editPaymentStatus' => 'required|in:dp,lunas',
            'editPaidAmount' => 'required|numeric|min:0',
        ]);

        $booking = Booking::findOrFail($this->editingPaymentBookingId);
        $booking->update([
            'payment_status' => $this->editPaymentStatus,
            'paid_amount' => (float) str_replace(['.', ','], '', $this->editPaidAmount),
        ]);
        
        $this->closeEditPaymentModal();
        session()->flash('message', 'Pembayaran berhasil diperbarui!');
    }

    public function updateStatus(int $id, string $status): void
    {
        $booking = Booking::findOrFail($id);
        $booking->update(['status' => $status]);
        
        session()->flash('message', 'Status booking berhasil diperbarui!');
    }

    public function deleteBooking(int $id): void
    {
        $booking = Booking::findOrFail($id);
        
        if ($booking->payment_proof) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($booking->payment_proof);
        }
        
        $booking->delete();
        
        session()->flash('message', 'Booking berhasil dihapus!');
    }

    public function exportExcel()
    {
        $export = new BookingsExport();
        
        if ($this->exportDateFrom) {
            $export->forDateRange($this->exportDateFrom, $this->exportDateTo ?: null);
        }
        
        if ($this->exportStatus) {
            $export->forStatus($this->exportStatus);
        }

        $filename = 'bookings_' . now()->format('Y-m-d_His') . '.xlsx';
        
        return Excel::download($export, $filename);
    }

    public function with(): array
    {
        $query = Booking::query()
            ->with('seatingSpot', 'items.menu')
            ->when($this->search, fn($q) => $q->where('customer_name', 'like', "%{$this->search}%")
                ->orWhere('booking_code', 'like', "%{$this->search}%")
                ->orWhere('whatsapp', 'like', "%{$this->search}%"))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->dateFilter, fn($q) => $q->whereDate('booking_date', $this->dateFilter));

        // Calculate total pax for filtered results
        $totalPax = (clone $query)->sum('guest_count');
        $totalBookingsFiltered = (clone $query)->count();

        return [
            'bookings' => $query->latest()->paginate(10),
            'totalPax' => $totalPax,
            'totalBookingsFiltered' => $totalBookingsFiltered,
        ];
    }
}; ?>

<div>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <flux:heading size="xl">Daftar Booking</flux:heading>
                <flux:subheading>Kelola reservasi buka puasa bersama</flux:subheading>
            </div>
        </div>

        @if (session('message'))
            <flux:callout variant="success" icon="check-circle">
                {{ session('message') }}
            </flux:callout>
        @endif

        <x-card>
            <div class="flex flex-col lg:flex-row gap-4 mb-4">
                <div class="flex-1">
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari nama, kode, atau WhatsApp..." icon="magnifying-glass" />
                </div>
                <div class="w-full lg:w-40">
                    <flux:select wire:model.live="statusFilter">
                        <option value="">Semua Status</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Dikonfirmasi</option>
                        <option value="cancelled">Dibatalkan</option>
                    </flux:select>
                </div>
                <div class="w-full lg:w-44">
                    <flux:input wire:model.live="dateFilter" type="date" />
                </div>
            </div>

            <!-- Filter Stats -->
            @if ($dateFilter || $statusFilter || $search)
                <div class="flex flex-wrap gap-4 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                    <div class="flex items-center gap-2">
                        <span class="text-amber-700 dark:text-amber-300 font-medium">📊 Hasil Filter:</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">Total Booking:</span>
                        <span class="font-bold text-amber-700 dark:text-amber-300">{{ $totalBookingsFiltered }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">Total Pax:</span>
                        <span class="font-bold text-amber-700 dark:text-amber-300">{{ $totalPax }} orang</span>
                    </div>
                    @if ($dateFilter)
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Tanggal:</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ \Carbon\Carbon::parse($dateFilter)->format('d M Y') }}</span>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Export Section -->
            <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg mb-4">
                <flux:heading size="sm" class="mb-3">📊 Export ke Excel</flux:heading>
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <flux:label class="text-xs">Dari Tanggal</flux:label>
                        <flux:input wire:model="exportDateFrom" type="date" size="sm" />
                    </div>
                    <div>
                        <flux:label class="text-xs">Sampai Tanggal</flux:label>
                        <flux:input wire:model="exportDateTo" type="date" size="sm" />
                    </div>
                    <div>
                        <flux:label class="text-xs">Status</flux:label>
                        <flux:select wire:model="exportStatus" size="sm">
                            <option value="">Semua</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Dikonfirmasi</option>
                            <option value="cancelled">Dibatalkan</option>
                        </flux:select>
                    </div>
                    <flux:button wire:click="exportExcel" variant="primary" icon="arrow-down-tray">
                        Download Excel
                    </flux:button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <x-table>
                    <x-columns>
                        <x-column>Kode</x-column>
                        <x-column>Nama</x-column>
                        <x-column>Tanggal</x-column>
                        <x-column>Pax</x-column>
                        <x-column>Total</x-column>
                        <x-column>Pembayaran</x-column>
                        <x-column>Status</x-column>
                        <x-column>Aksi</x-column>
                    </x-columns>

                    <x-rows>
                        @forelse ($bookings as $booking)
                            <x-row :key="$booking->id">
                                <x-cell class="font-mono text-sm">{{ $booking->booking_code }}</x-cell>
                                <x-cell>
                                    <div>
                                        <div class="font-medium">{{ $booking->customer_name }}</div>
                                        <div class="text-sm text-zinc-500">{{ $booking->whatsapp }}</div>
                                    </div>
                                </x-cell>
                                <x-cell>{{ $booking->booking_date->format('d M Y') }}</x-cell>
                                <x-cell>{{ $booking->guest_count }} orang</x-cell>
                                <x-cell>Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</x-cell>
                                <x-cell>
                                    @if ($booking->payment_status)
                                        <div class="text-sm">
                                            <flux:badge :color="$booking->payment_status === 'lunas' ? 'green' : 'yellow'">
                                                {{ $booking->payment_status === 'lunas' ? 'LUNAS' : 'DP' }}
                                            </flux:badge>
                                            <div class="text-zinc-600 dark:text-zinc-400 mt-1">
                                                Rp {{ number_format($booking->paid_amount, 0, ',', '.') }}
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-zinc-400">-</span>
                                    @endif
                                </x-cell>
                                <x-cell>
                                    <flux:badge :color="$booking->status_color">{{ $booking->status_label }}</flux:badge>
                                </x-cell>
                                <x-cell>
                                    <div class="flex gap-1">
                                        <flux:button href="{{ route('admin.bookings.show', $booking) }}" size="sm" icon="eye" title="Detail" />
                                        @if ($booking->status === 'pending')
                                            <flux:button wire:click="openConfirmModal({{ $booking->id }})" size="sm" variant="primary" icon="check" title="Konfirmasi" />
                                            <flux:button wire:click="updateStatus({{ $booking->id }}, 'cancelled')" wire:confirm="Yakin ingin membatalkan booking ini?" size="sm" variant="danger" icon="x-mark" title="Batalkan" />
                                        @endif
                                        <flux:button wire:click="deleteBooking({{ $booking->id }})" wire:confirm="Yakin ingin menghapus booking ini?" size="sm" variant="ghost" icon="trash" title="Hapus" />
                                    </div>
                                </x-cell>
                            </x-row>
                        @empty
                            <x-row>
                                <x-cell colspan="8" class="text-center py-8 text-zinc-500">
                                    Tidak ada booking ditemukan
                                </x-cell>
                            </x-row>
                        @endforelse
                    </x-rows>
                </x-table>
            </div>

            <div class="mt-4">
                {{ $bookings->links() }}
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


