@props([
    'label',
    'value',
    'icon' => null,
    'iconBg' => 'bg-blue-100',
    'iconColor' => 'text-blue-600',
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl p-5 lg:p-6 shadow-sm']) }}>
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs lg:text-sm text-gray-500 uppercase tracking-wide">{{ $label }}</p>
            <p class="text-2xl lg:text-3xl font-bold text-gray-900 mt-1">{{ $value }}</p>
        </div>
        @if($icon)
        <div class="w-12 h-12 lg:w-14 lg:h-14 rounded-full {{ $iconBg }} flex items-center justify-center">
            <div class="w-6 h-6 lg:w-7 lg:h-7 {{ $iconColor }}">
                {!! $icon !!}
            </div>
        </div>
        @endif
    </div>
</div>
