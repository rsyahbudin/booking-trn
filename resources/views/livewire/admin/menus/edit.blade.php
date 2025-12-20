<?php

use App\Models\Menu;
use App\Models\Category;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public Menu $menu;
    public string $name = '';
    public string $category_id = '';
    public string $price = '';
    public string $description = '';
    public $image = null;
    public ?string $existing_image = null;
    public bool $is_active = true;
    
    public array $variants = [];

    public function mount(Menu $menu): void
    {
        $this->menu = $menu->load('variants.options');
        $this->name = $menu->name;
        $this->category_id = $menu->category_id;
        $this->price = $menu->price;
        $this->description = $menu->description ?? '';
        $this->existing_image = $menu->image;
        $this->is_active = $menu->is_active;

        // Load existing variants
        foreach ($menu->variants as $variant) {
            $options = [];
            foreach ($variant->options as $option) {
                $options[] = [
                    'id' => $option->id,
                    'name' => $option->name,
                    'price_adjustment' => $option->price_adjustment,
                ];
            }
            $this->variants[] = [
                'id' => $variant->id,
                'name' => $variant->name,
                'is_required' => $variant->is_required,
                'options' => $options,
            ];
        }
    }

    public function addVariant(): void
    {
        $this->variants[] = [
            'id' => null,
            'name' => '',
            'is_required' => true,
            'options' => [
                ['id' => null, 'name' => '', 'price_adjustment' => 0],
            ],
        ];
    }

    public function removeVariant(int $index): void
    {
        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
    }

    public function addOption(int $variantIndex): void
    {
        $this->variants[$variantIndex]['options'][] = ['id' => null, 'name' => '', 'price_adjustment' => 0];
    }

    public function removeOption(int $variantIndex, int $optionIndex): void
    {
        unset($this->variants[$variantIndex]['options'][$optionIndex]);
        $this->variants[$variantIndex]['options'] = array_values($this->variants[$variantIndex]['options']);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
            'variants' => 'array',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.is_required' => 'boolean',
            'variants.*.options' => 'array|min:1',
            'variants.*.options.*.name' => 'required|string|max:255',
            'variants.*.options.*.price_adjustment' => 'numeric',
        ]);

        $imagePath = $this->existing_image;
        if ($this->image) {
            // Delete old image
            if ($this->existing_image) {
                \Storage::disk('public')->delete($this->existing_image);
            }
            $imagePath = $this->image->store('menus', 'public');
        }

        $this->menu->update([
            'name' => $validated['name'],
            'category_id' => $validated['category_id'],
            'price' => $validated['price'],
            'description' => $validated['description'],
            'image' => $imagePath,
            'is_active' => $validated['is_active'],
        ]);

        // Delete old variants and recreate
        $this->menu->variants()->delete();

        foreach ($this->variants as $variantData) {
            $variant = $this->menu->variants()->create([
                'name' => $variantData['name'],
                'is_required' => $variantData['is_required'] ?? true,
            ]);

            foreach ($variantData['options'] as $optionData) {
                $variant->options()->create([
                    'name' => $optionData['name'],
                    'price_adjustment' => $optionData['price_adjustment'] ?? 0,
                ]);
            }
        }

        session()->flash('message', 'Menu berhasil diperbarui!');
        $this->redirect(route('admin.menus.index'));
    }

    public function with(): array
    {
        return [
            'categories' => Category::active()->ordered()->get(),
        ];
    }
}; ?>

<div>
    <div class="space-y-6">
        <div>
            <flux:heading size="xl">Edit Menu</flux:heading>
            <flux:subheading>Edit menu: {{ $menu->name }}</flux:subheading>
        </div>

        <form wire:submit="save" class="space-y-6">
            <x-card class="max-w-3xl">
                <flux:heading size="lg" class="mb-4">Informasi Menu</flux:heading>
                
                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:input wire:model="name" label="Nama Menu" placeholder="Contoh: Paket Hemat A" required />
                        
                        <flux:select wire:model="category_id" label="Kategori" required>
                            <option value="">Pilih Kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    
                    <flux:input wire:model="price" label="Harga (Rp)" type="number" min="0" placeholder="45000" required />
                    
                    <flux:textarea wire:model="description" label="Deskripsi" placeholder="Deskripsi menu (opsional)" rows="3" />
                    
                    <div>
                        <flux:label>Gambar Menu</flux:label>
                        @if ($existing_image && !$image)
                            <div class="mt-2 mb-2">
                                <img src="{{ asset('storage/' . $existing_image) }}" alt="{{ $name }}" class="w-32 h-32 object-cover rounded-lg" />
                            </div>
                        @endif
                        <input type="file" wire:model="image" accept="image/*" class="mt-1 block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-300" />
                        @error('image') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        
                        @if ($image)
                            <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="mt-2 w-32 h-32 object-cover rounded-lg" />
                        @endif
                    </div>
                    
                    <flux:switch wire:model="is_active" label="Aktif" description="Menu aktif akan ditampilkan di halaman booking" />
                </div>
            </x-card>

            <!-- Variants Section -->
            <x-card class="max-w-3xl">
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="lg">Varian Menu</flux:heading>
                    <flux:button type="button" wire:click="addVariant" size="sm" icon="plus">Tambah Varian</flux:button>
                </div>

                @if (count($this->variants) === 0)
                    <p class="text-zinc-500 text-center py-4">Belum ada varian. Klik "Tambah Varian" untuk menambahkan pilihan seperti "Pilihan Ayam" atau "Pilihan Sambal".</p>
                @endif

                <div class="space-y-6">
                    @foreach ($this->variants as $vIndex => $variant)
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 space-y-4">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <flux:input wire:model="variants.{{ $vIndex }}.name" label="Nama Varian" placeholder="Contoh: Pilihan Ayam" required />
                                </div>
                                <flux:button type="button" wire:click="removeVariant({{ $vIndex }})" size="sm" variant="danger" icon="trash" />
                            </div>

                            <flux:switch wire:model="variants.{{ $vIndex }}.is_required" label="Wajib dipilih" />

                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <flux:label>Opsi</flux:label>
                                    <flux:button type="button" wire:click="addOption({{ $vIndex }})" size="xs" icon="plus">Tambah Opsi</flux:button>
                                </div>

                                <div class="space-y-2">
                                    @foreach ($variant['options'] as $oIndex => $option)
                                        <div class="flex gap-2 items-end">
                                            <div class="flex-1">
                                                <flux:input wire:model="variants.{{ $vIndex }}.options.{{ $oIndex }}.name" placeholder="Nama opsi (contoh: Paha)" size="sm" />
                                            </div>
                                            <div class="w-32">
                                                <flux:input wire:model="variants.{{ $vIndex }}.options.{{ $oIndex }}.price_adjustment" type="number" placeholder="+Harga" size="sm" />
                                            </div>
                                            @if (count($variant['options']) > 1)
                                                <flux:button type="button" wire:click="removeOption({{ $vIndex }}, {{ $oIndex }})" size="sm" variant="ghost" icon="x-mark" />
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>

            <div class="flex gap-3 max-w-3xl">
                <flux:button type="submit" variant="primary">Simpan Perubahan</flux:button>
                <flux:button href="{{ route('admin.menus.index') }}" variant="ghost">Batal</flux:button>
            </div>
        </form>
    </div>
</div>
