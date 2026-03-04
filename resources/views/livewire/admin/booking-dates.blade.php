<?php

use App\Models\BookingDate;
use App\Models\Booking;
use Livewire\Volt\Component;
use Carbon\Carbon;

new class extends Component {
    public int $daysToShow = 16;
    public array $closedDates = [];
    public array $dateNotes = [];
    public array $bookingCounts = [];

    // Range close
    public string $rangeStart = '';
    public string $rangeEnd = '';
    public string $rangeNote = '';
    public bool $showRangeModal = false;

    // Single date note edit
    public string $editingDate = '';
    public string $editingNote = '';
    public bool $showNoteModal = false;

    public function mount(): void
    {
        $this->loadDates();
    }

    public function loadDates(): void
    {
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays($this->daysToShow);

        // Load closed dates
        $bookingDates = BookingDate::whereBetween('date', [$startDate, $endDate])->get();

        $this->closedDates = [];
        $this->dateNotes = [];

        foreach ($bookingDates as $bd) {
            $dateStr = $bd->date->toDateString();
            if (!$bd->is_open) {
                $this->closedDates[] = $dateStr;
            }
            if ($bd->note) {
                $this->dateNotes[$dateStr] = $bd->note;
            }
        }

        // Load booking counts per date
        $counts = Booking::whereBetween('booking_date', [$startDate, $endDate])
            ->whereIn('status', ['pending', 'confirmed'])
            ->selectRaw('booking_date, COUNT(*) as count, SUM(guest_count) as total_pax')
            ->groupBy('booking_date')
            ->get();

        $this->bookingCounts = [];
        foreach ($counts as $c) {
            $this->bookingCounts[$c->booking_date->toDateString()] = [
                'count' => $c->count,
                'pax' => $c->total_pax,
            ];
        }
    }

    public function toggleDate(string $date): void
    {
        $bookingDate = BookingDate::where('date', $date)->first();

        if ($bookingDate) {
            $bookingDate->update(['is_open' => !$bookingDate->is_open]);
        } else {
            BookingDate::create([
                'date' => $date,
                'is_open' => false,
                'note' => null,
            ]);
        }

        $this->loadDates();
        session()->flash('message', $this->isDateClosed($date)
            ? 'Tanggal ' . Carbon::parse($date)->format('d M Y') . ' telah ditutup'
            : 'Tanggal ' . Carbon::parse($date)->format('d M Y') . ' telah dibuka'
        );
    }

    public function isDateClosed(string $date): bool
    {
        return in_array($date, $this->closedDates);
    }

    public function openRangeModal(): void
    {
        $this->rangeStart = '';
        $this->rangeEnd = '';
        $this->rangeNote = '';
        $this->showRangeModal = true;
    }

    public function closeRangeModal(): void
    {
        $this->showRangeModal = false;
    }

    public function closeRange(): void
    {
        $this->validate([
            'rangeStart' => 'required|date|after_or_equal:today',
            'rangeEnd' => 'required|date|after_or_equal:rangeStart',
        ], [
            'rangeStart.required' => 'Tanggal mulai harus diisi',
            'rangeStart.after_or_equal' => 'Tanggal mulai harus hari ini atau setelahnya',
            'rangeEnd.required' => 'Tanggal akhir harus diisi',
            'rangeEnd.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal mulai',
        ]);

        $start = Carbon::parse($this->rangeStart);
        $end = Carbon::parse($this->rangeEnd);

        while ($start->lte($end)) {
            BookingDate::updateOrCreate(
                ['date' => $start->toDateString()],
                ['is_open' => false, 'note' => $this->rangeNote ?: null]
            );
            $start->addDay();
        }

        $this->loadDates();
        $this->closeRangeModal();

        $startFormatted = Carbon::parse($this->rangeStart)->format('d M Y');
        $endFormatted = Carbon::parse($this->rangeEnd)->format('d M Y');
        session()->flash('message', "Tanggal {$startFormatted} - {$endFormatted} berhasil ditutup");
    }

    public function openAllInRange(): void
    {
        $this->validate([
            'rangeStart' => 'required|date|after_or_equal:today',
            'rangeEnd' => 'required|date|after_or_equal:rangeStart',
        ]);

        $start = Carbon::parse($this->rangeStart);
        $end = Carbon::parse($this->rangeEnd);

        while ($start->lte($end)) {
            BookingDate::updateOrCreate(
                ['date' => $start->toDateString()],
                ['is_open' => true, 'note' => null]
            );
            $start->addDay();
        }

        $this->loadDates();
        $this->closeRangeModal();

        $startFormatted = Carbon::parse($this->rangeStart)->format('d M Y');
        $endFormatted = Carbon::parse($this->rangeEnd)->format('d M Y');
        session()->flash('message', "Tanggal {$startFormatted} - {$endFormatted} berhasil dibuka kembali");
    }

    public function openNoteModal(string $date): void
    {
        $this->editingDate = $date;
        $this->editingNote = $this->dateNotes[$date] ?? '';
        $this->showNoteModal = true;
    }

    public function closeNoteModal(): void
    {
        $this->showNoteModal = false;
        $this->editingDate = '';
        $this->editingNote = '';
    }

    public function saveNote(): void
    {
        BookingDate::updateOrCreate(
            ['date' => $this->editingDate],
            ['note' => $this->editingNote ?: null]
        );

        $this->loadDates();
        $this->closeNoteModal();
        session()->flash('message', 'Catatan berhasil disimpan');
    }

    public function with(): array
    {
        $dates = [];
        $today = Carbon::today();

        for ($i = 0; $i <= $this->daysToShow; $i++) {
            $date = $today->copy()->addDays($i);
            $dateStr = $date->toDateString();
            $dates[] = [
                'date' => $dateStr,
                'day_name' => $date->translatedFormat('D'),
                'day' => $date->format('d'),
                'month' => $date->translatedFormat('M'),
                'full' => $date->translatedFormat('d M Y'),
                'is_today' => $date->isToday(),
                'is_weekend' => $date->isWeekend(),
                'is_closed' => in_array($dateStr, $this->closedDates),
                'note' => $this->dateNotes[$dateStr] ?? null,
                'booking_count' => $this->bookingCounts[$dateStr]['count'] ?? 0,
                'total_pax' => $this->bookingCounts[$dateStr]['pax'] ?? 0,
            ];
        }

        return [
            'dates' => $dates,
            'totalClosed' => count($this->closedDates),
        ];
    }
}; ?>

