<?php

use App\Models\Category;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';
    public string $description = '';
    public bool $is_active = true;
    public int $sort_order = 0;

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        Category::create($validated);

        session()->flash('message', 'Kategori berhasil ditambahkan!');
        $this->redirect(route('admin.categories.index'));
    }
}; ?>

<div>
    <div class="space-y-6">
        <div>
            <flux:heading size="xl">Tambah Kategori</flux:heading>
            <flux:subheading>Buat kategori menu baru</flux:subheading>
        </div>

        <x-card class="max-w-2xl">
            <form wire:submit="save" class="space-y-6">
                <flux:input wire:model="name" label="Nama Kategori" placeholder="Contoh: Paket Buka Puasa" required />
                
                <flux:textarea wire:model="description" label="Deskripsi" placeholder="Deskripsi kategori (opsional)" rows="3" />
                
                <flux:input wire:model="sort_order" label="Urutan" type="number" min="0" />
                
                <flux:switch wire:model="is_active" label="Aktif" description="Kategori aktif akan ditampilkan di halaman booking" />

                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary">Simpan</flux:button>
                    <flux:button href="{{ route('admin.categories.index') }}" variant="ghost">Batal</flux:button>
                </div>
            </form>
        </x-card>
    </div>
</div>
