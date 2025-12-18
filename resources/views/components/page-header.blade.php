@props([
    'title',
    'description' => null,
    'backUrl' => null,
])

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
    <div class="flex items-center gap-3">
        @if($backUrl)
        <a href="{{ $backUrl }}" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        @endif
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">{{ $title }}</h1>
            @if($description)
                <p class="text-gray-600 mt-1">{{ $description }}</p>
            @endif
        </div>
    </div>

    @if($slot->isNotEmpty())
    <div class="flex items-center gap-3">
        {{ $slot }}
    </div>
    @endif
</div>
