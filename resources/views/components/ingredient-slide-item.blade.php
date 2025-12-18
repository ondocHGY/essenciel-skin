@props([
    'ingredient',
    'pointColor' => '#10B981',
])

<div class="w-full flex-shrink-0 flex px-5 pb-5">
    <div class="w-full" style="display: grid; grid-template-columns: 1fr 2fr; gap: 16px; align-items: center;">
        {{-- 성분 이미지 --}}
        <div class="bg-white rounded-xl overflow-hidden flex items-center justify-center aspect-square">
            @if($ingredient->image)
                <img src="{{ asset('storage/' . $ingredient->image) }}" alt="{{ $ingredient->name }}" class="w-full h-full object-contain">
            @else
                <img src="{{ asset('ingredient_default.jpg') }}" alt="{{ $ingredient->name }}" class="w-full h-full object-contain">
            @endif
        </div>

        {{-- 성분 정보 --}}
        <div class="min-w-0">
            <div class="flex items-center justify-between py-1.5 mb-1.5" style="border-bottom: 1px solid #D9D9D9;">
                <span class="text-base font-semibold text-gray-900">{{ $ingredient->name }}</span>
                @if($ingredient->percentage)
                <span class="text-base font-semibold text-gray-900">{{ $ingredient->percentage }}</span>
                @endif
            </div>
            <p class="text-sm text-gray-500 leading-relaxed mb-2">{{ $ingredient->description }}</p>

            {{-- 태그 --}}
            @if($ingredient->tags && count($ingredient->tags) > 0)
            <div class="flex flex-wrap gap-1.5">
                @foreach($ingredient->tags as $tag)
                <span class="px-2 py-1 text-sm font-medium rounded text-white" style="background-color: {{ $pointColor }}">{{ $tag }}</span>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
