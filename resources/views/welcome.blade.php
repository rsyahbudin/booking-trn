<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booking Buka Puasa Bersama</title>
    <meta name="description" content="Reservasi buka puasa bersama di cafe kami. Nikmati berbagai menu lezat untuk berbuka puasa bersama keluarga dan teman.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

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
    </style>
</head>
<body class="bg-white dark:bg-zinc-900 antialiased">
    <!-- Navigation -->
    <nav class="bg-white/90 dark:bg-zinc-900/90 backdrop-blur-md fixed w-full z-50 border-b border-amber-100 dark:border-zinc-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🌙</span>
                    <span class="font-bold text-xl text-amber-800 dark:text-amber-400">Cafe Ramadhan</span>
                </div>
                <!-- <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ route('admin.dashboard') }}" class="text-zinc-600 dark:text-zinc-300 hover:text-amber-600 dark:hover:text-amber-400 transition">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-zinc-600 dark:text-zinc-300 hover:text-amber-600 dark:hover:text-amber-400 transition">
                            Login Admin
                        </a>
                    @endauth
                </div> -->
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-gradient min-h-screen flex items-center pt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="text-white">
                    <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-sm px-4 py-2 rounded-full mb-6">
                        <span class="text-xl">🌙</span>
                        <span class="text-sm font-medium">Ramadhan 1446 H</span>
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
                <div class="hidden lg:flex justify-center">
                    <div class="relative">
                        <div class="animate-float">
                            <div class="w-80 h-80 bg-white/20 backdrop-blur-sm rounded-3xl flex items-center justify-center">
                                <span class="text-9xl">🍽️</span>
                            </div>
                        </div>
                        <div class="absolute -bottom-4 -right-4 bg-white rounded-2xl p-4 shadow-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <div>
                                    <div class="font-bold text-zinc-800">Reservasi Mudah</div>
                                    <div class="text-sm text-zinc-500">Konfirmasi via WhatsApp</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
            
            @php
                $seatingSpots = \App\Models\SeatingSpot::active()->get();
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($seatingSpots as $spot)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl overflow-hidden shadow-lg card-hover border border-zinc-100 dark:border-zinc-700">
                        @if ($spot->image_url)
                            <div class="aspect-[4/3] overflow-hidden">
                                <img src="{{ $spot->image_url }}" alt="{{ $spot->name }}" class="w-full h-full object-cover hover:scale-105 transition duration-300">
                            </div>
                        @else
                            <div class="aspect-[4/3] bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                <span class="text-6xl">🪑</span>
                            </div>
                        @endif
                        <div class="p-6">
                            <h3 class="font-bold text-xl text-zinc-800 dark:text-white mb-2">{{ $spot->name }}</h3>
                            @if ($spot->description)
                                <p class="text-zinc-600 dark:text-zinc-400 mb-4">{{ $spot->description }}</p>
                            @endif
                            <div class="flex items-center justify-between">
                                @if ($spot->capacity)
                                    <span class="inline-flex items-center gap-2 text-amber-600 dark:text-amber-400 font-medium">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        {{ $spot->capacity }} orang
                                    </span>
                                @endif
                                <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400 text-sm font-medium">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    Tersedia
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Menu Preview -->
    <section id="menu" class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-zinc-800 dark:text-white mb-4">Menu Spesial Ramadhan</h2>
                <p class="text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto">Pilihan paket dan menu a la carte untuk buka puasa</p>
            </div>
            
            @php
                $categories = \App\Models\Category::active()->ordered()->with(['activeMenus' => fn($q) => $q->take(3)])->get();
            @endphp

            <div class="space-y-12">
                @foreach ($categories as $category)
                    @if ($category->activeMenus->count() > 0)
                        <div>
                            <h3 class="text-2xl font-bold text-amber-600 dark:text-amber-400 mb-6">{{ $category->name }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                @foreach ($category->activeMenus as $menu)
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
                <a href="https://maps.app.goo.gl/fhkaMUFW6zzSvdwV6" target="_blank" class="inline-flex items-center gap-2 text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 font-medium transition">
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
                    <div class="flex items-center gap-2 mb-4">
                        <span class="text-2xl">🌙</span>
                        <span class="font-bold text-xl">Cafe Ramadhan</span>
                    </div>
                    <p class="text-zinc-400">Tempat terbaik untuk buka puasa bersama keluarga dan sahabat.</p>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Kontak</h4>
                    <ul class="space-y-2 text-zinc-400">
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            <span>+62 812 3456 7890</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                            <span>Jl. Contoh No. 123, Kota</span>
                        </li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Jam Operasional</h4>
                    <ul class="space-y-2 text-zinc-400">
                        <li>Senin - Jumat: 16:00 - 22:00</li>
                        <li>Sabtu - Minggu: 15:00 - 23:00</li>
                        <li class="text-amber-400 font-medium">Khusus Ramadhan: 17:00 - 22:00</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-zinc-800 mt-8 pt-8 text-center text-zinc-500 text-sm">
                &copy; {{ date('Y') }} Cafe Ramadhan. All rights reserved.
            </div>
        </div>
    </footer>
</body>
</html>
