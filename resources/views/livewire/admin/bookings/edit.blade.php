<?php

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Menu;
use App\Models\SeatingSpot;
use Livewire\Volt\Component;

new class extends Component {
    public Booking $booking;
    
    public string $customer_name = '';
    public string $booking_date = '';
    public int $guest_count = 1;
    public string $whatsapp = '';
    public string $instagram = '';
    public string $seating_spot_id = '';
    public string $status = '';
    public string $notes = '';
    
    // Menu items management
    public array $items = [];
    public string $newMenuId = '';
    public int $newQuantity = 1;
    public array $selectedVariants = [];

    public function mount(Booking $booking): void
    {
        $this->booking = $booking->load('items.menu');
        $this->customer_name = $booking->customer_name;
        $this->booking_date = $booking->booking_date->format('Y-m-d');
        $this->guest_count = $booking->guest_count;
        $this->whatsapp = $booking->whatsapp;
        $this->instagram = $booking->instagram ?? '';
        $this->seating_spot_id = $booking->seating_spot_id;
        $this->status = $booking->status;
        $this->notes = $booking->notes ?? '';
        
        // Load existing items
        foreach ($booking->items as $item) {
            $optionsText = '';
            if (!empty($item->selected_options)) {
                $optionsText = ' (' . implode(', ', $item->selected_options) . ')';
            }
            $this->items[$item->id] = [
                'id' => $item->id,
                'menu_name' => ($item->menu->name ?? 'Menu tidak tersedia') . $optionsText,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
                'selected_options' => $item->selected_options ?? [],
            ];
        }
    }

    public function updatedNewMenuId(): void
    {
        $this->selectedVariants = [];
    }

    public function updateItemQuantity(int $itemId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeItem($itemId);
        } else {
            $this->items[$itemId]['quantity'] = $quantity;
        }
    }

    public function removeItem(int $itemId): void
    {
        unset($this->items[$itemId]);
    }

    public function addItem(): void
    {
        if (empty($this->newMenuId) || $this->newQuantity < 1) return;

        $menu = Menu::with('variants.options')->find($this->newMenuId);
        if (!$menu) return;

        // Validate required variants
        foreach ($menu->variants as $variant) {
            if ($variant->is_required && empty($this->selectedVariants[$variant->id])) {
                session()->flash('variant_error', "Varian '{$variant->name}' wajib dipilih!");
                return;
            }
        }

        // Calculate price with variant adjustments
        $priceAdjustment = 0;
        $optionLabels = [];
        
        foreach ($menu->variants as $variant) {
            if (isset($this->selectedVariants[$variant->id])) {
                $option = $variant->options->find($this->selectedVariants[$variant->id]);
                if ($option) {
                    $priceAdjustment += $option->price_adjustment;
                    $optionLabels[$variant->name] = $option->name;
                }
            }
        }

        $finalPrice = $menu->price + $priceAdjustment;
        $optionsText = !empty($optionLabels) ? ' (' . implode(', ', $optionLabels) . ')' : '';

        // Create the booking item
        $item = BookingItem::create([
            'booking_id' => $this->booking->id,
            'menu_id' => $menu->id,
            'quantity' => $this->newQuantity,
            'unit_price' => $finalPrice,
            'subtotal' => $finalPrice * $this->newQuantity,
            'selected_options' => $optionLabels,
        ]);

        $this->items[$item->id] = [
            'id' => $item->id,
            'menu_name' => $menu->name . $optionsText,
            'unit_price' => $finalPrice,
            'quantity' => $this->newQuantity,
            'selected_options' => $optionLabels,
        ];

        $this->newMenuId = '';
        $this->newQuantity = 1;
        $this->selectedVariants = [];
        
        $this->recalculateBookingTotals();
    }

    public function recalculateBookingTotals(): void
    {
        $this->booking->refresh();
        $this->booking->recalculateTotals();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'customer_name' => 'required|string|max:255',
            'booking_date' => 'required|date',
            'guest_count' => 'required|integer|min:1',
            'whatsapp' => 'required|string|max:20',
            'instagram' => 'nullable|string|max:255',
            'seating_spot_id' => 'required|exists:seating_spots,id',
            'status' => 'required|in:pending,confirmed,cancelled',
            'notes' => 'nullable|string',
        ]);

        // Update booking details
        $this->booking->update($validated);
        
        // Update/delete existing items
        foreach ($this->items as $itemId => $itemData) {
            $existingItem = BookingItem::find($itemId);
            if ($existingItem) {
                $existingItem->update([
                    'quantity' => $itemData['quantity'],
                    'subtotal' => $itemData['unit_price'] * $itemData['quantity'],
                ]);
            }
        }
        
        // Delete removed items
        $existingIds = array_keys($this->items);
        BookingItem::where('booking_id', $this->booking->id)
            ->whereNotIn('id', $existingIds)
            ->delete();
        
        // Recalculate totals
        $this->recalculateBookingTotals();

        session()->flash('message', 'Booking berhasil diperbarui!');
        $this->redirect(route('admin.bookings.show', $this->booking));
    }

    public function getSubtotalProperty(): float
    {
        $subtotal = 0;
        foreach ($this->items as $item) {
            $subtotal += $item['unit_price'] * $item['quantity'];
        }
        return $subtotal;
    }

    public function getSelectedMenuProperty(): ?Menu
    {
        if (!$this->newMenuId) return null;
        return Menu::with('variants.options')->find($this->newMenuId);
    }

    public function with(): array
    {
        return [
            'seatingSpots' => SeatingSpot::active()->get(),
            'menus' => Menu::where('is_active', true)->with('variants.options')->orderBy('name')->get(),
            'subtotal' => $this->subtotal,
            'tax' => $this->subtotal * (config('booking.tax_rate', 10) / 100),
            'total' => $this->subtotal * (1 + config('booking.tax_rate', 10) / 100),
            'selectedMenu' => $this->selectedMenu,
        ];
    }
}; ?>