<div>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <flux:heading size="xl">Kelola Tanggal Booking</flux:heading>
                <flux:subheading>Buka atau tutup tanggal untuk booking. Tanggal yang ditutup tidak bisa dipilih customer.</flux:subheading>
            </div>
            <div class="flex gap-2">
                <flux:button wire:click="openRangeModal" variant="primary" icon="calendar-days">
                    Tutup Range Tanggal
                </flux:button>
            </div>
        </div>

        @if (session('message'))
            <flux:callout variant="success" icon="check-circle">
                {{ session('message') }}
            </flux:callout>
        @endif

        <!-- Stats -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
            <x-card>
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-xl">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $daysToShow + 1 - $totalClosed }}</div>
                        <div class="text-xs text-zinc-500">Tanggal Buka</div>
                    </div>
                </div>
            </x-card>
            <x-card>
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-xl">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalClosed }}</div>
                        <div class="text-xs text-zinc-500">Tanggal Ditutup</div>
                    </div>
                </div>
            </x-card>
            <x-card class="col-span-2 sm:col-span-1">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-xl">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $daysToShow + 1 }}</div>
                        <div class="text-xs text-zinc-500">Total Hari</div>
                    </div>
                </div>
            </x-card>
        </div>

        <!-- Legend -->
        <div class="flex flex-wrap gap-4 text-sm">
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded bg-green-100 dark:bg-green-900/30 border-2 border-green-500"></div>
                <span class="text-zinc-600 dark:text-zinc-400">Buka</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded bg-red-100 dark:bg-red-900/30 border-2 border-red-500"></div>
                <span class="text-zinc-600 dark:text-zinc-400">Ditutup</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded bg-amber-100 dark:bg-amber-900/30 border-2 border-amber-500"></div>
                <span class="text-zinc-600 dark:text-zinc-400">Hari Ini</span>
            </div>
        </div>

        <!-- Calendar Grid -->
        <x-card>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-7 gap-3">
                @foreach ($dates as $d)
                    <div class="relative group rounded-xl border-2 p-3 transition-all duration-200 cursor-pointer hover:shadow-md
                        {{ $d['is_closed']
                            ? 'border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20'
                            : ($d['is_today']
                                ? 'border-amber-400 dark:border-amber-600 bg-amber-50 dark:bg-amber-900/20 ring-2 ring-amber-400/30'
                                : 'border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-600')
                        }}"
                    >
                        <!-- Day Header -->
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <span class="text-xs font-medium {{ $d['is_weekend'] ? 'text-red-500 dark:text-red-400' : 'text-zinc-500 dark:text-zinc-400' }}">
                                    {{ $d['day_name'] }}
                                </span>
                                <div class="text-lg font-bold {{ $d['is_closed'] ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-white' }}">
                                    {{ $d['day'] }}
                                </div>
                                <span class="text-[10px] text-zinc-400">{{ $d['month'] }}</span>
                            </div>

                            <!-- Status Icon -->
                            @if ($d['is_closed'])
                                <div class="p-1.5 bg-red-100 dark:bg-red-900/50 rounded-lg">
                                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                </div>
                            @else
                                <div class="p-1.5 bg-green-100 dark:bg-green-900/50 rounded-lg">
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                                </div>
                            @endif
                        </div>

                        <!-- Booking Count -->
                        @if ($d['booking_count'] > 0)
                            <div class="text-[10px] px-2 py-1 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium mb-2">
                                📋 {{ $d['booking_count'] }} booking · {{ $d['total_pax'] }} pax
                            </div>
                        @endif

                        <!-- Note -->
                        @if ($d['note'])
                            <div class="text-[10px] text-zinc-500 dark:text-zinc-400 truncate mb-2 italic">
                                📝 {{ $d['note'] }}
                            </div>
                        @endif

                        <!-- Action Buttons -->
                        <div class="flex gap-1 mt-auto">
                            <button
                                wire:click="toggleDate('{{ $d['date'] }}')"
                                wire:loading.attr="disabled"
                                class="flex-1 text-[10px] font-bold py-1.5 rounded-lg transition-all active:scale-95
                                    {{ $d['is_closed']
                                        ? 'bg-green-500 hover:bg-green-600 text-white'
                                        : 'bg-red-500 hover:bg-red-600 text-white'
                                    }}"
                            >
                                {{ $d['is_closed'] ? '🔓 Buka' : '🔒 Tutup' }}
                            </button>
                            <button
                                wire:click="openNoteModal('{{ $d['date'] }}')"
                                class="px-2 py-1.5 text-[10px] bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 rounded-lg transition text-zinc-600 dark:text-zinc-300"
                                title="Tambah catatan"
                            >
                                📝
                            </button>
                        </div>

                        <!-- Today marker -->
                        @if ($d['is_today'])
                            <div class="absolute -top-2 left-1/2 -translate-x-1/2">
                                <span class="text-[9px] bg-amber-500 text-white px-2 py-0.5 rounded-full font-bold shadow-sm">HARI INI</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>

    <!-- Range Close Modal -->
    @if ($showRangeModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" wire:click="closeRangeModal"></div>
            <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-full">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-zinc-800 dark:text-white">Tutup/Buka Range Tanggal</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Tutup atau buka beberapa tanggal sekaligus</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Dari Tanggal *</label>
                                <input type="date" wire:model="rangeStart" min="{{ date('Y-m-d') }}" class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-700 text-zinc-800 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                                @error('rangeStart') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Sampai Tanggal *</label>
                                <input type="date" wire:model="rangeEnd" min="{{ date('Y-m-d') }}" class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-700 text-zinc-800 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                                @error('rangeEnd') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Catatan (Opsional)</label>
                            <textarea wire:model="rangeNote" rows="2" placeholder="Contoh: Full book, Libur nasional, dll." class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-700 text-zinc-800 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent"></textarea>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-end gap-3 mt-6 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <button wire:click="closeRangeModal" class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-xl transition font-medium">
                            Batal
                        </button>
                        <button wire:click="openAllInRange" class="px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl transition font-medium flex items-center justify-center gap-2">
                            🔓 Buka Semua
                        </button>
                        <button wire:click="closeRange" class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl transition font-medium flex items-center justify-center gap-2">
                            🔒 Tutup Semua
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Note Modal -->
    @if ($showNoteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" wire:click="closeNoteModal"></div>
            <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-full">
                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-zinc-800 dark:text-white">Catatan Tanggal</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ \Carbon\Carbon::parse($editingDate)->translatedFormat('l, d F Y') }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Catatan</label>
                        <textarea wire:model="editingNote" rows="3" placeholder="Tambahkan catatan untuk tanggal ini..." class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-700 text-zinc-800 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <button wire:click="closeNoteModal" class="px-4 py-2 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition">
                            Batal
                        </button>
                        <button wire:click="saveNote" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition font-medium">
                            💾 Simpan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
