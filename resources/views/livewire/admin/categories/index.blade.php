<?php

use App\Models\Category;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public function deleteCategory(int $id): void
    {
        $category = Category::findOrFail($id);
        $category->delete();
        
        session()->flash('message', 'Kategori berhasil dihapus!');
    }

    public function with(): array
    {
        return [
            'categories' => Category::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->ordered()
                ->paginate(10),
        ];
    }
}; ?>

<div>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <flux:heading size="xl">Kategori Menu</flux:heading>
                <flux:subheading>Kelola kategori menu Anda</flux:subheading>
            </div>
            <flux:button href="{{ route('admin.categories.create') }}" variant="primary" icon="plus">
                Tambah Kategori
            </flux:button>
        </div>

        @if (session('message'))
            <flux:callout variant="success" icon="check-circle">
                {{ session('message') }}
            </flux:callout>
        @endif

        <x-card>
            <div class="mb-4">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari kategori..." icon="magnifying-glass" />
            </div>

            <x-table>
                <x-columns>
                    <x-column>Nama</x-column>
                    <x-column>Deskripsi</x-column>
                    <x-column>Urutan</x-column>
                    <x-column>Status</x-column>
                    <x-column>Aksi</x-column>
                </x-columns>

                <x-rows>
                    @forelse ($categories as $category)
                        <x-row :key="$category->id">
                            <x-cell class="font-medium">{{ $category->name }}</x-cell>
                            <x-cell>{{ Str::limit($category->description, 50) ?: '-' }}</x-cell>
                            <x-cell>{{ $category->sort_order }}</x-cell>
                            <x-cell>
                                @if ($category->is_active)
                                    <flux:badge color="green">Aktif</flux:badge>
                                @else
                                    <flux:badge color="red">Nonaktif</flux:badge>
                                @endif
                            </x-cell>
                            <x-cell>
                                <div class="flex gap-2">
                                    <flux:button href="{{ route('admin.categories.edit', $category) }}" size="sm" icon="pencil" />
                                    <flux:button wire:click="deleteCategory({{ $category->id }})" wire:confirm="Yakin ingin menghapus kategori ini?" size="sm" variant="danger" icon="trash" />
                                </div>
                            </x-cell>
                        </x-row>
                    @empty
                        <x-row>
                            <x-cell colspan="5" class="text-center py-8 text-zinc-500">
                                Tidak ada kategori ditemukan
                            </x-cell>
                        </x-row>
                    @endforelse
                </x-rows>
            </x-table>

            <div class="mt-4">
                {{ $categories->links() }}
            </div>
        </x-card>
    </div>
</div>
