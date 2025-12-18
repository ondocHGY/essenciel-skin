@props([
    'ingredient',
    'pointColor' => '#10B981',
])

<div class="w-full flex-shrink-0 flex px-5 pb-5">
    <div class="flex items-center gap-6 w-full">
        {{-- 성분 이미지 --}}
        <div class="bg-white rounded-xl overflow-hidden flex-shrink-0 flex items-center justify-center" style="width: 150px; height: 150px; min-width: 150px;">
            @if($ingredient->image)
                <img src="{{ asset('storage/' . $ingredient->image) }}" alt="{{ $ingredient->name }}" style="min-width: 150px; min-height: 150px; object-fit: contain;">
            @else
                <img src="{{ asset('ingredient_default.jpg') }}" alt="{{ $ingredient->name }}" style="min-width: 150px; min-height: 150px; object-fit: contain;">
            @endif
        </div>

        {{-- 성분 정보 --}}
        <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between py-2 mb-2 border-b border-[#D9D9D9]">
                <span class="text-xl font-semibold text-gray-900">{{ $ingredient->name }}</span>
                @if($ingredient->percentage)
                <span class="text-xl font-semibold text-gray-900">{{ $ingredient->percentage }}</span>
                @endif
            </div>
            <p class="text-base text-gray-500 leading-relaxed mb-3">{{ $ingredient->description }}</p>

            {{-- 태그 --}}
            @if($ingredient->tags && count($ingredient->tags) > 0)
            <div class="flex flex-wrap gap-2">
                @foreach($ingredient->tags as $tag)
                <span class="px-3 py-1.5 text-base font-medium rounded text-white" style="background-color: {{ $pointColor }}">{{ $tag }}</span>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
