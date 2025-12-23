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
    public string $whatsapp = '62';
    public string $instagram = '';
    public string $seating_spot_id = '';
    public bool $agree_rules = false;
    
    // Step 2: Menu Selection
    public array $cart = [];
    public array $selectedOptions = [];
    
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
        $this->booking_date = now()->addDay()->format('Y-m-d');
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
                'whatsapp' => ['required', 'string', 'max:20', 'regex:/^62[1-9][0-9]{7,12}$/'],
                'instagram' => 'nullable|string|max:255',
                'seating_spot_id' => 'required|exists:seating_spots,id',
                'agree_rules' => 'accepted',
            ], [
                'customer_name.required' => 'Nama harus diisi',
                'booking_date.required' => 'Tanggal booking harus dipilih',
                'guest_count.required' => 'Jumlah tamu harus diisi',
                'guest_count.min' => 'Minimal 1 orang',
                'whatsapp.required' => 'Nomor WhatsApp harus diisi',
                'whatsapp.regex' => 'Format nomor WhatsApp tidak valid (contoh: 628123456789)',
                'seating_spot_id.required' => 'Pilih spot duduk',
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
            'whatsapp' => $this->whatsapp,
            'instagram' => $this->instagram,
            'seating_spot_id' => $this->seating_spot_id,
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
        $waNumber = '6285813035292';
        
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
        
        $message = "*BOOKING BUKA PUASA DI TERAS RUMAH NENEK*\n\n";
        $message .= "*Kode Booking:* {$booking->booking_code}\n\n";
        $message .= "*Data Pelanggan:*\n";
        $message .= "Nama: {$this->customer_name}\n";
        $message .= "Tanggal: " . date('d F Y', strtotime($this->booking_date)) . "\n";
        $message .= "Jumlah Tamu: {$this->guest_count} orang\n";
        $message .= "WhatsApp: {$this->whatsapp}\n";
        if ($this->instagram) {
            $message .= "Instagram: {$this->instagram}\n";
        }
        $message .= "Spot: {$spot->name}\n\n";
        
        $message .= "*Pesanan:*\n";
        foreach ($this->cart as $item) {
            $options = !empty($item['options']) ? ' (' . implode(', ', $item['options']) . ')' : '';
            $message .= "• {$item['name']}{$options} x{$item['quantity']} = Rp " . number_format($item['price'] * $item['quantity'], 0, ',', '.') . "\n";
        }
        
        $message .= "\n*Rincian Pembayaran:*\n";
        $message .= "Subtotal: Rp " . number_format($this->subtotalAmount, 0, ',', '.') . "\n";
        $message .= "PPN (10%): Rp " . number_format($this->taxAmount, 0, ',', '.') . "\n";
        $message .= "*Total: Rp " . number_format($this->totalAmount, 0, ',', '.') . "*\n";
        $message .= "*DP (50%): Rp " . number_format($this->dpAmount, 0, ',', '.') . "*\n\n";
        
        $message .= "Bukti Transfer: " . url('storage/' . $booking->payment_proof) . "\n\n";
        
        if ($this->notes) {
            $message .= "*Catatan:* {$this->notes}\n\n";
        }
        
        $message .= "Mohon konfirmasi booking ini. Terima kasih!";
        
        return $message;
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

<div class="min-h-screen bg-gradient-to-br from-amber-50 via-orange-50 to-yellow-50 dark:from-zinc-900 dark:via-zinc-800 dark:to-zinc-900">
    <!-- Header -->
    <header class="bg-white/70 dark:bg-zinc-900/70 backdrop-blur-xl border-b border-white/20 dark:border-zinc-800 sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-4 py-3">
            <div class="flex flex-col items-center justify-center text-center">
                <a href="{{ route('home') }}" class="flex items-center gap-3 group hover:opacity-80 transition">
                    {{-- Logo Adaptif --}}
                    <img src="{{ asset('img/logo-black.png') }}" alt="Logo" class="h-12 w-auto object-contain block dark:hidden">
                    <img src="{{ asset('img/logo-white.png') }}" alt="Logo" class="h-12 w-auto object-contain hidden dark:block">
                    <div class="text-left">
                        <span class="block text-xs font-medium text-amber-600 dark:text-amber-400 tracking-wider uppercase">Booking Online</span>
                        <span class="font-bold text-lg leading-tight text-zinc-800 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition">Teras Rumah Nenek</span>
                    </div>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-8">
        @if (request('success'))
            <!-- Success State -->
            <div class="text-center py-12">
                <div class="w-20 h-20 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-zinc-800 dark:text-white mb-2">Booking Berhasil! 🎉</h1>
                <p class="text-zinc-600 dark:text-zinc-400 mb-4">Kode Booking: <span class="font-mono font-bold text-amber-600">{{ request('code') }}</span></p>
                <p class="text-zinc-600 dark:text-zinc-400 mb-8">Silakan konfirmasi booking Anda via WhatsApp</p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ session('booking_success.wa_url') ?? '#' }}" target="_blank" class="inline-flex items-center justify-center gap-2 bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-xl font-medium transition">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        Konfirmasi via WhatsApp
                    </a>
                    <a href="{{ route('home') }}" class="inline-flex items-center justify-center gap-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-white px-6 py-3 rounded-xl font-medium transition">
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
        @else
            <!-- Progress Steps -->
            <div class="mb-8">
                <div class="flex items-center justify-center">
                    @foreach ([1 => 'Data Diri', 2 => 'Pilih Menu', 3 => 'Pembayaran'] as $num => $label)
                        <div class="flex items-center">
                            <div class="flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition {{ $step >= $num ? 'bg-amber-500 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-500' }}">
                                    @if ($step > $num)
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    @else
                                        {{ $num }}
                                    @endif
                                </div>
                                <span class="text-xs mt-1 {{ $step >= $num ? 'text-amber-600 dark:text-amber-400 font-medium' : 'text-zinc-400' }}">{{ $label }}</span>
                            </div>
                            @if ($num < 3)
                                <div class="w-12 sm:w-24 h-1 mx-2 rounded {{ $step > $num ? 'bg-amber-500' : 'bg-zinc-200 dark:bg-zinc-700' }}"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 text-red-700 dark:text-red-400 rounded-xl">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Step 1: Customer Data -->
            @if ($step === 1)
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl p-6 md:p-8">
                    <h2 class="text-2xl font-bold text-zinc-800 dark:text-white mb-6">Data Diri</h2>
                    
                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Nama Lengkap *</label>
                            <input type="text" wire:model="customer_name" class="w-full px-4 py-3 rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent" placeholder="Masukkan nama Anda">
                            @error('customer_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Tanggal Booking *</label>
                                <input type="date" wire:model="booking_date" min="{{ date('Y-m-d') }}" class="w-full px-4 py-3 rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                                @error('booking_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Jumlah Tamu *</label>
                                <input type="number" wire:model="guest_count" min="1" class="w-full px-4 py-3 rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent" placeholder="1">
                                @error('guest_count') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Nomor WhatsApp *</label>
                                <div class="flex">
                                    <span class="inline-flex items-center px-4 py-3 rounded-l-xl border border-r-0 border-zinc-300 dark:border-zinc-600 bg-zinc-100 dark:bg-zinc-600 text-zinc-700 dark:text-zinc-300 text-sm font-medium">+</span>
                                    <input type="text" wire:model="whatsapp" class="w-full px-4 py-3 rounded-r-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent" placeholder="628123456789">
                                </div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Format: 62 diikuti nomor tanpa angka 0 di depan (contoh: 628123456789)</p>
                                @error('whatsapp') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Instagram</label>
                                <input type="text" wire:model="instagram" class="w-full px-4 py-3 rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent" placeholder="@username">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Pilih Spot Duduk *</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach ($seatingSpots as $spot)
                                    <label class="relative cursor-pointer">
                                        <input type="radio" wire:model="seating_spot_id" value="{{ $spot->id }}" class="peer sr-only">
                                        <div class="p-4 rounded-xl border-2 border-zinc-200 dark:border-zinc-600 peer-checked:border-amber-500 peer-checked:bg-amber-50 dark:peer-checked:bg-amber-900/20 transition">
                                            <div class="flex items-center gap-3">
                                                <span class="text-2xl">🪑</span>
                                                <div>
                                                    <div class="font-medium text-zinc-800 dark:text-white">{{ $spot->name }}</div>
                                                    @if ($spot->description)
                                                        <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $spot->description }}</div>
                                                    @endif
                                                    @if ($spot->capacity)
                                                        <div class="text-xs text-amber-600 dark:text-amber-400">Kapasitas: {{ $spot->capacity }} orang</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            @error('seating_spot_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Booking Rules -->
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-4 border border-amber-200 dark:border-amber-800">
                            <h3 class="font-medium text-amber-800 dark:text-amber-400 mb-3">📜 Aturan Booking</h3>
                            <ul class="text-sm text-amber-700 dark:text-amber-300 space-y-2">
                                @foreach ($bookingRules as $rule)
                                    <li class="flex items-start gap-2">
                                        <span class="text-amber-500">•</span>
                                        <span>{{ $rule }}</span>
                                    </li>
                                @endforeach
                            </ul>
                            <label class="flex items-center gap-3 mt-4 cursor-pointer">
                                <input type="checkbox" wire:model="agree_rules" class="w-5 h-5 rounded border-amber-300 text-amber-500 focus:ring-amber-500">
                                <span class="text-sm font-medium text-amber-800 dark:text-amber-400">Saya setuju dengan aturan booking di atas *</span>
                            </label>
                            @error('agree_rules') <span class="text-red-500 text-sm block mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <button wire:click="nextStep" class="bg-amber-500 hover:bg-amber-600 text-white px-8 py-3 rounded-xl font-medium transition flex items-center gap-2">
                            Lanjut Pilih Menu
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                    </div>
                </div>
            @endif

            <!-- Step 2: Menu Selection -->
            @if ($step === 2)
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Menu List -->
                    <div class="lg:col-span-2 space-y-6" x-data="{ activeCategory: '{{ $categories->first()?->id }}' }">
                        <h2 class="text-2xl font-bold text-zinc-800 dark:text-white">Pilih Menu</h2>
                        
                        <!-- Category Tabs -->
                        <div class="flex flex-wrap gap-2 sticky top-20 z-30 bg-gradient-to-b from-zinc-100 dark:from-zinc-900 to-transparent pb-4 pt-2 -mx-4 px-4">
                            @foreach ($categories as $category)
                                @if ($category->activeMenus->count() > 0)
                                    <button 
                                        @click="activeCategory = '{{ $category->id }}'"
                                        :class="activeCategory === '{{ $category->id }}' 
                                            ? 'bg-amber-500 text-white shadow-lg' 
                                            : 'bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-amber-100 dark:hover:bg-zinc-700'"
                                        class="px-4 py-2 rounded-full font-medium text-sm transition-all duration-200 border border-zinc-200 dark:border-zinc-700"
                                    >
                                        {{ $category->name }}
                                        <span class="ml-1 text-xs opacity-70">({{ $category->activeMenus->count() }})</span>
                                    </button>
                                @endif
                            @endforeach
                        </div>
                        
                        <!-- Menu Items by Category -->
                        @foreach ($categories as $category)
                            @if ($category->activeMenus->count() > 0)
                                <div x-show="activeCategory === '{{ $category->id }}'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl p-6">
                                    <h3 class="text-lg font-bold text-amber-600 dark:text-amber-400 mb-4">{{ $category->name }}</h3>
                                    
                                    <div class="space-y-4">
                                        @foreach ($category->activeMenus as $menu)
                                            <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl p-4">
                                                <div class="flex gap-4">
                                                    @if ($menu->image_url)
                                                        <img src="{{ $menu->image_url }}" alt="{{ $menu->name }}" class="w-20 h-20 object-cover rounded-lg flex-shrink-0">
                                                    @else
                                                        <div class="w-20 h-20 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                                            <span class="text-3xl">🍽️</span>
                                                        </div>
                                                    @endif
                                                    <div class="flex-1 min-w-0">
                                                        <h4 class="font-medium text-zinc-800 dark:text-white">{{ $menu->name }}</h4>
                                                        @if ($menu->description)
                                                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ $menu->description }}</p>
                                                        @endif
                                                        <p class="text-amber-600 dark:text-amber-400 font-bold mt-2">Rp {{ number_format($menu->price, 0, ',', '.') }}</p>
                                                    </div>
                                                </div>

                                                @if ($menu->variants->count() > 0)
                                                    <div class="mt-4 space-y-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                                                        @foreach ($menu->variants as $variant)
                                                            <div>
                                                                <label class="block text-sm font-medium mb-2 {{ $variant->is_required ? 'text-zinc-800 dark:text-zinc-200' : 'text-zinc-600 dark:text-zinc-400' }}">
                                                                    {{ $variant->name }}
                                                                    @if($variant->is_required)
                                                                        <span class="text-red-500 font-bold">*</span>
                                                                        <span class="text-xs text-red-500">(wajib)</span>
                                                                    @endif
                                                                </label>
                                                                <select wire:model="selectedOptions.{{ $menu->id }}.{{ $variant->id }}" class="w-full px-3 py-2 rounded-lg border {{ $variant->is_required ? 'border-amber-400 dark:border-amber-600' : 'border-zinc-300 dark:border-zinc-600' }} bg-white dark:bg-zinc-700 text-sm">
                                                                    <option value="">Pilih {{ $variant->name }}</option>
                                                                    @foreach ($variant->options as $option)
                                                                        <option value="{{ $option->id }}">
                                                                            {{ $option->name }}
                                                                            @if ($option->price_adjustment > 0)
                                                                                (+Rp {{ number_format($option->price_adjustment, 0, ',', '.') }})
                                                                            @endif
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                @if (session('variant_error_' . $menu->id))
                                                    <div class="mt-3 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
                                                        <p class="text-sm text-red-600 dark:text-red-400 flex items-center gap-2">
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                                                            {{ session('variant_error_' . $menu->id) }}
                                                        </p>
                                                    </div>
                                                @endif

                                                <button wire:click="addToCart({{ $menu->id }})" class="mt-4 w-full bg-amber-500 hover:bg-amber-600 text-white py-2 rounded-lg font-medium transition flex items-center justify-center gap-2">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                                    Tambah
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <!-- Cart -->
                    <div class="lg:col-span-1">
                        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl p-6 sticky top-24">
                            <h3 class="text-lg font-bold text-zinc-800 dark:text-white mb-4">Pesanan Anda</h3>
                            
                            @if (empty($cart))
                                <p class="text-zinc-500 dark:text-zinc-400 text-center py-8">Belum ada menu dipilih</p>
                            @else
                                <div class="space-y-3 max-h-96 overflow-y-auto">
                                    @foreach ($cart as $key => $item)
                                        <div class="flex gap-3 p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg">
                                            <div class="flex-1 min-w-0">
                                                <div class="font-medium text-sm text-zinc-800 dark:text-white">{{ $item['name'] }}</div>
                                                @if (!empty($item['options']))
                                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ implode(', ', $item['options']) }}</div>
                                                @endif
                                                <div class="text-amber-600 dark:text-amber-400 text-sm font-medium">Rp {{ number_format($item['price'], 0, ',', '.') }}</div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <button wire:click="updateQuantity('{{ $key }}', {{ $item['quantity'] - 1 }})" class="w-7 h-7 rounded-full bg-zinc-200 dark:bg-zinc-600 flex items-center justify-center text-zinc-600 dark:text-white hover:bg-zinc-300 dark:hover:bg-zinc-500">-</button>
                                                <span class="w-6 text-center text-sm font-medium">{{ $item['quantity'] }}</span>
                                                <button wire:click="updateQuantity('{{ $key }}', {{ $item['quantity'] + 1 }})" class="w-7 h-7 rounded-full bg-zinc-200 dark:bg-zinc-600 flex items-center justify-center text-zinc-600 dark:text-white hover:bg-zinc-300 dark:hover:bg-zinc-500">+</button>
                                            </div>
                                            <button wire:click="removeFromCart('{{ $key }}')" class="text-red-500 hover:text-red-700">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700 space-y-2">
                                    <div class="flex justify-between text-sm text-zinc-600 dark:text-zinc-400">
                                        <span>Subtotal</span>
                                        <span>Rp {{ number_format($subtotalAmount, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm text-zinc-600 dark:text-zinc-400">
                                        <span>PPN (10%)</span>
                                        <span>Rp {{ number_format($taxAmount, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between text-lg font-bold pt-2 border-t border-zinc-200 dark:border-zinc-700">
                                        <span class="text-zinc-800 dark:text-white">Total</span>
                                        <span class="text-amber-600 dark:text-amber-400">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            @endif

                            <div class="mt-6 space-y-3">
                                <button wire:click="prevStep" class="w-full bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-white py-3 rounded-xl font-medium transition">
                                    Kembali
                                </button>
                                <button wire:click="nextStep" @if(empty($cart)) disabled @endif class="w-full bg-amber-500 hover:bg-amber-600 disabled:bg-zinc-300 disabled:cursor-not-allowed text-white py-3 rounded-xl font-medium transition">
                                    Lanjut ke Pembayaran
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Step 3: Payment -->
            @if ($step === 3)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Order Summary -->
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl p-6">
                        <h2 class="text-xl font-bold text-zinc-800 dark:text-white mb-4">Ringkasan Pesanan</h2>
                        
                        <div class="space-y-3 mb-6">
                            @foreach ($cart as $item)
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="font-medium text-zinc-800 dark:text-white">{{ $item['name'] }} x{{ $item['quantity'] }}</div>
                                        @if (!empty($item['options']))
                                            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ implode(', ', $item['options']) }}</div>
                                        @endif
                                    </div>
                                    <span class="font-medium text-zinc-800 dark:text-white">Rp {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-zinc-600 dark:text-zinc-400">Subtotal</span>
                                <span class="font-medium text-zinc-800 dark:text-white">Rp {{ number_format($subtotalAmount, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-600 dark:text-zinc-400">PPN (10%)</span>
                                <span class="font-medium text-zinc-800 dark:text-white">Rp {{ number_format($taxAmount, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-lg border-t border-zinc-200 dark:border-zinc-700 pt-2 mt-2">
                                <span class="font-bold text-zinc-800 dark:text-white">Total</span>
                                <span class="font-bold text-zinc-800 dark:text-white">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-xl mt-2">
                                <span class="font-bold text-amber-600 dark:text-amber-400">Minimum DP (50%)</span>
                                <span class="font-bold text-amber-600 dark:text-amber-400">Rp {{ number_format($dpAmount, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl p-6">
                        <h2 class="text-xl font-bold text-zinc-800 dark:text-white mb-4">Pembayaran</h2>
                        
                        <div class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl text-center">
                            <p class="text-sm text-amber-800 dark:text-amber-300 mb-3">Scan QR Code atau transfer ke rekening berikut:</p>
                            
                            <!-- QR Code Placeholder -->
                            <div class="w-48 h-auto bg-white rounded-xl mx-auto border-2 border-amber-300 dark:border-amber-600 overflow-hidden p-2">
                                {{-- Ganti 'qris.jpg' dengan nama file gambar QRIS Anda. Simpan gambar di folder public/img/ --}}
                                <img src="{{ asset('img/qris.jpeg') }}" alt="QRIS Payment" class="w-full h-full object-contain">
                            </div>
                            
                            <a href="{{ asset('img/qris.jpeg') }}" download="qris-payment.jpeg" class="inline-flex items-center gap-2 mt-3 px-4 py-2 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Download QRIS
                            </a>
                            
                            <div class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">
                                <p class="font-medium">Bank BCA</p>
                                <p class="font-mono text-lg">6281580709</p>
                                <p>a.n. Dimas Imadudin Satrianto</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Upload Bukti Transfer *</label>
                                <input type="file" wire:model="payment_proof" accept="image/*" class="w-full text-sm text-zinc-500 file:mr-4 file:py-3 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-amber-100 file:text-amber-700 hover:file:bg-amber-200 dark:file:bg-amber-900 dark:file:text-amber-300">
                                @error('payment_proof') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                
                                @if ($payment_proof)
                                    <img src="{{ $payment_proof->temporaryUrl() }}" alt="Preview" class="mt-3 w-full max-w-xs rounded-xl border border-zinc-200 dark:border-zinc-700">
                                @endif
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Catatan (Opsional)</label>
                                <textarea wire:model="notes" rows="3" class="w-full px-4 py-3 rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent" placeholder="Catatan tambahan untuk pesanan Anda..."></textarea>
                            </div>
                        </div>

                        <div class="mt-6 space-y-3">
                            <button wire:click="prevStep" class="w-full bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-white py-3 rounded-xl font-medium transition">
                                Kembali
                            </button>
                            <button wire:click="submit" wire:loading.attr="disabled" class="w-full bg-green-500 hover:bg-green-600 disabled:bg-green-300 text-white py-3 rounded-xl font-medium transition flex items-center justify-center gap-2">
                                <span wire:loading.remove wire:target="submit">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                </span>
                                <span wire:loading wire:target="submit">Memproses...</span>
                                <span wire:loading.remove wire:target="submit">Kirim & Konfirmasi via WhatsApp</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </main>

    <!-- Footer -->
    <footer class="py-8 text-center text-zinc-500 dark:text-zinc-400 text-sm">
        &copy; {{ date('Y') }} Booking Buka Puasa. All rights reserved.
    </footer>

    <!-- Toast Notification -->
    <div
        x-data="{ 
            show: false, 
            message: '',
            init() {
                Livewire.on('cart-updated', (data) => {
                    this.message = data.name + ' ditambahkan ke keranjang!';
                    this.show = true;
                    setTimeout(() => this.show = false, 3000);
                });
            }
        }"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed top-6 right-6 z-50"
        style="display: none;"
    >
        <div class="flex items-center gap-3 bg-green-500 text-white px-5 py-3 rounded-xl shadow-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span x-text="message" class="font-medium"></span>
        </div>
    </div>
</div>
