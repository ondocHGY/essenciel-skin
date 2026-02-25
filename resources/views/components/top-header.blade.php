@props(['product', 'otherProducts' => collect()])

{{-- 상단 헤더 --}}
<div class="bg-black sticky top-0 z-50">
    <div class="max-w-lg mx-auto flex items-center justify-between px-4 py-3">
        <img src="{{ asset('logo_white.png') }}" alt="Essenciel" class="h-5">
        <div class="flex items-center gap-2">
            <x-language-switcher />
            <button @click="menuOpen = !menuOpen" class="text-white p-1">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <circle cx="5" cy="12" r="2"/>
                    <circle cx="12" cy="12" r="2"/>
                    <circle cx="19" cy="12" r="2"/>
                </svg>
            </button>
        </div>
    </div>
</div>

{{-- 메뉴 드롭다운 --}}
<div x-show="menuOpen" x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
     @click.away="menuOpen = false"
     class="fixed top-12 right-2 z-[60] bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden min-w-[180px]">
    <a href="{{ $product->sales_url ?: 'https://essenciel.co.kr' }}" target="_blank" @click="menuOpen = false"
       class="w-full text-left px-5 py-3.5 text-sm font-medium text-gray-800 hover:bg-gray-50 flex items-center gap-3 border-b border-gray-100">
        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        {{ __('제품 상세') }}
    </a>
    <button @click="menuOpen = false; showProductSelector = true"
            class="w-full text-left px-5 py-3.5 text-sm font-medium text-gray-800 hover:bg-gray-50 flex items-center gap-3">
        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
        </svg>
        {{ __('다른 제품 분석') }}
    </button>
</div>

{{-- 다른 제품 분석 바텀 시트 --}}
<div x-show="showProductSelector" x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-[70] bg-black/60"
     @click.self="showProductSelector = false">

    {{-- 바텀 시트 --}}
    <div x-show="showProductSelector"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-y-full"
         x-transition:enter-end="translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-y-0"
         x-transition:leave-end="translate-y-full"
         class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl px-5 py-6"
         style="max-height: 60vh;">

        {{-- 핸들 바 --}}
        <div class="flex justify-center mb-4">
            <div class="w-10 h-1 bg-gray-300 rounded-full"></div>
        </div>

        <h3 class="text-lg font-bold text-gray-900 mb-1">{{ __('분석할 제품을 선택하세요') }}</h3>
        <p class="text-sm text-gray-500 mb-5">{{ __('제품을 선택하면 해당 제품의 페이지로 이동합니다.') }}</p>

        {{-- 제품 가로 스크롤 --}}
        <div class="overflow-x-auto -mx-1 pb-2" style="scrollbar-width: none; -ms-overflow-style: none;">
            <div class="flex gap-4 px-1" style="min-width: max-content;">
                @foreach($otherProducts as $otherProduct)
                <a href="{{ localized_route('product.show', ['code' => $otherProduct->code]) }}"
                   class="flex-shrink-0 w-28 text-center group">
                    <div class="w-28 h-28 rounded-xl overflow-hidden bg-gray-100 mb-2 border border-gray-200 group-hover:border-gray-400 transition-colors">
                        @if($otherProduct->image)
                        <img src="{{ asset('storage/' . $otherProduct->image) }}" alt="{{ $otherProduct->name }}" class="w-full h-full object-contain">
                        @else
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        @endif
                    </div>
                    <p class="text-xs font-medium text-gray-700 truncate">{{ $otherProduct->name }}</p>
                    <p class="text-[10px] text-gray-400 truncate">{{ $otherProduct->brand }}</p>
                </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
