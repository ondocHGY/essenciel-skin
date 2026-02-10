@extends('layouts.admin')

@section('title', '리뷰 엑셀 업로드')

@section('content')
<div class="max-w-3xl mx-auto">
    {{-- 페이지 헤더 --}}
    <x-page-header title="리뷰 엑셀 업로드" description="스토어별 리뷰 데이터를 엑셀 파일로 업로드합니다">
        <x-button :href="route('admin.reviews.index')" variant="secondary" size="md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            목록으로
        </x-button>
    </x-page-header>

    {{-- 플래시 메시지 --}}
    <x-flash-messages />

    {{-- 업로드 폼 --}}
    <div class="bg-white rounded-xl shadow-sm p-6 lg:p-8">
        <form action="{{ route('admin.reviews.upload.submit') }}" method="POST" enctype="multipart/form-data" x-data="uploadForm()">
            @csrf

            {{-- 제품 선택 --}}
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">제품 선택 <span class="text-xs text-gray-400 font-normal">(선택사항 - 엑셀 내 상품코드로 자동 매칭)</span></label>
                <select name="product_id"
                        class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 @error('product_id') border-red-500 @enderror">
                    <option value="">자동 매칭 (상품코드 기반)</option>
                    @foreach($products as $product)
                    <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                        {{ $product->name }} ({{ $product->brand }})
                    </option>
                    @endforeach
                </select>
                @error('product_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- 플랫폼 선택 --}}
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">플랫폼 선택 <span class="text-red-500">*</span></label>
                <select name="platform" required
                        class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 @error('platform') border-red-500 @enderror">
                    <option value="">플랫폼을 선택하세요</option>
                    @foreach($platforms as $key => $label)
                    <option value="{{ $key }}" {{ old('platform') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('platform')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- 파일 업로드 --}}
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">엑셀 파일 <span class="text-red-500">*</span></label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-gray-400 transition-colors"
                     :class="{ 'border-blue-500 bg-blue-50': isDragging }"
                     @dragover.prevent="isDragging = true"
                     @dragleave.prevent="isDragging = false"
                     @drop.prevent="handleDrop($event)">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label for="file" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                <span>파일 선택</span>
                                <input id="file" name="file" type="file" class="sr-only" accept=".xlsx,.xls,.csv" required @change="handleFileSelect($event)">
                            </label>
                            <p class="pl-1">또는 드래그 앤 드롭</p>
                        </div>
                        <p class="text-xs text-gray-500">XLSX, XLS, CSV (최대 10MB)</p>
                        <template x-if="fileName">
                            <p class="text-sm text-blue-600 font-medium mt-2" x-text="fileName"></p>
                        </template>
                    </div>
                </div>
                @error('file')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- 안내 사항 --}}
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h4 class="text-sm font-medium text-gray-900 mb-2">엑셀 파일 형식 안내</h4>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span><strong>필수 컬럼:</strong> 리뷰 내용 (댓글, 내용, content, review 등)</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span><strong>선택 컬럼:</strong> 평점, 작성자, 작성일, 리뷰ID</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-yellow-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <span>5자 미만 리뷰는 자동 스킵됩니다</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-purple-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>중복 리뷰는 자동으로 업데이트됩니다</span>
                    </li>
                </ul>
                <div class="mt-4">
                    <a href="{{ route('admin.reviews.download-sample') }}" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-700 font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        샘플 엑셀 파일 다운로드
                    </a>
                </div>
            </div>

            {{-- 제출 버튼 --}}
            <div class="flex justify-end gap-3">
                <x-button :href="route('admin.reviews.index')" variant="secondary" size="lg">
                    취소
                </x-button>
                <x-button type="submit" variant="primary" size="lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    업로드
                </x-button>
            </div>
        </form>
    </div>
</div>

<script>
function uploadForm() {
    return {
        isDragging: false,
        fileName: '',

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                this.fileName = file.name;
            }
        },

        handleDrop(event) {
            this.isDragging = false;
            const file = event.dataTransfer.files[0];
            if (file) {
                const input = document.getElementById('file');
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                input.files = dataTransfer.files;
                this.fileName = file.name;
            }
        }
    }
}
</script>
@endsection
