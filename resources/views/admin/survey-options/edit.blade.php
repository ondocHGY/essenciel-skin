@extends('layouts.admin')

@section('title', $category->name . ' 옵션 관리')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- 헤더 -->
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.survey-options.index') }}" class="text-gray-500 hover:text-gray-700 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-gray-900">{{ $category->name }}</h1>
                @if($category->is_system)
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600">시스템</span>
                @endif
            </div>
            <p class="text-gray-500 font-mono text-sm">{{ $category->key }}</p>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- 영향도 설명 -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <h3 class="font-medium text-blue-800 mb-2">영향도(Modifier) 설명</h3>
        <div class="text-sm text-blue-700 space-y-1">
            <p><span class="font-mono bg-blue-100 px-1 rounded">1.0</span> = 기본값 (효과 100%)</p>
            <p><span class="font-mono bg-green-100 px-1 rounded text-green-700">1.2</span> = 효과 120% (더 좋은 결과 예측)</p>
            <p><span class="font-mono bg-red-100 px-1 rounded text-red-700">0.8</span> = 효과 80% (덜 좋은 결과 예측)</p>
        </div>
    </div>

    <!-- 카테고리 설정 -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">카테고리 설정</h2>
        <form action="{{ route('admin.survey-options.update', $category) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">카테고리명</label>
                    <input type="text" name="name" value="{{ old('name', $category->name) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">상태</label>
                    <label class="relative inline-flex items-center cursor-pointer mt-2">
                        <input type="checkbox" name="is_active" value="1" {{ $category->is_active ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-700">활성화</span>
                    </label>
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">설명 (선택)</label>
                <textarea name="description" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('description', $category->description) }}</textarea>
            </div>
            <button type="submit" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                저장
            </button>
        </form>
    </div>

    <!-- 옵션 추가 -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">새 옵션 추가</h2>
        <form action="{{ route('admin.survey-options.options.store', $category) }}" method="POST" class="flex flex-wrap gap-4">
            @csrf
            <div class="flex-1 min-w-[120px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">저장값</label>
                <input type="text" name="value" placeholder="예: option1"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            <div class="flex-1 min-w-[120px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">표시 텍스트</label>
                <input type="text" name="label" placeholder="예: 옵션 1"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            @if($category->has_icon)
            <div class="w-20">
                <label class="block text-sm font-medium text-gray-700 mb-1">아이콘</label>
                <input type="text" name="icon" placeholder="이모지"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-center text-xl">
            </div>
            @endif
            <div class="w-24">
                <label class="block text-sm font-medium text-gray-700 mb-1">영향도</label>
                <input type="number" name="modifier" value="1.0" step="0.05" min="0.1" max="2.0"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-center">
            </div>
            <div class="flex items-end">
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                    추가
                </button>
            </div>
        </form>
    </div>

    <!-- 옵션 목록 -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">옵션 목록</h2>
            <p class="text-sm text-gray-500 mt-1">드래그하여 순서를 변경할 수 있습니다</p>
        </div>

        @if($category->options->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12"></th>
                        @if($category->has_icon)
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">아이콘</th>
                        @endif
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">저장값</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">표시 텍스트</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">영향도</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-20">상태</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">관리</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200" id="sortable-options">
                    @foreach($category->options as $option)
                    <tr class="hover:bg-gray-50 transition-colors" data-id="{{ $option->id }}">
                        <td class="px-4 py-4">
                            <span class="cursor-move text-gray-400 hover:text-gray-600 drag-handle">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M7 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 2zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 14zm6-8a2 2 0 1 0-.001-4.001A2 2 0 0 0 13 6zm0 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 14z"></path>
                                </svg>
                            </span>
                        </td>
                        @if($category->has_icon)
                        <td class="px-4 py-4 text-2xl">{{ $option->icon }}</td>
                        @endif
                        <td class="px-4 py-4 font-mono text-sm text-gray-600">{{ $option->value }}</td>
                        <td class="px-4 py-4 font-medium text-gray-900">{{ $option->label }}</td>
                        <td class="px-4 py-4">
                            @php
                                $modifier = $option->modifier ?? 1.0;
                                $modifierClass = $modifier > 1 ? 'bg-green-100 text-green-700' : ($modifier < 1 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700');
                                $modifierText = $modifier == 1.0 ? '1.0 (기본)' : number_format($modifier, 2);
                            @endphp
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-mono font-medium {{ $modifierClass }}">
                                {{ $modifierText }}
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            @if($option->is_active)
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700">활성</span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-700">비활성</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button onclick="openEditModal({{ $option->id }}, '{{ addslashes($option->value) }}', '{{ addslashes($option->label) }}', '{{ addslashes($option->icon ?? '') }}', {{ $option->modifier ?? 1.0 }}, {{ $option->is_active ? 'true' : 'false' }})"
                                        class="text-blue-600 hover:text-blue-700 p-1" title="수정">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <form action="{{ route('admin.survey-options.options.destroy', $option) }}" method="POST" class="inline"
                                      onsubmit="return confirm('정말 삭제하시겠습니까?\n\n주의: 이미 수집된 설문 데이터에서 이 옵션을 선택한 응답이 있을 수 있습니다.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 p-1" title="삭제">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-8 text-center text-gray-500">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
            </svg>
            <p>등록된 옵션이 없습니다.</p>
            <p class="text-sm">위 폼에서 새 옵션을 추가해주세요.</p>
        </div>
        @endif
    </div>
</div>

<!-- 수정 모달 -->
<div id="editModal" class="fixed inset-0 bg-gray-900/50 z-50 hidden items-center justify-center" onclick="if(event.target === this) closeEditModal()">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">옵션 수정</h3>
        <form id="editForm" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">저장값</label>
                    <input type="text" name="value" id="editValue"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">표시 텍스트</label>
                    <input type="text" name="label" id="editLabel"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                </div>
                @if($category->has_icon)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">아이콘</label>
                    <input type="text" name="icon" id="editIcon"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-center text-xl">
                </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">영향도</label>
                    <input type="number" name="modifier" id="editModifier" step="0.05" min="0.1" max="2.0"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">1.0 = 기본값, 1.2 = 120% 효과, 0.8 = 80% 효과</p>
                </div>
                <div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" id="editIsActive" value="1" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-700">활성화</span>
                    </label>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                    취소
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    저장
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sortableEl = document.getElementById('sortable-options');
    if (sortableEl) {
        new Sortable(sortableEl, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'bg-blue-50',
            onEnd: function(evt) {
                const orders = [...document.querySelectorAll('#sortable-options tr')]
                    .map(tr => parseInt(tr.dataset.id));

                fetch('{{ route('admin.survey-options.options.reorder', $category) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ orders })
                });
            }
        });
    }
});

function openEditModal(id, value, label, icon, modifier, isActive) {
    const modal = document.getElementById('editModal');
    const form = document.getElementById('editForm');

    form.action = '/admin/survey-options/options/' + id;
    document.getElementById('editValue').value = value;
    document.getElementById('editLabel').value = label;
    @if($category->has_icon)
    document.getElementById('editIcon').value = icon;
    @endif
    document.getElementById('editModifier').value = modifier;
    document.getElementById('editIsActive').checked = isActive;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>
@endpush
