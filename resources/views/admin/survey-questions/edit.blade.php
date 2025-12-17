@extends('layouts.admin')

@section('title', '설문 질문 수정')

@section('content')
<div class="max-w-4xl mx-auto" x-data="questionForm()">
    <!-- 페이지 헤더 -->
    <div class="mb-8">
        <a href="{{ route('admin.survey-questions.index') }}" class="inline-flex items-center gap-1 text-gray-500 hover:text-gray-700 mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            목록으로
        </a>
        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">설문 질문 수정</h1>
        <p class="text-gray-600 mt-1">질문과 옵션의 내용 및 효능 계수를 수정합니다</p>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
        <ul class="list-disc list-inside text-sm">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('admin.survey-questions.update', $surveyQuestion) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- 기본 정보 -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">질문 정보</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">질문 키 (Key) <span class="text-red-500">*</span></label>
                    <input type="text" name="key" value="{{ old('key', $surveyQuestion->key) }}" required
                           pattern="[a-z_]+" placeholder="예: sleep_hours"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">영문 소문자와 언더스코어(_)만 사용 가능</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">카테고리 <span class="text-red-500">*</span></label>
                    <select name="category" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="basic" {{ old('category', $surveyQuestion->category) == 'basic' ? 'selected' : '' }}>기본 정보 (연령, 성별 등)</option>
                        <option value="lifestyle" {{ old('category', $surveyQuestion->category) == 'lifestyle' ? 'selected' : '' }}>생활 습관 (수면, 스트레스 등)</option>
                        <option value="habit" {{ old('category', $surveyQuestion->category) == 'habit' ? 'selected' : '' }}>케어 습관 (스킨케어 등)</option>
                    </select>
                </div>
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">질문 제목 <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title', $surveyQuestion->title) }}" required
                       placeholder="예: 평균 수면 시간은 어떻게 되시나요?"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">부가 설명 (Subtitle)</label>
                <input type="text" name="subtitle" value="{{ old('subtitle', $surveyQuestion->subtitle) }}"
                       placeholder="예: 피부 재생 능력을 파악해요"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="mt-6 grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">정렬 순서</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $surveyQuestion->sort_order) }}" min="0"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="flex items-center">
                    <label class="flex items-center gap-2 cursor-pointer mt-6">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $surveyQuestion->is_active) ? 'checked' : '' }}
                               class="w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">활성화</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- 옵션 설정 -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">옵션 설정</h2>
                    <p class="text-sm text-gray-500 mt-1">각 옵션에 효능 보정계수(Modifier)를 설정합니다</p>
                </div>
                <button type="button" @click="addOption()"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-50 text-blue-600 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    옵션 추가
                </button>
            </div>

            <!-- Modifier 설명 -->
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4 text-sm">
                <p class="text-amber-800">
                    <strong>Modifier 값 설명:</strong>
                    1.0 = 기준값 (효과 변화 없음) |
                    1.15 = +15% 효과 증가 |
                    0.8 = -20% 효과 감소
                </p>
            </div>

            <div class="space-y-4">
                <template x-for="(option, index) in options" :key="index">
                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex items-start gap-4">
                            <input type="hidden" :name="'options[' + index + '][id]'" x-model="option.id">
                            <div class="flex-1 grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">값 (Value)</label>
                                    <input type="text" :name="'options[' + index + '][value]'" x-model="option.value" required
                                           placeholder="예: under6"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">라벨 (표시명)</label>
                                    <input type="text" :name="'options[' + index + '][label]'" x-model="option.label" required
                                           placeholder="예: 6시간 미만"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Modifier</label>
                                    <div class="relative">
                                        <input type="number" :name="'options[' + index + '][modifier]'" x-model="option.modifier" required
                                               step="0.01" min="0.1" max="2.0"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs"
                                              :class="option.modifier > 1 ? 'text-green-600' : (option.modifier < 1 ? 'text-red-600' : 'text-gray-400')"
                                              x-text="option.modifier > 1 ? '+' + Math.round((option.modifier - 1) * 100) + '%' : (option.modifier < 1 ? Math.round((option.modifier - 1) * 100) + '%' : '±0%')">
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">설명 (선택)</label>
                                    <input type="text" :name="'options[' + index + '][description]'" x-model="option.description"
                                           placeholder="추가 설명"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                            <div class="flex flex-col gap-2 mt-5">
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="checkbox" :name="'options[' + index + '][is_active]'" value="1" x-model="option.is_active"
                                           class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                                    <span class="text-xs text-gray-600">활성</span>
                                </label>
                                <button type="button" @click="removeOption(index)" x-show="options.length > 2"
                                        class="p-1.5 text-red-500 hover:bg-red-50 rounded transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- 제출 버튼 -->
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.survey-questions.index') }}"
               class="px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                취소
            </a>
            <button type="submit"
                    class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                변경사항 저장
            </button>
        </div>
    </form>
</div>

@php
$optionsData = $surveyQuestion->options->map(fn($o) => [
    'id' => $o->id,
    'value' => $o->value,
    'label' => $o->label,
    'modifier' => $o->modifier,
    'description' => $o->description,
    'is_active' => $o->is_active,
])->toArray();
@endphp
<script>
function questionForm() {
    return {
        options: @json($optionsData),
        addOption() {
            this.options.push({ id: null, value: '', label: '', modifier: 1.0, description: '', is_active: true });
        },
        removeOption(index) {
            if (this.options.length > 2) {
                this.options.splice(index, 1);
            }
        }
    };
}
</script>
@endsection
