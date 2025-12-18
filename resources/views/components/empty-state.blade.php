@props([
    'icon' => null,
    'title' => '데이터가 없습니다',
    'description' => null,
    'actionUrl' => null,
    'actionLabel' => null,
])

<div {{ $attributes->merge(['class' => 'text-center py-12']) }}>
    @if($icon)
    <div class="mx-auto mb-4">
        {!! $icon !!}
    </div>
    @else
    <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
    </svg>
    @endif

    <p class="text-gray-500 mb-4">{{ $title }}</p>

    @if($description)
        <p class="text-sm text-gray-400 mb-4">{{ $description }}</p>
    @endif

    @if($actionUrl && $actionLabel)
    <x-button :href="$actionUrl" variant="primary" size="md">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        {{ $actionLabel }}
    </x-button>
    @endif

    {{ $slot }}
</div>
