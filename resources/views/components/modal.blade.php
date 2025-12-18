@props([
    'name' => 'showModal',
    'maxWidth' => 'md',
    'closeOnBackdrop' => true,
    'bgColor' => 'white',
])

@php
    $maxWidthClasses = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        'full' => 'max-w-full mx-4',
    ];

    $maxWidthClass = $maxWidthClasses[$maxWidth] ?? $maxWidthClasses['md'];

    $bgClasses = [
        'white' => 'bg-white',
        'dark' => 'bg-slate-900',
        'gray' => 'bg-gray-100',
    ];

    $bgClass = $bgClasses[$bgColor] ?? $bgClasses['white'];
@endphp

<div x-show="{{ $name }}"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4"
     @if($closeOnBackdrop) @click.self="{{ $name }} = false" @endif
     {{ $attributes }}>

    <div x-show="{{ $name }}"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="{{ $bgClass }} rounded-2xl w-full {{ $maxWidthClass }} overflow-hidden shadow-xl"
         @click.stop>
        {{ $slot }}
    </div>
</div>
