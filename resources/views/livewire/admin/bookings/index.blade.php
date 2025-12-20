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
        return [
            'bookings' => Booking::query()
                ->with('seatingSpot', 'items.menu')
                ->when($this->search, fn($q) => $q->where('customer_name', 'like', "%{$this->search}%")
                    ->orWhere('booking_code', 'like', "%{$this->search}%")
                    ->orWhere('whatsapp', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
                ->when($this->dateFilter, fn($q) => $q->whereDate('booking_date', $this->dateFilter))
                ->latest()
                ->paginate(10),
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
                                    <flux:badge :color="$booking->status_color">{{ $booking->status_label }}</flux:badge>
                                </x-cell>
                                <x-cell>
                                    <div class="flex gap-1">
                                        <flux:button href="{{ route('admin.bookings.show', $booking) }}" size="sm" icon="eye" title="Detail" />
                                        @if ($booking->status === 'pending')
                                            <flux:button wire:click="updateStatus({{ $booking->id }}, 'confirmed')" size="sm" variant="primary" icon="check" title="Konfirmasi" />
                                            <flux:button wire:click="updateStatus({{ $booking->id }}, 'cancelled')" wire:confirm="Yakin ingin membatalkan booking ini?" size="sm" variant="danger" icon="x-mark" title="Batalkan" />
                                        @endif
                                        <flux:button wire:click="deleteBooking({{ $booking->id }})" wire:confirm="Yakin ingin menghapus booking ini?" size="sm" variant="ghost" icon="trash" title="Hapus" />
                                    </div>
                                </x-cell>
                            </x-row>
                        @empty
                            <x-row>
                                <x-cell colspan="7" class="text-center py-8 text-zinc-500">
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
</div>
