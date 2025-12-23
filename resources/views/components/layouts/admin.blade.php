<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Admin' }} - Booking Buka Puasa</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    <flux:sidebar sticky stashable class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <flux:brand href="{{ route('admin.dashboard') }}" logo="" name="Booking Admin" class="px-2 dark:hidden" />
        <flux:brand href="{{ route('admin.dashboard') }}" logo="" name="Booking Admin" class="px-2 hidden dark:flex" />

        <flux:navlist variant="outline">
            <flux:navlist.item icon="home" href="{{ route('admin.dashboard') }}" :current="request()->routeIs('admin.dashboard')">
                Dashboard
            </flux:navlist.item>

            <flux:navlist.item icon="clipboard-document-check" href="{{ route('admin.bookings.orders') }}" :current="request()->routeIs('admin.bookings.orders')">
                Daftar Pesanan
            </flux:navlist.item>

            @if (Auth::user()->isAdmin())
                <flux:navlist.item icon="calendar-days" href="{{ route('admin.bookings.index') }}" :current="request()->routeIs('admin.bookings.index')">
                    Booking
                </flux:navlist.item>

                <flux:navlist.item icon="rectangle-stack" href="{{ route('admin.categories.index') }}" :current="request()->routeIs('admin.categories.*')">
                    Kategori
                </flux:navlist.item>

                <flux:navlist.item icon="clipboard-document-list" href="{{ route('admin.menus.index') }}" :current="request()->routeIs('admin.menus.*')">
                    Menu
                </flux:navlist.item>

                <flux:navlist.item icon="map-pin" href="{{ route('admin.seating-spots.index') }}" :current="request()->routeIs('admin.seating-spots.*')">
                    Spot Duduk
                </flux:navlist.item>

                <flux:navlist.item icon="cog-6-tooth" href="{{ route('admin.settings') }}" :current="request()->routeIs('admin.settings')">
                    Pengaturan
                </flux:navlist.item>
            @endif
        </flux:navlist>

        <flux:spacer />

        <flux:navlist variant="outline">
            <flux:navlist.item icon="arrow-left-start-on-rectangle" href="{{ route('home') }}">
                Kembali ke Website
            </flux:navlist.item>
        </flux:navlist>

        <!-- User Menu -->
        <flux:dropdown position="top" align="start" class="max-lg:hidden">
            <flux:profile avatar="" name="{{ Auth::user()->name }}" />

            <flux:menu>
                <flux:menu.item icon="arrow-right-start-on-rectangle" href="{{ route('logout') }}"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    Logout
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
            @csrf
        </form>
    </flux:sidebar>

    <!-- Mobile Header -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
        <flux:spacer />
        <flux:profile avatar="" />
    </flux:header>

    <!-- Main Content -->
    <flux:main>
        {{ $slot }}
    </flux:main>

    @fluxScripts
</body>
</html>
