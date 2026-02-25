<?php

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingDate;
use App\Models\Category;
use App\Models\Menu;
use App\Models\SeatingSpot;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Carbon\Carbon;

new class extends Component {
    use WithFileUploads;

    public int $step = 1;
    
    // Step 1: Customer Data
    public string $customer_name = '';
    public string $booking_date = '';
    public int $guest_count = 1;
    public string $whatsapp = '';
    public string $instagram = '';
    public string $seating_spot_id = '';
    public string $alternative_seating_spot_id = '';
    public bool $agree_rules = false;
    
    // Step 2: Menu Selection
    public array $cart = [];
    public array $selectedOptions = [];
    public $activeCategory = null;
    public bool $showCart = false;
    
    // Step 3: Payment
    public $payment_proof = null;
    public string $notes = '';
    
    // Computed with tax
    public float $subtotalAmount = 0;
    public float $taxAmount = 0;
    public float $totalAmount = 0;
    public float $dpAmount = 0;

    protected $listeners = ['refreshTotal' => 'calculateTotal'];

    public function mount(): void
    {
        $this->booking_date = now()->format('Y-m-d');
        $this->activeCategory = (string) Category::whereHas('activeMenus')->first()?->id;
    }

    public function getAvailableDatesProperty(): array
    {
        $dates = [];
        $now = Carbon::now();
        $cutoffHour = config('booking.cutoff_hour', 15);
        
        // Generate next 30 days
        for ($i = 0; $i <= 30; $i++) {
            $date = Carbon::today()->addDays($i);
            
            // Skip if today and past cutoff time
            if ($date->isToday() && $now->hour >= $cutoffHour) {
                // Check if force opened
                if (!BookingDate::isAvailableForBooking($date->toDateString())) {
                    continue;
                }
            }
            
            // Check if date is available in BookingDate
            if (BookingDate::isAvailableForBooking($date->toDateString())) {
                $dates[] = $date->toDateString();
            }
        }
        
        return $dates;
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validate([
                'customer_name' => 'required|string|max:255',
                'booking_date' => 'required|date|after_or_equal:today',
                'guest_count' => 'required|integer|min:1',
                'whatsapp' => ['required', 'string', 'max:20', 'regex:/^8[0-9]{8,13}$/'],
                'instagram' => 'nullable|string|max:255',
                'seating_spot_id' => 'required|exists:seating_spots,id',
                'alternative_seating_spot_id' => 'required|exists:seating_spots,id|different:seating_spot_id',
                'agree_rules' => 'accepted',
            ], [
                'customer_name.required' => 'Nama harus diisi',
                'booking_date.required' => 'Tanggal booking harus dipilih',
                'guest_count.required' => 'Jumlah pax harus diisi',
                'guest_count.min' => 'Minimal 1 pax',
                'whatsapp.required' => 'Nomor WhatsApp harus diisi',
                'whatsapp.regex' => 'Format nomor WhatsApp tidak valid (contoh: 8123456789)',
                'seating_spot_id.required' => 'Pilih spot prioritas',
                'alternative_seating_spot_id.required' => 'Pilih spot alternatif',
                'alternative_seating_spot_id.different' => 'Spot alternatif harus berbeda dengan spot prioritas',
                'agree_rules.accepted' => 'Anda harus menyetujui aturan booking',
            ]);

            // Validate date availability
            if (!BookingDate::isAvailableForBooking($this->booking_date)) {
                $this->addError('booking_date', 'Tanggal ini tidak tersedia untuk booking');
                return;
            }
        }
        
        if ($this->step === 2) {
            if (empty($this->cart)) {
                session()->flash('error', 'Pilih minimal 1 menu');
                return;
            }
        }
        
        $this->step++;
        $this->calculateTotal();
    }

    public function prevStep(): void
    {
        $this->step--;
    }

    public function addToCart(int $menuId): void
    {
        $menu = Menu::with('variants.options')->find($menuId);
        
        if (!$menu) return;

        // Validate required variants
        foreach ($menu->variants as $variant) {
            if ($variant->is_required) {
                if (!isset($this->selectedOptions[$menuId][$variant->id]) || empty($this->selectedOptions[$menuId][$variant->id])) {
                    session()->flash('variant_error_' . $menuId, 'Pilih ' . $variant->name . ' terlebih dahulu (wajib)');
                    return;
                }
            }
        }

        $cartKey = $menuId . '_' . md5(json_encode($this->selectedOptions[$menuId] ?? []));
        
        if (isset($this->cart[$cartKey])) {
            $this->cart[$cartKey]['quantity']++;
        } else {
            $priceAdjustment = 0;
            $optionLabels = [];
            
            if (isset($this->selectedOptions[$menuId])) {
                foreach ($menu->variants as $variant) {
                    if (isset($this->selectedOptions[$menuId][$variant->id])) {
                        $option = $variant->options->find($this->selectedOptions[$menuId][$variant->id]);
                        if ($option) {
                            $priceAdjustment += $option->price_adjustment;
                            $optionLabels[$variant->name] = $option->name;
                        }
                    }
                }
            }
            
            $this->cart[$cartKey] = [
                'menu_id' => $menuId,
                'name' => $menu->name,
                'price' => $menu->price + $priceAdjustment,
                'quantity' => 1,
                'options' => $optionLabels,
            ];
        }
        
        $this->calculateTotal();
        $this->selectedOptions[$menuId] = [];
        
        // Dispatch toast notification
        $this->dispatch('cart-updated', name: $menu->name);
    }

    public function removeFromCart(string $cartKey): void
    {
        unset($this->cart[$cartKey]);
        $this->calculateTotal();
    }

    public function updateQuantity(string $cartKey, int $quantity): void
    {
        if ($quantity <= 0) {
            unset($this->cart[$cartKey]);
        } else {
            $this->cart[$cartKey]['quantity'] = $quantity;
        }
        $this->calculateTotal();
    }

    public function calculateTotal(): void
    {
        $this->subtotalAmount = 0;
        foreach ($this->cart as $item) {
            $this->subtotalAmount += $item['price'] * $item['quantity'];
        }
        
        $taxRate = config('booking.tax_rate', 10) / 100;
        $this->taxAmount = $this->subtotalAmount * $taxRate;
        $this->totalAmount = $this->subtotalAmount + $this->taxAmount;
        
        $dpPercentage = config('booking.dp_percentage', 50) / 100;
        $this->dpAmount = ceil($this->totalAmount * $dpPercentage);
    }

    public function submit(): void
    {
        $this->validate([
            'payment_proof' => 'required|image|max:5120',
        ], [
            'payment_proof.required' => 'Upload bukti pembayaran',
            'payment_proof.image' => 'File harus berupa gambar',
            'payment_proof.max' => 'Ukuran maksimal 5MB',
        ]);

        // Save payment proof
        $paymentPath = $this->payment_proof->store('payments', 'public');

        // Create booking with tax
        $booking = Booking::create([
            'customer_name' => $this->customer_name,
            'booking_date' => $this->booking_date,
            'guest_count' => $this->guest_count,
            'whatsapp' => '62' . $this->whatsapp,
            'instagram' => $this->instagram,
            'seating_spot_id' => $this->seating_spot_id,
            'alternative_seating_spot_id' => $this->alternative_seating_spot_id,
            'subtotal_amount' => $this->subtotalAmount,
            'tax_amount' => $this->taxAmount,
            'total_amount' => $this->totalAmount,
            'dp_amount' => $this->dpAmount,
            'payment_proof' => $paymentPath,
            'notes' => $this->notes,
            'status' => 'pending',
        ]);

        // Create booking items
        foreach ($this->cart as $item) {
            BookingItem::create([
                'booking_id' => $booking->id,
                'menu_id' => $item['menu_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'subtotal' => $item['price'] * $item['quantity'],
                'selected_options' => $item['options'],
            ]);
        }

        // Generate WhatsApp message
        $message = $this->generateWhatsAppMessage($booking);
        $waNumber = \App\Models\SiteSetting::get('whatsapp', '6285813035292');
        
        $waUrl = 'https://wa.me/' . $waNumber . '?text=' . urlencode($message);

        session()->flash('booking_success', [
            'code' => $booking->booking_code,
            'wa_url' => $waUrl,
        ]);
        
        $this->redirect(route('booking.form') . '?success=1&code=' . $booking->booking_code);
    }

    private function generateWhatsAppMessage(Booking $booking): string
    {
        $spot = SeatingSpot::find($this->seating_spot_id);
        $alternativeSpot = SeatingSpot::find($this->alternative_seating_spot_id);
        
        // Build menu items string
        $menuItemsText = '';
        foreach ($this->cart as $item) {
            $options = !empty($item['options']) ? ' (' . implode(', ', $item['options']) . ')' : '';
            $menuItemsText .= "• {$item['name']}{$options} x{$item['quantity']} = Rp " . number_format($item['price'] * $item['quantity'], 0, ',', '.') . "\n";
        }
        
        // Get template from settings
        $template = \App\Models\SiteSetting::get('wa_template_customer', $this->getDefaultCustomerTemplate());
        
        // Replace placeholders
        $message = \App\Models\SiteSetting::parseTemplate($template, [
            'booking_code' => $booking->booking_code,
            'customer_name' => $this->customer_name,
            'booking_date' => date('d F Y', strtotime($this->booking_date)),
            'guest_count' => $this->guest_count . ' orang',
            'spot_name' => $spot->name,
            'alternative_spot_name' => $alternativeSpot->name,
            'menu_items' => $menuItemsText,
            'subtotal' => 'Rp ' . number_format($this->subtotalAmount, 0, ',', '.'),
            'tax' => 'Rp ' . number_format($this->taxAmount, 0, ',', '.'),
            'total' => 'Rp ' . number_format($this->totalAmount, 0, ',', '.'),
            'dp_amount' => 'Rp ' . number_format($this->dpAmount, 0, ',', '.'),
            'whatsapp' => $booking->whatsapp,
            'instagram' => $this->instagram ?? '-',
            'notes' => $this->notes ?? '-',
            'payment_proof_url' => url('storage/' . $booking->payment_proof),
        ]);
        
        return $message;
    }

    private function getDefaultCustomerTemplate(): string
    {
        return "Halo, saya ingin konfirmasi booking:\n\n" .
               "Kode: {booking_code}\n" .
               "Nama: {customer_name}\n" .
               "Tanggal: {booking_date}\n" .
               "Jumlah Tamu: {guest_count}\n" .
               "Spot Prioritas: {spot_name}\n" .
               "Spot Alternatif: {alternative_spot_name}\n\n" .
               "*Pesanan:*\n{menu_items}\n" .
               "*Pembayaran:*\n" .
               "Subtotal: {subtotal}\n" .
               "PPN 10%: {tax}\n" .
               "Total: {total}\n" .
               "DP (50%): {dp_amount}\n\n" .
               "Terima kasih!";
    }

    public function with(): array
    {
        return [
            'seatingSpots' => SeatingSpot::active()->get(),
            'categories' => Category::active()->ordered()->with(['activeMenus' => fn($q) => $q->with('variants.options')])->get(),
            'bookingRules' => config('booking.rules', []),
            'availableDates' => $this->availableDates,
        ];
    }
}; ?>

