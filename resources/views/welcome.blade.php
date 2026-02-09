<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buka Puasa di Teras Rumah Nenek</title>
    <meta name="description" content="Reservasi buka puasa bersama di cafe kami. Nikmati berbagai menu lezat untuk berbuka puasa bersama keluarga dan teman.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    
    <!-- Alpine.js for interactive components -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .hero-gradient {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 50%, #d97706 100%);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .hero-bg {
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('{{ asset('img/hero-bg.jpg') }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
    </style>
</head>
<body class="bg-white dark:bg-zinc-900 antialiased">
    <!-- Navigation -->
    <nav class="bg-transparent fixed w-full z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-2">
                    {{-- Ganti 'logo.png' dengan file logo Anda di folder public/img/ --}}
                    {{-- Logo: Hitam untuk Light Mode, Putih untuk Dark Mode --}}
                    <img src="{{ asset('img/logo-black.png') }}" alt="Logo" class="h-10 w-auto object-contain block dark:hidden">
                    <img src="{{ asset('img/logo-white.png') }}" alt="Logo" class="h-10 w-auto object-contain hidden dark:block">
                    <!-- <span class="font-bold text-xl text-amber-800 dark:text-amber-400 hidden sm:block">Teras Rumah Nenek</span> -->
                </div>
                <div class="flex items-center gap-3">
                    <a href="https://wa.me/{{ \App\Models\SiteSetting::get('whatsapp', '6285813035292') }}" target="_blank" class="flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 dark:bg-white/5 backdrop-blur-md text-zinc-900 dark:text-white hover:bg-green-500 hover:text-white transition-all duration-300 font-medium text-sm group border border-white/20">
                        <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.296-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.084 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                        <span class="hidden md:inline">WhatsApp</span>
                    </a>
                    <a href="https://instagram.com/{{ \App\Models\SiteSetting::get('instagram', 'terasrumahnenek') }}" target="_blank" class="flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 dark:bg-white/5 backdrop-blur-md text-zinc-900 dark:text-white hover:bg-pink-500 hover:text-white transition-all duration-300 font-medium text-sm group border border-white/20">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        <span class="hidden md:inline font-medium">Instagram</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-bg min-h-screen flex items-center pt-16 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="text-white">
                    <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-sm px-4 py-2 rounded-full mb-6">
                        <span class="text-xl">🌙</span>
                        <span class="text-sm font-medium">Ramadhan 1447 H</span>
                    </div>
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight mb-6">
                        Buka Puasa<br>
                        <span class="text-amber-200">Bersama</span><br>
                        Lebih Istimewa
                    </h1>
                    <p class="text-lg md:text-xl text-amber-100 mb-8 max-w-lg">
                        Nikmati berbagai menu spesial Ramadhan bersama keluarga dan sahabat dalam suasana yang hangat dan nyaman.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('booking.form') }}" class="inline-flex items-center justify-center gap-2 bg-white text-amber-600 hover:bg-amber-50 px-8 py-4 rounded-xl font-bold text-lg transition shadow-lg hover:shadow-xl">
                            <span>Booking Sekarang</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                        <a href="#menu" class="inline-flex items-center justify-center gap-2 border-2 border-white/50 text-white hover:bg-white/10 px-8 py-4 rounded-xl font-bold text-lg transition">
                            Lihat Menu
                        </a>
                    </div>
                </div>
                <!-- <div class="hidden lg:flex justify-center">
                    <div class="relative">
                        <div class="animate-float">
                            <div class="w-80 h-80 bg-white/20 backdrop-blur-sm rounded-3xl flex items-center justify-center overflow-hidden">
                                {{-- Ganti 'feature-icon.jpg' dengan gambar menu andalan Anda --}}
                                <img src="{{ asset('img/feature-icon.jpg') }}" alt="Feature Menu" class="w-full h-full object-cover">
                            </div>
                        </div>
                        <!-- <div class="absolute -bottom-4 -right-4 bg-white rounded-2xl p-4 shadow-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <div>
                                    <div class="font-bold text-zinc-800">Reservasi Mudah</div>
                                    <div class="text-sm text-zinc-500">Konfirmasi via WhatsApp</div>
                                </div>
                            </div>
                        </div> -->
                    </div>
                </div> -->
            </div>
            

        </div>
    </section>

    <!-- Features -->
    <section class="py-20 bg-amber-50 dark:bg-zinc-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-zinc-800 dark:text-white mb-4">Mengapa Memilih Kami?</h2>
                <p class="text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto">Kami menyediakan pengalaman buka puasa yang tak terlupakan</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white dark:bg-zinc-700 rounded-2xl p-8 text-center card-hover">
                    <div class="w-16 h-16 bg-amber-100 dark:bg-amber-900 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <span class="text-3xl">🍛</span>
                    </div>
                    <h3 class="text-xl font-bold text-zinc-800 dark:text-white mb-3">Menu Variatif</h3>
                    <p class="text-zinc-600 dark:text-zinc-400">Berbagai pilihan menu dari makanan tradisional hingga modern</p>
                </div>
                <div class="bg-white dark:bg-zinc-700 rounded-2xl p-8 text-center card-hover">
                    <div class="w-16 h-16 bg-amber-100 dark:bg-amber-900 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <span class="text-3xl">🪑</span>
                    </div>
                    <h3 class="text-xl font-bold text-zinc-800 dark:text-white mb-3">Tempat Nyaman</h3>
                    <p class="text-zinc-600 dark:text-zinc-400">Indoor AC, Outdoor Garden, VIP Room tersedia untuk kenyamanan Anda</p>
                </div>
                <div class="bg-white dark:bg-zinc-700 rounded-2xl p-8 text-center card-hover">
                    <div class="w-16 h-16 bg-amber-100 dark:bg-amber-900 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <span class="text-3xl">💳</span>
                    </div>
                    <h3 class="text-xl font-bold text-zinc-800 dark:text-white mb-3">DP 50% Saja</h3>
                    <p class="text-zinc-600 dark:text-zinc-400">Cukup bayar DP 50% untuk mengamankan reservasi Anda</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Seating Spots -->
    <section id="spots" class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-zinc-800 dark:text-white mb-4">Pilihan Tempat Duduk</h2>
                <p class="text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto">Pilih spot yang sesuai dengan kebutuhan Anda</p>
            </div>
            
            <livewire:seating-spots-list />
        </div>
    </section>

    <!-- Menu Preview -->
    <section id="menu" class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-zinc-800 dark:text-white mb-4">Menu Spesial Ramadhan</h2>
                <p class="text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto">Pilihan paket dan menu untuk buka puasa</p>
            </div>
            
            @php
                $categories = \App\Models\Category::active()->ordered()->with(['activeMenus' => fn($q) => $q->with('variants')])->get();
            @endphp

            <!-- Category Tabs and Menu Content -->
            <div x-data="{ activeCategory: '{{ $categories->first()?->id }}' }">
                <!-- Category Tabs -->
                <div class="flex flex-wrap justify-center gap-2 mb-8">
                    @foreach ($categories as $category)
                        @if ($category->activeMenus->count() > 0)
                            <button 
                                @click="activeCategory = '{{ $category->id }}'"
                                :class="activeCategory === '{{ $category->id }}' 
                                    ? 'bg-amber-500 text-white shadow-lg' 
                                    : 'bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-amber-100 dark:hover:bg-zinc-700'"
                                class="px-5 py-2.5 rounded-full font-medium transition-all duration-200 border border-zinc-200 dark:border-zinc-700"
                            >
                                {{ $category->name }}
                            </button>
                        @endif
                    @endforeach
                </div>

                <!-- Menu Items -->
                <div class="space-y-12">
                    @foreach ($categories as $category)
                        @if ($category->activeMenus->count() > 0)
                            <div x-show="activeCategory === '{{ $category->id }}'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                                <h3 class="text-2xl font-bold text-amber-600 dark:text-amber-400 mb-6">{{ $category->name }}</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    @foreach ($category->activeMenus->take(6) as $menu)
                                        <div class="bg-white dark:bg-zinc-800 rounded-2xl overflow-hidden shadow-lg card-hover border border-zinc-100 dark:border-zinc-700">
                                            <div class="aspect-video bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                                @if ($menu->image_url)
                                                    <img src="{{ $menu->image_url }}" alt="{{ $menu->name }}" class="w-full h-full object-cover">
                                                @else
                                                    <span class="text-5xl">🍽️</span>
                                                @endif
                                            </div>
                                            <div class="p-6">
                                                <h4 class="font-bold text-lg text-zinc-800 dark:text-white mb-2">{{ $menu->name }}</h4>
                                                @if ($menu->description)
                                                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4 line-clamp-2">{{ $menu->description }}</p>
                                                @endif
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xl font-bold text-amber-600 dark:text-amber-400">Rp {{ number_format($menu->price, 0, ',', '.') }}</span>
                                                    @if ($menu->variants->count() > 0)
                                                        <span class="text-xs bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 px-2 py-1 rounded-full">{{ $menu->variants->count() }} pilihan</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="text-center mt-12">
                <a href="{{ route('booking.form') }}" class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-8 py-4 rounded-xl font-bold text-lg transition shadow-lg">
                    <span>Lihat Semua Menu & Booking</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>
            </div>
        </div>
    </section>

    <!-- How to Book -->
    <section class="py-20 bg-zinc-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Cara Booking</h2>
                <p class="text-zinc-400 max-w-2xl mx-auto">4 langkah mudah untuk reservasi buka puasa bersama</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-amber-500 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl font-bold">1</div>
                    <h3 class="font-bold text-lg mb-2">Isi Data Diri</h3>
                    <p class="text-zinc-400 text-sm">Lengkapi nama, tanggal, jumlah tamu, dan pilih spot</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-amber-500 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl font-bold">2</div>
                    <h3 class="font-bold text-lg mb-2">Pilih Menu</h3>
                    <p class="text-zinc-400 text-sm">Pilih menu favorit dengan varian sesuai selera</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-amber-500 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl font-bold">3</div>
                    <h3 class="font-bold text-lg mb-2">Bayar DP 50%</h3>
                    <p class="text-zinc-400 text-sm">Transfer DP dan upload bukti pembayaran</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-amber-500 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl font-bold">4</div>
                    <h3 class="font-bold text-lg mb-2">Konfirmasi WA</h3>
                    <p class="text-zinc-400 text-sm">Kirim detail booking via WhatsApp untuk konfirmasi</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Location -->
    <section id="location" class="py-20 bg-amber-50 dark:bg-zinc-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-zinc-800 dark:text-white mb-4">Lokasi Kami</h2>
                <p class="text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto">Temukan kami di Teras Rumah Nenek</p>
            </div>
            <div class="bg-white dark:bg-zinc-900 rounded-2xl overflow-hidden shadow-xl">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3965.2372420565475!2d106.87853877499158!3d-6.36333459362675!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69ed302dffac99%3A0x5865ecdaf645cf7f!2sTeras%20Rumah%20Nenek!5e0!3m2!1sen!2sid!4v1766221357865!5m2!1sen!2sid" 
                    width="100%" 
                    height="450" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade"
                    class="w-full"
                ></iframe>
            </div>
            <div class="mt-8 text-center">
                <a href="{{ \App\Models\SiteSetting::get('gmaps_link', 'https://maps.app.goo.gl/fhkaMUFW6zzSvdwV6') }}" target="_blank" class="inline-flex items-center gap-2 text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 font-medium transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Buka di Google Maps
                </a>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-20 hero-gradient">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">Siap Untuk Buka Puasa Bersama?</h2>
            <p class="text-xl text-amber-100 mb-8">Jangan sampai kehabisan tempat! Booking sekarang dan nikmati momen berharga bersama orang-orang tersayang.</p>
            <a href="{{ route('booking.form') }}" class="inline-flex items-center gap-2 bg-white text-amber-600 hover:bg-amber-50 px-10 py-5 rounded-xl font-bold text-xl transition shadow-xl">
                <span>Booking Sekarang</span>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-zinc-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <div class="flex items-center gap-3 mb-6">
                        <img src="{{ asset('img/logo-white.png') }}" alt="Logo" class="h-12 w-auto rounded-lg p-1">
                        <!-- <div>
                            <span class="font-bold text-xl block leading-tight">Teras Rumah<br>Nenek</span>
                        </div> -->
                    </div>
                    <p class="text-zinc-400">{{ \App\Models\SiteSetting::get('tagline', 'Tempat Buka Puasa Keluarga yang Nyaman') }}</p>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Kontak</h4>
                    <ul class="space-y-2 text-zinc-400">
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            <span>{{ \App\Models\SiteSetting::get('whatsapp', 'Nomor Telepon belum diatur') }}</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <span>{{ \App\Models\SiteSetting::get('address', 'Alamat belum diatur') }}</span>
                        </li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Operasional Ramadhan</h4>
                    <ul class="space-y-2 text-zinc-400">
                        <li>{{ \App\Models\SiteSetting::get('operating_hours', 'Senin - Minggu: 16:00 - 23:00') }}</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-zinc-800 mt-8 pt-8 text-center text-zinc-500 text-sm">
                &copy; {{ date('Y') }} {{ \App\Models\SiteSetting::get('cafe_name', 'Teras Rumah Nenek') }}. All rights reserved.
            </div>
        </div>
    </footer>
    @livewireScripts
</body>
</html>
