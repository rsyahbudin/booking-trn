<div class="flex items-center justify-center">
    <img src="{{ asset('img/logo-black.png') }}" alt="Logo" class="h-8 w-auto object-contain block dark:hidden">
    <img src="{{ asset('img/logo-white.png') }}" alt="Logo" class="h-8 w-auto object-contain hidden dark:block">
</div>
<div class="ms-2 grid flex-1 text-start text-sm">
    <span class="mb-0.5 truncate leading-tight font-bold">{{ \App\Models\SiteSetting::get('cafe_name', 'Teras Rumah Nenek') }}</span>
</div>
