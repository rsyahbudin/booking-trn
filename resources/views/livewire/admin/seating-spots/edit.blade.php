<?php

use App\Models\SeatingSpot;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public SeatingSpot $spot;
    public string $name = '';
    public string $description = '';
    public ?int $capacity = null;
    public $image = null;
    public ?string $existing_image = null;
    public bool $is_active = true;

    public function mount(SeatingSpot $spot): void
    {
        $this->spot = $spot;
        $this->name = $spot->name;
        $this->description = $spot->description ?? '';
        $this->capacity = $spot->capacity;
        $this->existing_image = $spot->image;
        $this->is_active = $spot->is_active;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer|min:1',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ]);

        if ($this->image) {
            // Delete old image if exists
            if ($this->existing_image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($this->existing_image);
            }
            $validated['image'] = $this->image->store('seating-spots', 'public');
        }

        $this->spot->update($validated);

        session()->flash('message', 'Spot duduk berhasil diperbarui!');
        $this->redirect(route('admin.seating-spots.index'));
    }

    public function deleteImage(): void
    {
        if ($this->existing_image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($this->existing_image);
            $this->spot->update(['image' => null]);
            $this->existing_image = null;
        }
    }
}; ?>

<div>
    <div class="space-y-6">
        <div>
            <flux:heading size="xl">Edit Spot Duduk</flux:heading>
            <flux:subheading>Edit spot: {{ $spot->name }}</flux:subheading>
        </div>

        <x-card class="max-w-2xl">
            <form wire:submit="save" class="space-y-6">
                <flux:input wire:model="name" label="Nama Spot" placeholder="Contoh: Indoor AC, VIP Room" required />
                
                <flux:textarea wire:model="description" label="Deskripsi" placeholder="Deskripsi spot (opsional)" rows="3" />
                
                <flux:input wire:model="capacity" label="Kapasitas" type="number" min="1" placeholder="Jumlah orang maksimal" />
                
                <div>
                    <flux:label>Foto Spot</flux:label>
                    @if($existing_image)
                        <div class="mt-2 flex items-start gap-4">
                            <img src="{{ asset('storage/' . $existing_image) }}" class="h-32 w-auto object-cover rounded-lg" />
                            <flux:button type="button" wire:click="deleteImage" size="sm" variant="danger" icon="trash">Hapus</flux:button>
                        </div>
                    @endif
                    <input type="file" wire:model="image" accept="image/*" 
                        class="mt-2 block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100" />
                    @if($image)
                        <img src="{{ $image->temporaryUrl() }}" class="mt-2 h-32 w-auto object-cover rounded-lg" />
                    @endif
                    @error('image') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                
                <flux:switch wire:model="is_active" label="Aktif" description="Spot aktif akan tersedia untuk booking" />

                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary">Simpan Perubahan</flux:button>
                    <flux:button href="{{ route('admin.seating-spots.index') }}" variant="ghost">Batal</flux:button>
                </div>
            </form>
        </x-card>
    </div>
</div>
