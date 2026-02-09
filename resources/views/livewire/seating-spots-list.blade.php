<?php

use App\Models\SeatingSpot;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public function with(): array
    {
        return [
            'seatingSpots' => SeatingSpot::active()->paginate(6),
        ];
    }
}; ?>

<div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-8">
        @foreach ($seatingSpots as $spot)
            <div class="bg-white dark:bg-zinc-800 rounded-2xl overflow-hidden shadow-lg card-hover border border-zinc-100 dark:border-zinc-700 h-full flex flex-col">
                @if ($spot->image_url)
                    <div class="aspect-[4/3] overflow-hidden">
                        <img src="{{ $spot->image_url }}" alt="{{ $spot->name }}" class="w-full h-full object-cover hover:scale-105 transition duration-300">
                    </div>
                @else
                    <div class="aspect-[4/3] bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                        <span class="text-6xl">🪑</span>
                    </div>
                @endif
                <div class="p-6 flex-1 flex flex-col">
                    <h3 class="font-bold text-xl text-zinc-800 dark:text-white mb-2">{{ $spot->name }}</h3>
                    @if ($spot->description)
                        <p class="text-zinc-600 dark:text-zinc-400 mb-4 flex-1">{{ $spot->description }}</p>
                    @endif
                    <div class="flex items-center justify-between mt-auto pt-4 border-t border-zinc-100 dark:border-zinc-700/50">
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

    <!-- Custom Pagination -->
    <div class="mt-8">
        {{ $seatingSpots->links(data: ['scrollTo' => '#spots']) }}
    </div>
</div>
