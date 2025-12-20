<?php

use App\Models\SeatingSpot;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public function deleteSpot(int $id): void
    {
        $spot = SeatingSpot::findOrFail($id);
        $spot->delete();
        
        session()->flash('message', 'Spot duduk berhasil dihapus!');
    }

    public function with(): array
    {
        return [
            'spots' => SeatingSpot::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->latest()
                ->paginate(10),
        ];
    }
}; ?>

<div>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <flux:heading size="xl">Spot Duduk</flux:heading>
                <flux:subheading>Kelola area tempat duduk cafe</flux:subheading>
            </div>
            <flux:button href="{{ route('admin.seating-spots.create') }}" variant="primary" icon="plus">
                Tambah Spot
            </flux:button>
        </div>

        @if (session('message'))
            <flux:callout variant="success" icon="check-circle">
                {{ session('message') }}
            </flux:callout>
        @endif

        <x-card>
            <div class="mb-4">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari spot..." icon="magnifying-glass" />
            </div>

            <x-table>
                <x-columns>
                    <x-column>Nama</x-column>
                    <x-column>Deskripsi</x-column>
                    <x-column>Kapasitas</x-column>
                    <x-column>Status</x-column>
                    <x-column>Aksi</x-column>
                </x-columns>

                <x-rows>
                    @forelse ($spots as $spot)
                        <x-row :key="$spot->id">
                            <x-cell class="font-medium">{{ $spot->name }}</x-cell>
                            <x-cell>{{ Str::limit($spot->description, 50) ?: '-' }}</x-cell>
                            <x-cell>{{ $spot->capacity ? $spot->capacity . ' orang' : '-' }}</x-cell>
                            <x-cell>
                                @if ($spot->is_active)
                                    <flux:badge color="green">Aktif</flux:badge>
                                @else
                                    <flux:badge color="red">Nonaktif</flux:badge>
                                @endif
                            </x-cell>
                            <x-cell>
                                <div class="flex gap-2">
                                    <flux:button href="{{ route('admin.seating-spots.edit', $spot) }}" size="sm" icon="pencil" />
                                    <flux:button wire:click="deleteSpot({{ $spot->id }})" wire:confirm="Yakin ingin menghapus spot ini?" size="sm" variant="danger" icon="trash" />
                                </div>
                            </x-cell>
                        </x-row>
                    @empty
                        <x-row>
                            <x-cell colspan="5" class="text-center py-8 text-zinc-500">
                                Tidak ada spot duduk ditemukan
                            </x-cell>
                        </x-row>
                    @endforelse
                </x-rows>
            </x-table>

            <div class="mt-4">
                {{ $spots->links() }}
            </div>
        </x-card>
    </div>
</div>
