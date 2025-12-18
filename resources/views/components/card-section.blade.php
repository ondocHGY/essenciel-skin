@props([
    'title',
    'icon' => null,
    'iconBg' => 'bg-slate-800',
    'description' => null,
    'action' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow-sm p-6']) }}>
    <div class="flex items-start justify-between mb-4">
        <div class="flex items-center gap-3">
            @if($icon)
            <div class="w-10 h-10 {{ $iconBg }} rounded-lg flex items-center justify-center flex-shrink-0">
                {!! $icon !!}
            </div>
            @endif
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ $title }}</h2>
                @if($description)
                    <p class="text-sm text-gray-500 mt-0.5">{{ $description }}</p>
                @endif
            </div>
        </div>

        @if($action)
            {{ $action }}
        @endif
    </div>

    {{ $slot }}
</div>