<div class="min-h-screen bg-zinc-50 dark:bg-black font-sans pb-32">
    <!-- Header -->
    <header class="bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md border-b border-zinc-200 dark:border-zinc-800 sticky top-0 z-50">
        <div class="max-w-md mx-auto px-4 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <img src="{{ asset('img/logo-black.png') }}" alt="Logo" class="h-8 w-auto block dark:hidden">
                <img src="{{ asset('img/logo-white.png') }}" alt="Logo" class="h-8 w-auto hidden dark:block">
                <span class="font-bold text-zinc-900 dark:text-white tracking-tight">Booking Puasa Teras Rumah Nenek</span>
            </a>
            @if(!$step == 1)
                <button wire:click="prevStep" class="text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white transition">
                    <span class="sr-only">Kembali</span>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
            @endif
        </div>
    </header>

    <main class="max-w-md md:max-w-5xl mx-auto px-4 pt-6 transition-all duration-300">
        @if (request('success'))
            <!-- Success State -->
            <div class="flex flex-col items-center justify-center min-h-[60vh] text-center animate-fade-in-up">
                <div class="w-24 h-24 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-12 h-12 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2">Booking Berhasil!</h1>
                <p class="text-zinc-500 dark:text-zinc-400 mb-6">Kode: <span class="font-mono font-bold text-zinc-900 dark:text-white bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded">{{ request('code') }}</span></p>
                
                <div class="w-full space-y-3 max-w-md mx-auto">
                    <a href="{{ session('booking_success.wa_url') ?? '#' }}" target="_blank" class="flex w-full items-center justify-center gap-2 bg-[#25D366] hover:bg-[#128C7E] text-white px-6 py-3.5 rounded-xl font-bold shadow-lg shadow-green-500/20 transition transform active:scale-95">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        Konfirmasi via WhatsApp
                    </a>
                    <a href="{{ route('home') }}" class="flex w-full items-center justify-center gap-2 bg-white dark:bg-zinc-800 border-2 border-zinc-100 dark:border-zinc-700 hover:border-zinc-300 text-zinc-700 dark:text-zinc-300 px-6 py-3.5 rounded-xl font-bold transition transform active:scale-95">
                        Kembali ke Home
                    </a>
                </div>
            </div>
        @else
            <!-- Progress Steps (Compact) -->
            <div class="flex items-center justify-between mb-8 px-2 max-w-md mx-auto">
                @foreach ([1 => 'Data', 2 => 'Menu', 3 => 'Bayar'] as $num => $label)
                    <div class="flex flex-col items-center z-10 relative">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300 {{ $step >= $num ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/30 scale-110' : 'bg-zinc-200 dark:bg-zinc-800 text-zinc-400' }}">
                            @if ($step > $num)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            @else
                                {{ $num }}
                            @endif
                        </div>
                        <span class="text-[10px] mt-1 font-medium {{ $step >= $num ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-400' }}">{{ $label }}</span>
                    </div>
                @endforeach
                <!-- Progress Line Background -->
                <div class="absolute left-0 right-0 top-4 h-[2px] bg-zinc-200 dark:bg-zinc-800 -z-0 mx-8 max-w-md"></div>
                <!-- Active Progress Line -->
                <div class="absolute left-0 top-4 h-[2px] bg-amber-500 -z-0 mx-8 transition-all duration-500" style="width: {{ ($step - 1) * 50 }}%; max-width: calc(100% - 4rem);"></div>
            </div>

            @if (session('error'))
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 rounded-xl text-sm flex items-start gap-3 max-w-md mx-auto">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    {{ session('error') }}
                </div>
            @endif

            <!-- Step 1: Customer Data -->
            @if ($step === 1)
                <div x-data="{ showRules: false }" class="space-y-6 animate-fade-in max-w-5xl mx-auto">
                    <div>
                        <h2 class="text-xl font-bold text-zinc-900 dark:text-white">Informasi Pemesan</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Isi data diri Anda dengan benar</p>
                    </div>

                    <div class="md:grid md:grid-cols-2 md:gap-6 space-y-4 md:space-y-0">
                        <!-- Nama -->
                        <div class="relative md:col-span-2">
                            <input type="text" wire:model="customer_name" id="customer_name" class="peer w-full px-4 pt-6 pb-1 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent placeholder-transparent transition shadow-sm" placeholder="Nama Lengkap">
                            <label for="customer_name" class="absolute left-4 top-1 text-xs text-zinc-500 dark:text-zinc-400 transition-all peer-placeholder-shown:text-base peer-placeholder-shown:top-3.5 peer-focus:top-1 peer-focus:text-xs peer-focus:text-amber-600">Nama Lengkap</label>
                            @error('customer_name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Date & Guest Grid -->
                        <div class="grid grid-cols-2 gap-4 md:col-span-1">
                            <div class="relative">
                                <input type="date" wire:model="booking_date" min="{{ date('Y-m-d') }}" id="booking_date" class="peer w-full px-4 pt-5 pb-2 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent placeholder-transparent transition shadow-sm h-[58px] appearance-none leading-tight">
                                <label for="booking_date" class="absolute left-4 top-1 text-xs text-zinc-500 dark:text-zinc-400 transition-all peer-placeholder-shown:text-base peer-placeholder-shown:top-3.5 peer-focus:top-1 peer-focus:text-xs peer-focus:text-amber-600">Tanggal</label>
                            </div>
                            
                            <div class="relative">
                                <input type="number" wire:model="guest_count" min="1" id="guest_count" class="peer w-full px-4 pt-5 pb-2 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent placeholder-transparent transition shadow-sm h-[58px] appearance-none leading-tight">
                                <label for="guest_count" class="absolute left-4 top-1 text-xs text-zinc-500 dark:text-zinc-400 transition-all peer-placeholder-shown:text-base peer-placeholder-shown:top-3.5 peer-focus:top-1 peer-focus:text-xs peer-focus:text-amber-600">Jumlah Pax</label>
                            </div>
                            @error('booking_date') <span class="text-red-500 text-xs block md:hidden col-span-2">{{ $message }}</span> @enderror
                            @error('guest_count') <span class="text-red-500 text-xs block md:hidden col-span-2">{{ $message }}</span> @enderror
                        </div>

                        <!-- WhatsApp & IG -->
                        <div class="space-y-4 md:space-y-0 md:grid md:grid-cols-2 md:gap-4 md:col-span-1">
                            <div class="relative">
                                <div class="absolute left-4 top-3.5 flex items-center pointer-events-none">
                                    <span class="text-zinc-500 font-medium border-r border-zinc-300 dark:border-zinc-600 pr-2 mr-2">+62</span>
                                </div>
                                <input type="tel" wire:model="whatsapp" class="w-full pl-16 pr-4 py-3.5 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent placeholder-zinc-400 transition shadow-sm" placeholder="8123456789">
                                @error('whatsapp') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="relative">
                                <span class="absolute left-4 top-3.5 text-zinc-500 dark:text-zinc-400">@</span>
                                <input type="text" wire:model="instagram" class="w-full pl-10 pr-4 py-3.5 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent placeholder-zinc-400 transition shadow-sm" placeholder="Instagram (Opsional)">
                            </div>
                        </div>

                    </div>

                    <!-- Spot Selection -->
                    <div x-data="{ primarySpot: @entangle('seating_spot_id'), altSpot: @entangle('alternative_seating_spot_id') }" class="max-w-5xl mx-auto">
                        <!-- Primary Spot -->
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-zinc-900 dark:text-white mb-3">
                                Spot Utama <span class="text-amber-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                @foreach ($seatingSpots as $spot)
                                    <label class="cursor-pointer group relative">
                                        <input type="radio" wire:model.live="seating_spot_id" value="{{ $spot->id }}" class="peer sr-only" x-bind:disabled="altSpot === '{{ $spot->id }}'">
                                        <div class="h-full bg-white dark:bg-zinc-800 border-2 border-zinc-200 dark:border-zinc-700 rounded-xl p-3 flex flex-col items-center text-center transition-all duration-200 peer-checked:border-amber-500 peer-checked:bg-amber-50 dark:peer-checked:bg-amber-900/10 peer-disabled:opacity-50 peer-disabled:grayscale">
                                            <div class="w-full aspect-video rounded-lg dark:bg-zinc-700 mb-2 overflow-hidden bg-zinc-100">
                                                @if($spot->image_url)
                                                    <img src="{{ $spot->image_url }}" class="w-full h-full object-cover" alt="{{ $spot->name }}">
                                                @else
                                                    <span class="w-full h-full flex items-center justify-center text-2xl">🪑</span>
                                                @endif
                                            </div>
                                            <span class="font-medium text-xs sm:text-sm text-zinc-800 dark:text-white">{{ $spot->name }}</span>
                                            @if($spot->capacity)
                                                <span class="text-[10px] text-zinc-500">{{ $spot->capacity }} Pax</span>
                                            @endif
                                        </div>
                                        
                                        <!-- Checkmark -->
                                        <div class="absolute top-2 right-2 opacity-0 peer-checked:opacity-100 transition text-amber-500 bg-white rounded-full">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            @error('seating_spot_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Alternative Only shows after primary selected -->
                        <div x-show="primarySpot" x-transition class="mb-6">
                            <label class="block text-sm font-bold text-zinc-900 dark:text-white mb-3">
                                Spot Cadangan <span class="text-blue-500">*</span>
                                <span class="text-xs font-normal text-zinc-500 block">Dipakai jika spot utama penuh</span>
                            </label>
                            <div class="flex overflow-x-auto gap-3 pb-2 -mx-4 px-4 scrollbar-hide md:grid md:grid-cols-4 md:gap-3 md:mx-0 md:px-0 md:overflow-visible">
                                @foreach ($seatingSpots as $spot)
                                    <label class="cursor-pointer shrink-0 w-32 md:w-auto relative">
                                        <input type="radio" wire:model.live="alternative_seating_spot_id" value="{{ $spot->id }}" class="peer sr-only" x-bind:disabled="primarySpot === '{{ $spot->id }}'">
                                        <div class="h-full bg-white dark:bg-zinc-800 border-2 border-zinc-200 dark:border-zinc-700 rounded-xl p-2 flex flex-col items-center text-center transition-all duration-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/10 peer-disabled:opacity-40">
                                            <span class="font-medium text-xs text-zinc-800 dark:text-white line-clamp-1">{{ $spot->name }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            @error('alternative_seating_spot_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Rules -->
                    <div class="bg-amber-50 dark:bg-amber-900/10 rounded-xl p-4 border border-amber-100 dark:border-amber-800/30">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="agree_rules" class="mt-1 w-5 h-5 rounded border-amber-300 text-amber-500 focus:ring-amber-500">
                            <div class="text-sm">
                                <span class="font-bold text-amber-900 dark:text-amber-400 block mb-1">Persetujuan</span>
                                <span class="text-amber-800 dark:text-amber-300/80 leading-relaxed">
                                    Saya telah membaca dan menyetujui seluruh <button type="button" class="underline font-bold" @click="showRules = true">Aturan Booking</button> yang berlaku.
                                </span>
                            </div>
                        </label>
                        @error('agree_rules') <span class="text-red-500 text-xs mt-2 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Desktop Next Button (Moved here) -->
                    <div class="hidden md:flex justify-end mt-8">
                        <button wire:click="nextStep" class="bg-amber-500 hover:bg-amber-600 text-white px-8 py-3.5 rounded-xl font-bold shadow-lg shadow-amber-500/20 transition transform active:scale-95 flex items-center gap-2">
                            Lanjut Menu
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                        </button>
                    </div>

                    <!-- Rules Modal -->
                    <div x-show="showRules" class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display: none;">
                        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showRules = false"></div>
                        <div class="bg-white dark:bg-zinc-900 w-full max-w-lg rounded-2xl shadow-2xl relative z-10 flex flex-col max-h-[80vh]">
                            <div class="p-4 border-b border-zinc-200 dark:border-zinc-800 flex justify-between items-center bg-zinc-50 dark:bg-zinc-800/50 rounded-t-2xl">
                                <h3 class="font-bold text-lg text-zinc-900 dark:text-white">Aturan Booking</h3>
                                <button @click="showRules = false" class="text-zinc-500 hover:text-zinc-800 dark:hover:text-white p-2 rounded-full hover:bg-zinc-200 dark:hover:bg-zinc-700">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                            <div class="p-6 overflow-y-auto text-sm text-zinc-600 dark:text-zinc-300 space-y-2 leading-relaxed">
                                <ul class="list-disc pl-5 space-y-1">
                                    @foreach($bookingRules as $rule)
                                        <li>{{ $rule }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="p-4 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-black/20 rounded-b-2xl">
                                <button @click="showRules = false; $wire.set('agree_rules', true)" class="w-full bg-amber-500 hover:bg-amber-600 text-white py-3 rounded-xl font-bold transition">Saya Setuju</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Step 2: Menu Selection -->
            @if ($step === 2)
                <div x-data="{ activeCategory: @entangle('activeCategory'), showCart: @entangle('showCart') }" class="pb-24 md:pb-0 max-w-5xl mx-auto">
                    <div class="md:grid md:grid-cols-3 md:gap-8 items-start">
                        <!-- Left Column: Menu -->
                        <div class="md:col-span-2">
                    <!-- Sticky Category Pills -->
                    <div class="sticky top-16 z-40 bg-zinc-50/95 dark:bg-black/95 backdrop-blur-sm -mx-4 px-4 py-3 border-b border-zinc-200 dark:border-zinc-800 overflow-x-auto scrollbar-hide flex gap-2">
                        @foreach ($categories as $category)
                            @if ($category->activeMenus->count() > 0)
                                <button 
                                    @click="activeCategory = '{{ $category->id }}'"
                                    class="whitespace-nowrap px-4 py-1.5 rounded-full text-sm font-medium transition-all duration-200"
                                    :class="activeCategory === '{{ $category->id }}' 
                                        ? 'bg-amber-500 text-white shadow-md shadow-amber-500/20' 
                                        : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700'"
                                >
                                    {{ $category->name }}
                                </button>
                            @endif
                        @endforeach
                    </div>

                    <!-- Menu List -->
                    <div class="space-y-8 mt-4">
                        @foreach ($categories as $category)
                            @if ($category->activeMenus->count() > 0)
                                <div x-show="activeCategory === '{{ $category->id }}'" x-transition.opacity.duration.300ms>
                                    <div class="space-y-4">
                                        @foreach ($category->activeMenus as $menu)
                                            <div class="bg-white dark:bg-zinc-800 rounded-2xl p-3 shadow-sm border border-zinc-100 dark:border-zinc-800 flex gap-4">
                                                <!-- Image -->
                                                <div class="w-24 h-24 flex-shrink-0 bg-zinc-100 dark:bg-zinc-700 rounded-xl overflow-hidden">
                                                    @if($menu->image_url)
                                                        <img src="{{ $menu->image_url }}" alt="{{ $menu->name }}" class="w-full h-full object-cover">
                                                    @else
                                                        <div class="w-full h-full flex items-center justify-center text-3xl">🍽️</div>
                                                    @endif
                                                </div>

                                                <!-- Info -->
                                                <div class="flex-1 min-w-0 flex flex-col justify-between py-1">
                                                    <div>
                                                        <h4 class="font-bold text-zinc-900 dark:text-white leading-tight mb-1 line-clamp-2">{{ $menu->name }}</h4>
                                                        <p class="text-amber-600 dark:text-amber-400 font-bold text-sm">Rp {{ number_format($menu->price, 0, ',', '.') }}</p>
                                                        @if($menu->description)
                                                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 line-clamp-2 leading-snug">{{ $menu->description }}</p>
                                                        @endif
                                                    </div>

                                                    <!-- Add Button or Options -->
                                                    <div class="mt-2">
                                                        @if ($menu->variants->count() > 0)
                                                            <div class="text-xs text-zinc-400 mb-2">+ Opsi tersedia</div>
                                                        @endif
                                                        
                                                        <button 
                                                            wire:click="addToCart({{ $menu->id }})"
                                                            wire:loading.attr="disabled"
                                                            class="w-full bg-zinc-100 hover:bg-amber-100 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-900 dark:text-white text-xs font-bold py-2 rounded-lg transition active:scale-95 flex items-center justify-center gap-1"
                                                        >
                                                            <span>Tambah +</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Options Modal (Inline for simplicity) -->
                                            @if ($menu->variants->count() > 0)
                                                <div class="mb-4 ml-4 -mt-2 bg-zinc-50 dark:bg-zinc-900 p-3 rounded-b-xl border-x border-b border-zinc-100 dark:border-zinc-800 text-sm">
                                                    @foreach ($menu->variants as $variant)
                                                        <div class="mb-2">
                                                            <label class="block text-xs font-medium text-zinc-500 mb-1">{{ $variant->name }} @if($variant->is_required) <span class="text-red-500">*</span> @endif</label>
                                                            <select wire:model="selectedOptions.{{ $menu->id }}.{{ $variant->id }}" class="w-full text-xs py-1.5 px-2 rounded border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 dark:text-white">
                                                                <option value="">Pilih...</option>
                                                                @foreach ($variant->options as $option)
                                                                    <option value="{{ $option->id }}">{{ $option->name }} (+{{ number_format($option->price_adjustment) }})</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    @endforeach
                                                    @if (session('variant_error_' . $menu->id))
                                                        <p class="text-red-500 text-xs">{{ session('variant_error_' . $menu->id) }}</p>
                                                    @endif
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- Right Column: Sticky Cart (Desktop Only) -->
                <div class="hidden md:block md:col-span-1 sticky top-24">
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-lg border border-zinc-100 dark:border-zinc-800 overflow-hidden">
                        <div class="p-4 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-black/20">
                            <h3 class="font-bold text-lg text-zinc-900 dark:text-white flex items-center gap-2">
                                <span>Keranjang</span>
                                <span class="bg-amber-500 text-white text-xs px-2 py-0.5 rounded-full">{{ count($cart) }}</span>
                            </h3>
                        </div>
                        
                        <div class="max-h-[60vh] overflow-y-auto p-4 space-y-3 custom-scrollbar">
                             @if (empty($cart))
                                <div class="text-center py-8">
                                    <div class="text-4xl mb-3">🛒</div>
                                    <p class="text-zinc-500 dark:text-zinc-400 text-sm">Belum ada menu yang dipilih</p>
                                </div>
                            @else
                                @foreach ($cart as $key => $item)
                                    <div class="flex gap-2 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl p-2.5 border border-zinc-100 dark:border-zinc-800">
                                        <div class="flex-1 min-w-0">
                                            <div class="font-bold text-zinc-900 dark:text-white text-sm line-clamp-1">{{ $item['name'] }}</div>
                                            @if (!empty($item['options']))
                                                <div class="text-[10px] text-zinc-500 dark:text-zinc-400 truncate">{{ implode(', ', $item['options']) }}</div>
                                            @endif
                                            <div class="text-amber-600 dark:text-amber-400 font-bold text-xs mt-1">Rp {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}</div>
                                        </div>

                                        <div class="flex flex-col items-end justify-between">
                                            <div class="flex items-center gap-2 bg-white dark:bg-zinc-700 rounded-lg p-0.5 shadow-sm">
                                                <button wire:click="updateQuantity('{{ $key }}', {{ $item['quantity'] - 1 }})" class="w-5 h-5 flex items-center justify-center text-zinc-500 hover:text-amber-600 transition">-</button>
                                                <span class="text-xs font-bold w-3 text-center dark:text-white">{{ $item['quantity'] }}</span>
                                                <button wire:click="updateQuantity('{{ $key }}', {{ $item['quantity'] + 1 }})" class="w-5 h-5 flex items-center justify-center text-zinc-500 hover:text-amber-600 transition">+</button>
                                            </div>
                                            <button wire:click="removeFromCart('{{ $key }}')" class="text-red-500 text-[10px] hover:underline">Hapus</button>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        <div class="p-4 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-black/20">
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-zinc-600 dark:text-zinc-400 text-sm">Total Estimasi</span>
                                <span class="font-bold text-lg text-amber-600 dark:text-amber-500">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex gap-2">
                                <button wire:click="prevStep" class="w-10 flex items-center justify-center bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                                </button>
                                <button 
                                    wire:click="nextStep" 
                                    @if(empty($cart)) disabled @endif 
                                    class="flex-1 bg-amber-500 hover:bg-amber-600 disabled:bg-zinc-300 dark:disabled:bg-zinc-700 text-white py-2.5 rounded-xl font-bold transition flex items-center justify-center gap-2 text-sm"
                                >
                                    Lanjut Bayar
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                    <!-- Cart Modal / Bottom Sheet (Mobile Only) -->
                    <div 
                        x-show="showCart" 
                        class="md:hidden fixed inset-0 z-[60] flex items-end sm:items-center justify-center pointer-events-none"
                    >
                        <!-- Backdrop -->
                        <div 
                            x-show="showCart"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            @click="showCart = false"
                            class="absolute inset-0 bg-black/60 backdrop-blur-sm pointer-events-auto"
                        ></div>

                        <!-- Modal Content -->
                        <div 
                            x-show="showCart"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-full sm:translate-y-10 sm:scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                            x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                            x-transition:leave-end="opacity-0 translate-y-full sm:translate-y-10 sm:scale-95"
                            class="bg-white dark:bg-zinc-900 w-full max-w-md max-h-[85vh] sm:rounded-2xl rounded-t-2xl shadow-2xl flex flex-col pointer-events-auto overflow-hidden relative z-10"
                        >
                            <div class="p-4 border-b border-zinc-200 dark:border-zinc-800 flex justify-between items-center bg-zinc-50 dark:bg-zinc-800/50">
                                <h3 class="font-bold text-lg text-zinc-900 dark:text-white">Keranjang Belanja</h3>
                                <button @click="showCart = false" class="text-zinc-500 hover:text-zinc-800 dark:hover:text-white transition bg-transparent p-2 rounded-full hover:bg-zinc-200 dark:hover:bg-zinc-700">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>

                            <div class="flex-1 overflow-y-auto p-4 space-y-4">
                                @if (empty($cart))
                                    <div class="text-center py-12">
                                        <div class="text-4xl mb-3">🛒</div>
                                        <p class="text-zinc-500 dark:text-zinc-400">Keranjang masih kosong</p>
                                    </div>
                                @else
                                    @foreach ($cart as $key => $item)
                                        <div class="flex gap-3 bg-white dark:bg-zinc-800 rounded-xl p-3 border border-zinc-100 dark:border-zinc-800 shadow-sm">
                                            <div class="flex-1 min-w-0">
                                                <div class="font-bold text-zinc-900 dark:text-white text-sm">{{ $item['name'] }}</div>
                                                @if (!empty($item['options']))
                                                    <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ implode(', ', $item['options']) }}</div>
                                                @endif
                                                <div class="text-amber-600 dark:text-amber-400 font-bold text-sm mt-1">Rp {{ number_format($item['price'], 0, ',', '.') }}</div>
                                            </div>

                                            <div class="flex flex-col items-end gap-2">
                                                <div class="flex items-center gap-3 bg-zinc-100 dark:bg-zinc-700 rounded-lg p-1">
                                                    <button wire:click="updateQuantity('{{ $key }}', {{ $item['quantity'] - 1 }})" class="w-6 h-6 flex items-center justify-center bg-white dark:bg-zinc-600 rounded shadow-sm text-zinc-600 dark:text-white text-sm font-bold hover:bg-zinc-50 dark:hover:bg-zinc-500">-</button>
                                                    <span class="text-sm font-medium w-4 text-center dark:text-white">{{ $item['quantity'] }}</span>
                                                    <button wire:click="updateQuantity('{{ $key }}', {{ $item['quantity'] + 1 }})" class="w-6 h-6 flex items-center justify-center bg-white dark:bg-zinc-600 rounded shadow-sm text-zinc-600 dark:text-white text-sm font-bold hover:bg-zinc-50 dark:hover:bg-zinc-500">+</button>
                                                </div>
                                                <button wire:click="removeFromCart('{{ $key }}')" class="text-red-500 text-xs font-medium hover:underline">Hapus</button>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            <div class="p-4 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-black/20">
                                <div class="flex justify-between items-center mb-4">
                                    <span class="text-zinc-600 dark:text-zinc-400">Total</span>
                                    <span class="font-bold text-xl text-amber-600 dark:text-amber-500">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex gap-3">
                                    <button @click="showCart = false" class="flex-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 py-3.5 rounded-xl font-bold transition hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                        Tutup
                                    </button>
                                    <button 
                                        wire:click="nextStep" 
                                        @if(empty($cart)) disabled @endif 
                                        class="flex-[2] bg-amber-500 hover:bg-amber-600 disabled:bg-zinc-300 dark:disabled:bg-zinc-700 text-white py-3.5 rounded-xl font-bold transition flex items-center justify-center gap-2"
                                    >
                                        Bayar
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Moved Step 2 Bottom Bar INSIDE Step 2 Scope (Mobile Only) -->
                    <div class="md:hidden fixed bottom-0 left-0 right-0 p-4 bg-white/90 dark:bg-zinc-900/90 backdrop-blur-md border-t border-zinc-200 dark:border-zinc-800 z-40">
                        <div class="max-w-md mx-auto relative">
                            <div class="flex gap-3">
                                <!-- Back Button -->
                                <button 
                                    wire:click="prevStep"
                                    class="w-12 h-auto flex items-center justify-center bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                                </button>

                                <button 
                                    @click="showCart = true"
                                    class="flex-1 flex flex-col justify-center items-start px-4 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-xl transition cursor-pointer"
                                >
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">Total Estimasi  <span class="text-amber-500 text-[10px] ml-1">(Lihat)</span></span>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-zinc-900 dark:text-white text-lg">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
                                        <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    </div>
                                </button>
                                
                                <!-- Next Button -->
                                <button wire:click="nextStep" @if(empty($cart)) disabled @endif class="bg-amber-500 hover:bg-amber-600 disabled:bg-zinc-300 disabled:text-zinc-500 text-white px-6 py-3.5 rounded-xl font-bold shadow-lg shadow-amber-500/20 transition transform active:scale-95 flex items-center justify-center gap-2">
                                    <span>Bayar</span>
                                    @if(!empty($cart))
                                        <span class="bg-white/20 px-2 py-0.5 rounded text-sm min-w-[24px] text-center">{{ count($cart) }}</span>
                                    @endif
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Step 3: Payment & Summary -->
            @if ($step === 3)
                <div class="pb-24 max-w-5xl mx-auto animate-fade-in">
                    <div class="md:grid md:grid-cols-2 md:gap-8 items-start">
                        <!-- Left Column: Bill Card -->
                        <div class="space-y-6">
                            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-zinc-100 dark:border-zinc-700 overflow-hidden relative">
                                <div class="absolute top-0 w-full h-1 bg-gradient-to-r from-amber-400 to-orange-500"></div>
                                <div class="p-6">
                                    <div class="flex justify-between items-center mb-6">
                                        <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Rincian Pesanan</h2>
                                        <span class="bg-amber-100 text-amber-800 text-xs font-bold px-2 py-1 rounded">Pending</span>
                                    </div>

                                    <!-- List Item -->
                                    <div class="space-y-3 mb-6">
                                        @foreach ($cart as $item)
                                            <div class="flex justify-between items-start text-sm">
                                                <div class="flex-1">
                                                    <div class="font-medium text-zinc-800 dark:text-white">{{ $item['name'] }} <span class="text-xs text-zinc-400">x{{ $item['quantity'] }}</span></div>
                                                    @if (!empty($item['options']))
                                                        <div class="text-xs text-zinc-500">{{ implode(', ', $item['options']) }}</div>
                                                    @endif
                                                </div>
                                                <div class="font-semibold text-zinc-700 dark:text-zinc-300">
                                                    {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <!-- Totals -->
                                    <div class="border-t border-dashed border-zinc-200 dark:border-zinc-700 pt-4 space-y-2">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-zinc-500">Subtotal</span>
                                            <span class="font-medium dark:text-white">{{ number_format($subtotalAmount, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-zinc-500">Tax ({{ config('booking.tax_rate', 10) }}%)</span>
                                            <span class="font-medium dark:text-white">{{ number_format($taxAmount, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="flex justify-between text-base font-bold pt-2">
                                            <span class="text-zinc-900 dark:text-white">Total</span>
                                            <span class="text-amber-600 dark:text-amber-500">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="bg-amber-50 dark:bg-amber-900/20 p-3 rounded-xl flex justify-between items-center mt-2">
                                            <span class="text-sm font-medium text-amber-800 dark:text-amber-300">Min. DP (50%)</span>
                                            <span class="text-lg font-bold text-amber-700 dark:text-amber-400">Rp {{ number_format($dpAmount, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Payment Info -->
                        <div class="space-y-6 mt-6 md:mt-0">
                            <div class="bg-white dark:bg-zinc-800 rounded-2xl p-6 shadow-sm border border-zinc-100 dark:border-zinc-700">
                                <h3 class="font-bold text-zinc-900 dark:text-white mb-4">Pembayaran</h3>
                                
                                <div class="flex flex-col items-center mb-6">
                                    <div class="w-40 h-40 bg-zinc-50 rounded-xl overflow-hidden mb-3 border border-zinc-200 p-2">
                                        <img src="{{ asset('img/qris.jpeg') }}" alt="QRIS" class="w-full h-full object-contain">
                                    </div>
                                    <div class="text-center">
                                        <p class="font-bold text-zinc-900 dark:text-white">BCA 6281580709</p>
                                        <p class="text-xs text-zinc-500">a.n. Dimas Imadudin Satrianto</p>
                                        <button type="button" @click="navigator.clipboard.writeText('6281580709'); alert('Nomor rekening disalin!')" class="text-xs text-amber-600 font-medium mt-1 hover:underline block mx-auto">Salin Nomor Rekening</button>
                                        <a href="{{ asset('img/qris.jpeg') }}" download="QRIS-NFC.jpeg" class="text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 mt-2 inline-flex items-center gap-1 transition">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                            Download QRIS
                                        </a>
                                    </div>
                                </div>

                                <!-- Upload -->
                                <div class="space-y-4">
                                    <div class="bg-zinc-50 dark:bg-zinc-900 border-2 border-dashed border-zinc-300 dark:border-zinc-700 rounded-xl p-4 text-center hover:bg-zinc-100 dark:hover:bg-zinc-700 transition relative">
                                        <input type="file" wire:model="payment_proof" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                        <div class="pointer-events-none">
                                            @if ($payment_proof)
                                                <div class="flex items-center justify-center gap-2 text-green-600 dark:text-green-400">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                    <span class="font-medium text-sm">File terpilih</span>
                                                </div>
                                                <p class="text-xs text-zinc-500 mt-1 truncate px-4">{{ $payment_proof->temporaryUrl() }}</p>
                                            @else
                                                <svg class="w-8 h-8 text-zinc-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Upload Bukti Transfer</p>
                                                <p class="text-xs text-zinc-400 mt-1">Klik untuk memilih file</p>
                                            @endif
                                        </div>
                                    </div>
                                    @error('payment_proof') <span class="text-red-500 text-xs block text-center">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Desktop Action Buttons -->
                            <div class="hidden md:flex gap-3">
                                <button wire:click="prevStep" class="w-14 flex items-center justify-center bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition h-[52px]">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                                </button>
                                <button wire:click="submit" wire:loading.attr="disabled" class="flex-1 bg-green-500 hover:bg-green-600 disabled:bg-zinc-300 text-white rounded-xl font-bold shadow-lg shadow-green-500/20 transition transform active:scale-95 flex items-center justify-center gap-2 h-[52px]">
                                    <span wire:loading.remove wire:target="submit">Kirim Bukti Pembayaran</span>
                                    <span wire:loading wire:target="submit">Mengirim...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Floating Bottom Action Bar (Global for Step 1 & 3) -->
            @if ($step !== 2)
                <div class="md:hidden fixed bottom-0 left-0 right-0 p-4 bg-white/90 dark:bg-zinc-900/90 backdrop-blur-md border-t border-zinc-200 dark:border-zinc-800 z-50">
                    <div class="max-w-md mx-auto relative">
                        <div class="flex gap-3">
                            @if ($step === 1)
                                <button wire:click="nextStep" class="flex-1 bg-amber-500 hover:bg-amber-600 text-white py-3.5 rounded-xl font-bold shadow-lg shadow-amber-500/20 transition transform active:scale-95 flex items-center justify-center gap-2">
                                    Lanjut Menu
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                                </button>
                            @elseif ($step === 3)
                                <button wire:click="prevStep" class="w-12 h-auto flex items-center justify-center bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                                </button>
                                <button wire:click="submit" wire:loading.attr="disabled" class="flex-1 bg-green-500 hover:bg-green-600 disabled:bg-zinc-300 text-white py-3.5 rounded-xl font-bold shadow-lg shadow-green-500/20 transition transform active:scale-95 flex items-center justify-center gap-2">
                                    <span wire:loading.remove wire:target="submit">Kirim Bukti</span>
                                    <span wire:loading wire:target="submit">Mengirim...</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </main>

    <!-- Global Toast -->
    <div x-data="{ show: false, message: '' }" x-on:cart-updated.window="show = true; message = $event.detail.name + ' ditambahkan'; setTimeout(() => show = false, 2000)" class="fixed top-20 left-1/2 transform -translate-x-1/2 z-[60] Pointer-events-none">
        <div x-show="show" x-transition.move.top class="bg-zinc-900/90 text-white px-4 py-2 rounded-full text-sm font-medium shadow-xl backdrop-blur-sm flex items-center gap-2">
            <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span x-text="message"></span>
        </div>
    </div>
</div>
