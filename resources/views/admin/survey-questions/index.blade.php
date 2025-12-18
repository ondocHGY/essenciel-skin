@extends('layouts.admin')

@section('title', '설문 질문 관리')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- 페이지 헤더 --}}
    <x-page-header title="설문 질문 관리" :description="'총 ' . $questions->count() . '개의 질문이 등록되어 있습니다'">
        <x-button :href="route('admin.survey-questions.create')" variant="primary" size="md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            질문 추가
        </x-button>
    </x-page-header>

    {{-- 플래시 메시지 --}}
    <x-flash-messages />

    <!-- 설명 카드 -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="font-medium text-blue-900">설문 질문과 효능 계수 관리</p>
                <p class="text-sm text-blue-700 mt-1">
                    각 질문의 옵션에 설정된 <strong>Modifier 값</strong>이 분석 결과의 효능 예측에 반영됩니다.
                    Modifier가 1.0보다 크면 효과 증가, 작으면 효과 감소를 의미합니다.
                </p>
            </div>
        </div>
    </div>

    <!-- 질문 목록 -->
    <div class="space-y-4" id="question-list">
        @forelse($questions as $question)
        <div class="bg-white rounded-xl shadow-sm overflow-hidden" data-id="{{ $question->id }}">
            <div class="p-5 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="cursor-move text-gray-400 hover:text-gray-600 drag-handle">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded">{{ $question->key }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full
                                    {{ $question->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $question->is_active ? '활성' : '비활성' }}
                                </span>
                                <span class="text-xs px-2 py-0.5 bg-blue-50 text-blue-600 rounded-full">
                                    @switch($question->category)
                                        @case('basic') 기본 정보 @break
                                        @case('lifestyle') 생활 습관 @break
                                        @case('habit') 케어 습관 @break
                                    @endswitch
                                </span>
                            </div>
                            <h3 class="font-semibold text-gray-900 mt-1">{{ $question->title }}</h3>
                            @if($question->subtitle)
                            <p class="text-sm text-gray-500 mt-0.5">{{ $question->subtitle }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.survey-questions.edit', $question) }}"
                           class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="수정">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </a>
                        <form action="{{ route('admin.survey-questions.destroy', $question) }}" method="POST"
                              onsubmit="return confirm('이 질문을 삭제하시겠습니까? 연결된 모든 옵션도 함께 삭제됩니다.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="삭제">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 옵션 목록 -->
            <div class="px-5 py-3 bg-gray-50">
                <div class="flex flex-wrap gap-2">
                    @foreach($question->options as $option)
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-sm
                                {{ !$option->is_active ? 'opacity-50' : '' }}">
                        <span class="font-medium text-gray-700">{{ $option->label }}</span>
                        <span class="text-xs px-1.5 py-0.5 rounded
                            {{ $option->modifier > 1 ? 'bg-green-100 text-green-700' : ($option->modifier < 1 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">
                            {{ $option->modifier > 1 ? '+' : '' }}{{ round(($option->modifier - 1) * 100) }}%
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl shadow-sm p-12 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-gray-500 mb-4">등록된 설문 질문이 없습니다</p>
            <a href="{{ route('admin.survey-questions.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                첫 질문 추가하기
            </a>
        </div>
        @endforelse
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const el = document.getElementById('question-list');
    if (el && typeof Sortable !== 'undefined') {
        new Sortable(el, {
            animation: 150,
            handle: '.drag-handle',
            onEnd: function(evt) {
                const order = Array.from(el.children).map(item => item.dataset.id);
                fetch('{{ route("admin.survey-questions.update-order") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ order: order })
                });
            }
        });
    }
});
</script>
@endsection
