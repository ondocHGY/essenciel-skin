@extends('layouts.admin')

@section('title', $ingredient->name . ' - 성분 수정')

@section('content')
<div class="max-w-3xl mx-auto" x-data="ingredientForm()">
    {{-- 페이지 헤더 --}}
    <x-page-header
        title="성분 수정"
        :description="$ingredient->name . ' 성분 정보를 수정합니다'"
        :backUrl="route('admin.products.ingredients.index', $product)" />

    {{-- 플래시 메시지 --}}
    <x-flash-messages />

    <form action="{{ route('admin.products.ingredients.update', [$product, $ingredient]) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">기본 정보</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- 성분명 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">성분명 <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $ingredient->name) }}" required
                           placeholder="예: 아쿠아포린"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 함유량 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">함유량 (%)</label>
                    <input type="text" name="percentage" value="{{ old('percentage', $ingredient->percentage) }}"
                           placeholder="예: 2%"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('percentage')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- 설명 --}}
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">설명</label>
                <textarea name="description" rows="3"
                          placeholder="예: 피부 속 수분 통로를 활성화해 수분의 흡수와 이동을 돕음"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('description', $ingredient->description) }}</textarea>
                @error('description')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- 이미지 업로드 --}}
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">성분 이미지</label>
                <div class="flex items-start gap-4">
                    <div class="w-24 h-24 bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center border-2 border-dashed border-gray-300"
                         id="image-preview">
                        @if($ingredient->image)
                            <img src="{{ asset('storage/' . $ingredient->image) }}" class="w-full h-full object-contain p-1">
                        @else
                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        @endif
                    </div>
                    <div class="flex-1">
                        <input type="file" name="image" id="image-input" accept="image/*"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-xs text-gray-500 mt-2">PNG, JPG, GIF, WebP (최대 2MB)</p>
                        @if($ingredient->image)
                        <label class="flex items-center gap-2 mt-2 cursor-pointer">
                            <input type="checkbox" name="remove_image" value="1"
                                   class="w-4 h-4 text-red-600 rounded border-gray-300 focus:ring-red-500">
                            <span class="text-sm text-red-600">이미지 삭제</span>
                        </label>
                        @endif
                    </div>
                </div>
                @error('image')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- 태그 설정 --}}
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">2차 태그</h2>
                    <p class="text-sm text-gray-500">성분의 특성이나 효과를 태그로 표시합니다</p>
                </div>
                <button type="button" @click="addTag()"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-50 text-blue-600 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    태그 추가
                </button>
            </div>

            <div class="space-y-3">
                <template x-for="(tag, index) in tags" :key="index">
                    <div class="flex items-center gap-3">
                        <input type="text" :name="'tags[' + index + ']'" x-model="tag.value"
                               placeholder="예: 수분 순환"
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <button type="button" @click="removeTag(index)"
                                class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </template>
            </div>

            <div x-show="tags.length === 0" class="text-center py-4 text-gray-400 text-sm">
                태그가 없습니다. 위 버튼을 클릭하여 추가하세요.
            </div>
        </div>

        {{-- 추가 설정 --}}
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">추가 설정</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">정렬 순서</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $ingredient->sort_order) }}" min="0"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">작은 숫자가 먼저 표시됩니다</p>
                </div>

                <div class="flex items-center">
                    <label class="flex items-center gap-2 cursor-pointer mt-6">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $ingredient->is_active) ? 'checked' : '' }}
                               class="w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">활성화 (제품 페이지에 표시)</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- 제출 버튼 --}}
        <div class="flex items-center justify-end gap-3">
            <x-button :href="route('admin.products.ingredients.index', $product)" variant="outline" size="lg">
                취소
            </x-button>
            <x-button type="submit" variant="primary" size="lg">
                변경사항 저장
            </x-button>
        </div>
    </form>
</div>

@php
$existingTags = old('tags', $ingredient->tags ?? []);
@endphp

<script>
function ingredientForm() {
    return {
        tags: @json(collect($existingTags)->map(fn($t) => ['value' => $t])->values()->toArray()),

        addTag() {
            this.tags.push({ value: '' });
        },

        removeTag(index) {
            this.tags.splice(index, 1);
        }
    };
}

// 이미지 미리보기
document.getElementById('image-input')?.addEventListener('change', function(e) {
    const preview = document.getElementById('image-preview');
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-contain p-1">`;
        };
        reader.readAsDataURL(file);
    }
});
</script>
@endsection
