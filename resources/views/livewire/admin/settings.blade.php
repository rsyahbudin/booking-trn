<?php

use App\Models\SiteSetting;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Cache;

new class extends Component {
    public array $settings = [];

    public function mount(): void
    {
        $this->loadSettings();
    }

    public function loadSettings(): void
    {
        $this->settings = SiteSetting::orderBy('order')->get()->map(function ($setting) {
            return [
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => $setting->value,
                'label' => $setting->label,
                'type' => $setting->type,
            ];
        })->toArray();
    }

    public function save(): void
    {
        foreach ($this->settings as $setting) {
            SiteSetting::where('id', $setting['id'])->update([
                'value' => $setting['value'],
            ]);
        }

        SiteSetting::clearCache();

        session()->flash('message', 'Pengaturan berhasil disimpan!');
    }
}; ?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">Pengaturan Situs</flux:heading>
            <flux:subheading>Kelola informasi cafe yang ditampilkan di website</flux:subheading>
        </div>
    </div>

    @if (session('message'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-green-700 dark:text-green-400 flex items-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                {{ session('message') }}
            </p>
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <!-- Info Dasar -->
        <x-card>
            <flux:heading size="lg" class="mb-4">Informasi Dasar</flux:heading>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($settings as $index => $setting)
                    @if (in_array($setting['key'], ['cafe_name', 'tagline', 'whatsapp', 'instagram']))
                        <div class="{{ $setting['key'] === 'tagline' ? 'md:col-span-2' : '' }}">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                {{ $setting['label'] }}
                            </label>
                            @if ($setting['type'] === 'textarea')
                                <textarea 
                                    wire:model="settings.{{ $index }}.value" 
                                    rows="3"
                                    class="w-full px-4 py-3 rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                ></textarea>
                            @else
                                <input 
                                    type="{{ $setting['type'] === 'url' ? 'url' : 'text' }}" 
                                    wire:model="settings.{{ $index }}.value" 
                                    class="w-full px-4 py-3 rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                >
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </x-card>

        <!-- Lokasi & Jam -->
        <x-card>
            <flux:heading size="lg" class="mb-4">Lokasi & Jam Operasional</flux:heading>
            
            <div class="space-y-4">
                @foreach ($settings as $index => $setting)
                    @if (in_array($setting['key'], ['address', 'gmaps_link', 'operating_hours']))
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                {{ $setting['label'] }}
                            </label>
                            @if ($setting['type'] === 'textarea')
                                <textarea 
                                    wire:model="settings.{{ $index }}.value" 
                                    rows="3"
                                    class="w-full px-4 py-3 rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                ></textarea>
                            @else
                                <input 
                                    type="{{ $setting['type'] === 'url' ? 'url' : 'text' }}" 
                                    wire:model="settings.{{ $index }}.value" 
                                    class="w-full px-4 py-3 rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                >
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </x-card>

        <!-- Template WhatsApp -->
        <x-card>
            <flux:heading size="lg" class="mb-4">Template Pesan WhatsApp</flux:heading>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
                Gunakan placeholder berikut: <code class="bg-zinc-100 dark:bg-zinc-700 px-1 rounded">{booking_code}</code>, 
                <code class="bg-zinc-100 dark:bg-zinc-700 px-1 rounded">{customer_name}</code>, 
                <code class="bg-zinc-100 dark:bg-zinc-700 px-1 rounded">{booking_date}</code>, 
                <code class="bg-zinc-100 dark:bg-zinc-700 px-1 rounded">{guest_count}</code>, 
                <code class="bg-zinc-100 dark:bg-zinc-700 px-1 rounded">{spot_name}</code>, 
                <code class="bg-zinc-100 dark:bg-zinc-700 px-1 rounded">{menu_items}</code>, 
                <code class="bg-zinc-100 dark:bg-zinc-700 px-1 rounded">{subtotal}</code>, 
                <code class="bg-zinc-100 dark:bg-zinc-700 px-1 rounded">{tax}</code>, 
                <code class="bg-zinc-100 dark:bg-zinc-700 px-1 rounded">{total}</code>, 
                <code class="bg-zinc-100 dark:bg-zinc-700 px-1 rounded">{dp_amount}</code>, 
                <code class="bg-zinc-100 dark:bg-zinc-700 px-1 rounded">{paid_amount}</code>, 
                <code class="bg-zinc-100 dark:bg-zinc-700 px-1 rounded">{remaining_text}</code>
            </p>
            
            <div class="space-y-4">
                @foreach ($settings as $index => $setting)
                    @if (in_array($setting['key'], ['wa_template_customer', 'wa_template_confirm']))
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                {{ $setting['label'] }}
                            </label>
                            <textarea 
                                wire:model="settings.{{ $index }}.value" 
                                rows="10"
                                class="w-full px-4 py-3 rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent font-mono text-sm"
                            ></textarea>
                        </div>
                    @endif
                @endforeach
            </div>
        </x-card>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary" icon="check">
                Simpan Pengaturan
            </flux:button>
        </div>
    </form>
</div>