<div>
    <div class="space-y-6">
        <div>
            <flux:heading size="xl">Edit Booking</flux:heading>
            <flux:subheading>Kode: {{ $booking->booking_code }}</flux:subheading>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Customer Info -->
            <x-card>
                <flux:heading size="lg" class="mb-4">Data Pelanggan</flux:heading>
                <form wire:submit="save" class="space-y-4">
                    <flux:input wire:model="customer_name" label="Nama Pelanggan" required />
                    
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="booking_date" label="Tanggal Booking" type="date" required />
                        <flux:input wire:model="guest_count" label="Jumlah Tamu" type="number" min="1" required />
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="whatsapp" label="WhatsApp" required />
                        <flux:input wire:model="instagram" label="Instagram" />
                    </div>
                    
                    <flux:select wire:model="seating_spot_id" label="Spot Duduk" required>
                        @foreach ($seatingSpots as $spot)
                            <option value="{{ $spot->id }}">{{ $spot->name }}</option>
                        @endforeach
                    </flux:select>
                    
                    <flux:select wire:model="status" label="Status" required>
                        <option value="pending">Menunggu Konfirmasi</option>
                        <option value="confirmed">Dikonfirmasi</option>
                        <option value="cancelled">Dibatalkan</option>
                    </flux:select>
                    
                    <flux:textarea wire:model="notes" label="Catatan" rows="2" />

                    <div class="flex gap-3 pt-4">
                        <flux:button type="submit" variant="primary">Simpan Perubahan</flux:button>
                        <flux:button href="{{ route('admin.bookings.show', $booking) }}" variant="ghost">Batal</flux:button>
                    </div>
                </form>
            </x-card>

            <!-- Menu Items -->
            <x-card>
                <flux:heading size="lg" class="mb-4">Pesanan Menu</flux:heading>
                
                <!-- Add New Item -->
                <div class="mb-4 p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg space-y-3">
                    <flux:heading size="sm">Tambah Menu</flux:heading>
                    
                    @if (session('variant_error'))
                        <div class="p-3 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 text-red-700 dark:text-red-400 rounded-lg text-sm">
                            {{ session('variant_error') }}
                        </div>
                    @endif
                    
                    <flux:select wire:model.live="newMenuId" placeholder="Pilih Menu">
                        <option value="">-- Pilih Menu --</option>
                        @foreach ($menus as $menu)
                            <option value="{{ $menu->id }}">{{ $menu->name }} - Rp {{ number_format($menu->price, 0, ',', '.') }}</option>
                        @endforeach
                    </flux:select>

                    <!-- Variant Options -->
                    @if ($selectedMenu && $selectedMenu->variants->count() > 0)
                        <div class="space-y-2 p-3 bg-white dark:bg-zinc-700 rounded-lg">
                            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">Pilih Varian:</p>
                            @foreach ($selectedMenu->variants as $variant)
                                <div>
                                    <flux:label class="text-xs">
                                        {{ $variant->name }}
                                        @if ($variant->is_required)
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </flux:label>
                                    <flux:select wire:model="selectedVariants.{{ $variant->id }}" size="sm">
                                        <option value="">-- Pilih {{ $variant->name }} {{ $variant->is_required ? '(wajib)' : '' }} --</option>
                                        @foreach ($variant->options as $option)
                                            <option value="{{ $option->id }}">
                                                {{ $option->name }}
                                                @if ($option->price_adjustment != 0)
                                                    ({{ $option->price_adjustment > 0 ? '+' : '' }}Rp {{ number_format($option->price_adjustment, 0, ',', '.') }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </flux:select>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex gap-2">
                        <div class="w-24">
                            <flux:label class="text-xs">Qty</flux:label>
                            <flux:input wire:model="newQuantity" type="number" min="1" />
                        </div>
                        <div class="flex-1 flex items-end">
                            <flux:button wire:click="addItem" icon="plus" variant="primary" class="w-full">Tambah ke Pesanan</flux:button>
                        </div>
                    </div>
                </div>

                <!-- Current Items -->
                <div class="space-y-2">
                    @forelse ($items as $itemId => $item)
                        <div class="flex items-center justify-between p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                            <div class="flex-1">
                                <div class="font-medium">{{ $item['menu_name'] }}</div>
                                <div class="text-sm text-zinc-500">Rp {{ number_format($item['unit_price'], 0, ',', '.') }} / item</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="number" wire:change="updateItemQuantity({{ $itemId }}, $event.target.value)" value="{{ $item['quantity'] }}" min="1" class="w-16 px-2 py-1 border rounded text-center">
                                <span class="w-24 text-right font-medium">Rp {{ number_format($item['unit_price'] * $item['quantity'], 0, ',', '.') }}</span>
                                <flux:button wire:click="removeItem({{ $itemId }})" size="sm" variant="danger" icon="trash" />
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-zinc-500">
                            Belum ada menu yang dipilih
                        </div>
                    @endforelse
                </div>

                <!-- Totals -->
                @if (count($items) > 0)
                    <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-600 dark:text-zinc-400">Subtotal</span>
                            <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-600 dark:text-zinc-400">PPN (10%)</span>
                            <span>Rp {{ number_format($tax, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-lg">
                            <span>Total</span>
                            <span>Rp {{ number_format($total, 0, ',', '.') }}</span>
                        </div>
                    </div>
                @endif
            </x-card>
        </div>
    </div>
</div>
