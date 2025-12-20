<?php

use App\Models\Menu;
use App\Models\Category;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $categoryFilter = '';

    public function deleteMenu(int $id): void
    {
        $menu = Menu::findOrFail($id);
        
        // Delete image if exists
        if ($menu->image) {
            \Storage::disk('public')->delete($menu->image);
        }
        
        $menu->delete();
        
        session()->flash('message', 'Menu berhasil dihapus!');
    }

    public function with(): array
    {
        return [
            'menus' => Menu::query()
                ->with('category', 'variants.options')
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->when($this->categoryFilter, fn($q) => $q->where('category_id', $this->categoryFilter))
                ->latest()
                ->paginate(10),
            'categories' => Category::ordered()->get(),
        ];
    }
}; ?>

<div>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <flux:heading size="xl">Menu</flux:heading>
                <flux:subheading>Kelola menu restoran Anda</flux:subheading>
            </div>
            <flux:button href="{{ route('admin.menus.create') }}" variant="primary" icon="plus">
                Tambah Menu
            </flux:button>
        </div>

        @if (session('message'))
            <flux:callout variant="success" icon="check-circle">
                {{ session('message') }}
            </flux:callout>
        @endif

        <x-card>
            <div class="flex flex-col sm:flex-row gap-4 mb-4">
                <div class="flex-1">
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari menu..." icon="magnifying-glass" />
                </div>
                <div class="w-full sm:w-48">
                    <flux:select wire:model.live="categoryFilter">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </flux:select>
                </div>
            </div>

            <x-table>
                <x-columns>
                    <x-column>Gambar</x-column>
                    <x-column>Nama</x-column>
                    <x-column>Kategori</x-column>
                    <x-column>Harga</x-column>
                    <x-column>Varian</x-column>
                    <x-column>Status</x-column>
                    <x-column>Aksi</x-column>
                </x-columns>

                <x-rows>
                    @forelse ($menus as $menu)
                        <x-row :key="$menu->id">
                            <x-cell>
                                @if ($menu->image_url)
                                    <img src="{{ $menu->image_url }}" alt="{{ $menu->name }}" class="w-12 h-12 object-cover rounded-lg" />
                                @else
                                    <div class="w-12 h-12 bg-zinc-200 dark:bg-zinc-700 rounded-lg flex items-center justify-center">
                                        <flux:icon.photo class="size-6 text-zinc-400" />
                                    </div>
                                @endif
                            </x-cell>
                            <x-cell class="font-medium">{{ $menu->name }}</x-cell>
                            <x-cell>{{ $menu->category->name }}</x-cell>
                            <x-cell>Rp {{ number_format($menu->price, 0, ',', '.') }}</x-cell>
                            <x-cell>
                                @if ($menu->variants->count() > 0)
                                    <flux:badge>{{ $menu->variants->count() }} varian</flux:badge>
                                @else
                                    <span class="text-zinc-400">-</span>
                                @endif
                            </x-cell>
                            <x-cell>
                                @if ($menu->is_active)
                                    <flux:badge color="green">Aktif</flux:badge>
                                @else
                                    <flux:badge color="red">Nonaktif</flux:badge>
                                @endif
                            </x-cell>
                            <x-cell>
                                <div class="flex gap-2">
                                    <flux:button href="{{ route('admin.menus.edit', $menu) }}" size="sm" icon="pencil" />
                                    <flux:button wire:click="deleteMenu({{ $menu->id }})" wire:confirm="Yakin ingin menghapus menu ini?" size="sm" variant="danger" icon="trash" />
                                </div>
                            </x-cell>
                        </x-row>
                    @empty
                        <x-row>
                            <x-cell colspan="7" class="text-center py-8 text-zinc-500">
                                Tidak ada menu ditemukan
                            </x-cell>
                        </x-row>
                    @endforelse
                </x-rows>
            </x-table>

            <div class="mt-4">
                {{ $menus->links() }}
            </div>
        </x-card>
    </div>
</div>
