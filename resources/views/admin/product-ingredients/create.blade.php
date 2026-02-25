@extends('layouts.admin')

@section('title', $product->name . ' - 성분 추가')

@section('content')
<div class="max-w-3xl mx-auto" x-data="ingredientForm()">
    {{-- 페이지 헤더 --}}
    <x-page-header
        title="성분 추가"
        :description="$product->name . ' 제품에 새로운 성분을 추가합니다'"
        :backUrl="route('admin.products.ingredients.index', $product)" />

    {{-- 플래시 메시지 --}}
    <x-flash-messages />

    <form action="{{ route('admin.products.ingredients.store', $product) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">기본 정보</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- 성분명 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">성분명 <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           placeholder="예: 아쿠아포린"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 함유량 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">함유량 (%)</label>
                    <input type="text" name="percentage" value="{{ old('percentage') }}"
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
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('description') }}</textarea>
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
                        <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <input type="file" name="image" id="image-input" accept="image/*"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-xs text-gray-500 mt-2">PNG, JPG, GIF, WebP (최대 2MB)</p>
                    </div>
                </div>
                @error('image')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- 다국어 번역 --}}
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6" x-data="{ langOpen: false }">
            <div class="flex items-center justify-between cursor-pointer" @click="langOpen = !langOpen">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">다국어 번역</h2>
                    <p class="text-sm text-gray-500">성분명과 설명의 번역을 입력합니다</p>
                </div>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="langOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>

            <div x-show="langOpen" x-cloak class="mt-4 space-y-4">
                @php
                    $locales = ['en' => '🇺🇸 English', 'ja' => '🇯🇵 日本語', 'zh' => '🇨🇳 中文', 'vi' => '🇻🇳 Tiếng Việt', 'ar' => '🇸🇦 العربية'];
                    $existingTranslations = old('translations', []);
                @endphp

                @foreach($locales as $locale => $label)
                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ $label }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">성분명</label>
                            <input type="text" name="translations[{{ $locale }}][name]"
                                   value="{{ $existingTranslations[$locale]['name'] ?? '' }}"
                                   placeholder="성분명 번역"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">설명</label>
                            <input type="text" name="translations[{{ $locale }}][description]"
                                   value="{{ $existingTranslations[$locale]['description'] ?? '' }}"
                                   placeholder="설명 번역"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>
                @endforeach
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

        {{-- 카드 위치 설정 --}}
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">카드 위치 설정</h2>
            <p class="text-sm text-gray-500 mb-4">메인 페이지 제품 썸네일 위에 표시되는 성분 카드의 위치를 설정합니다.</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">위에서 (top)</label>
                    <input type="text" name="card_position[top]" value="{{ old('card_position.top') }}"
                           placeholder="예: 20%"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">왼쪽에서 (left)</label>
                    <input type="text" name="card_position[left]" value="{{ old('card_position.left') }}"
                           placeholder="예: 10%"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">오른쪽에서 (right)</label>
                    <input type="text" name="card_position[right]" value="{{ old('card_position.right') }}"
                           placeholder="예: 5%"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">left와 right 중 하나만 입력하세요. 비워두면 기본 위치가 사용됩니다.</p>
        </div>

        {{-- 추가 설정 --}}
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">추가 설정</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">정렬 순서</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">작은 숫자가 먼저 표시됩니다</p>
                </div>

                <div class="flex items-center">
                    <label class="flex items-center gap-2 cursor-pointer mt-6">
                        <input type="checkbox" name="is_active" value="1" checked
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
                성분 추가
            </x-button>
        </div>
    </form>
</div>

<script>
function ingredientForm() {
    return {
        tags: [],

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
