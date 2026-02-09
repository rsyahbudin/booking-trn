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

    <!-- Toast Notification for Validation Errors -->
    <div
        x-data="{ 
            show: false, 
            type: 'error',
            title: '',
            messages: [],
            init() {
                // Listen for validation errors from Livewire
                Livewire.hook('message.failed', (component, action, errors) => {
                    if (errors && Object.keys(errors).length > 0) {
                        this.showValidationErrors(errors);
                    }
                });
                
                // Check for validation errors on page load
                @if ($errors->any())
                    this.showValidationErrors({!! json_encode($errors->messages()) !!});
                @endif
            },
            showValidationErrors(errors) {
                this.type = 'error';
                this.title = 'Mohon periksa field berikut:';
                this.messages = [];
                
                // Convert errors object to array of messages
                for (let field in errors) {
                    if (Array.isArray(errors[field])) {
                        this.messages = this.messages.concat(errors[field]);
                    } else {
                        this.messages.push(errors[field]);
                    }
                }
                
                this.show = true;
                setTimeout(() => this.show = false, 6000);
            },
            showSuccess(message) {
                this.type = 'success';
                this.title = 'Berhasil!';
                this.messages = [message];
                this.show = true;
                setTimeout(() => this.show = false, 3000);
            }
        }"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-x-4"
        x-transition:enter-end="opacity-100 translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 translate-x-4"
        class="fixed top-6 right-6 z-[9999] max-w-md"
        style="display: none;"
    >
        <div 
            :class="{
                'bg-red-50 dark:bg-red-900/30 border-red-300 dark:border-red-700': type === 'error',
                'bg-green-50 dark:bg-green-900/30 border-green-300 dark:border-green-700': type === 'success'
            }"
            class="border-l-4 rounded-lg shadow-lg p-4 backdrop-blur-sm"
        >
            <div class="flex items-start gap-3">
                <!-- Icon -->
                <template x-if="type === 'error'">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </template>
                <template x-if="type === 'success'">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </template>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <h3 
                        x-text="title"
                        :class="{
                            'text-red-800 dark:text-red-300': type === 'error',
                            'text-green-800 dark:text-green-300': type === 'success'
                        }"
                        class="font-semibold text-sm mb-1"
                    ></h3>
                    <ul 
                        :class="{
                            'text-red-700 dark:text-red-400': type === 'error',
                            'text-green-700 dark:text-green-400': type === 'success'
                        }"
                        class="text-sm space-y-1"
                    >
                        <template x-for="(msg, index) in messages" :key="index">
                            <li class="flex items-start gap-1.5">
                                <span class="mt-1.5 w-1 h-1 rounded-full bg-current flex-shrink-0"></span>
                                <span x-text="msg"></span>
                            </li>
                        </template>
                    </ul>
                </div>

                <!-- Close Button -->
                <button 
                    @click="show = false"
                    :class="{
                        'text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300': type === 'error',
                        'text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300': type === 'success'
                    }"
                    class="flex-shrink-0 ml-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    @fluxScripts
</body>
</html>
