@extends('layouts.admin')

@section('title', $product->name . ' - 성분 관리')

@section('content')
<div class="max-w-6xl mx-auto">
    {{-- 페이지 헤더 --}}
    <x-page-header
        title="{{ $product->name }} - Active Ingredients"
        description="제품 소개 페이지에 표시되는 성분을 관리합니다"
        :backUrl="route('admin.products.edit', $product)" />

    {{-- 플래시 메시지 --}}
    <x-flash-messages />

    {{-- 상단 액션 --}}
    <div class="flex items-center justify-between mb-6">
        <div class="text-sm text-gray-500">
            총 <span class="font-semibold text-gray-900">{{ $ingredients->count() }}</span>개 성분
        </div>
        <x-button :href="route('admin.products.ingredients.create', $product)" variant="primary">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            성분 추가
        </x-button>
    </div>

    {{-- 성분 목록 --}}
    @if($ingredients->count() > 0)
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="divide-y divide-gray-100" id="sortable-ingredients">
            @foreach($ingredients as $ingredient)
            <div class="p-4 hover:bg-gray-50 transition-colors flex items-center gap-4" data-id="{{ $ingredient->id }}">
                {{-- 드래그 핸들 --}}
                <div class="cursor-move text-gray-400 hover:text-gray-600 drag-handle">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                    </svg>
                </div>

                {{-- 이미지 --}}
                <div class="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0 flex items-center justify-center">
                    @if($ingredient->image)
                        <img src="{{ asset('storage/' . $ingredient->image) }}" alt="{{ $ingredient->name }}" class="w-full h-full object-contain p-1">
                    @else
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="8" r="2" stroke-width="1.5"/>
                            <circle cx="6" cy="16" r="2" stroke-width="1.5"/>
                            <circle cx="18" cy="16" r="2" stroke-width="1.5"/>
                            <line x1="12" y1="10" x2="7" y2="14.5" stroke-width="1.5"/>
                            <line x1="12" y1="10" x2="17" y2="14.5" stroke-width="1.5"/>
                        </svg>
                    @endif
                </div>

                {{-- 정보 --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-semibold text-gray-900">{{ $ingredient->name }}</span>
                        @if($ingredient->percentage)
                        <span class="text-emerald-600 text-sm font-medium">{{ $ingredient->percentage }}</span>
                        @endif
                        @if(!$ingredient->is_active)
                        <span class="px-2 py-0.5 bg-gray-100 text-gray-500 text-xs rounded-full">비활성</span>
                        @endif
                    </div>
                    @if($ingredient->description)
                    <p class="text-sm text-gray-500 truncate">{{ $ingredient->description }}</p>
                    @endif
                    @if($ingredient->tags && count($ingredient->tags) > 0)
                    <div class="flex flex-wrap gap-1 mt-1">
                        @foreach($ingredient->tags as $tag)
                        <span class="px-2 py-0.5 bg-emerald-50 text-emerald-600 text-xs rounded-full">{{ $tag }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>

                {{-- 액션 --}}
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.products.ingredients.edit', [$product, $ingredient]) }}"
                       class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </a>
                    <form action="{{ route('admin.products.ingredients.destroy', [$product, $ingredient]) }}" method="POST"
                          onsubmit="return confirm('정말 삭제하시겠습니까?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-3 text-center">드래그하여 순서를 변경할 수 있습니다</p>
    @else
    <x-empty-state
        title="등록된 성분이 없습니다"
        description="새로운 성분을 추가하여 제품 소개 페이지에 표시해보세요"
        :actionUrl="route('admin.products.ingredients.create', $product)"
        actionText="첫 성분 추가하기" />
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const el = document.getElementById('sortable-ingredients');
    if (el) {
        new Sortable(el, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function(evt) {
                const order = Array.from(el.children).map(item => item.dataset.id);
                fetch('{{ route("admin.products.ingredients.reorder", $product) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ order })
                });
            }
        });
    }
});
</script>
@endpush
@endsection
