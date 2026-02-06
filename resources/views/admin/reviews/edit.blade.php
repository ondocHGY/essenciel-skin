@extends('layouts.admin')

@section('title', '리뷰 수정')

@section('content')
<div class="max-w-3xl mx-auto">
    {{-- 페이지 헤더 --}}
    <x-page-header title="리뷰 수정" description="리뷰 내용과 설정을 수정합니다">
        <x-button :href="route('admin.reviews.index')" variant="secondary" size="md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            목록으로
        </x-button>
    </x-page-header>

    {{-- 플래시 메시지 --}}
    <x-flash-messages />

    {{-- 수정 폼 --}}
    <div class="bg-white rounded-xl shadow-sm p-6 lg:p-8">
        <form action="{{ route('admin.reviews.update', $review) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- 제품 선택 --}}
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">제품 <span class="text-red-500">*</span></label>
                <select name="product_id" required
                        class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 @error('product_id') border-red-500 @enderror">
                    @foreach($products as $product)
                    <option value="{{ $product->id }}" {{ old('product_id', $review->product_id) == $product->id ? 'selected' : '' }}>
                        {{ $product->name }} ({{ $product->brand }})
                    </option>
                    @endforeach
                </select>
                @error('product_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- 플랫폼 --}}
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">플랫폼 <span class="text-red-500">*</span></label>
                <select name="platform" required
                        class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 @error('platform') border-red-500 @enderror">
                    @foreach($platforms as $key => $label)
                    <option value="{{ $key }}" {{ old('platform', $review->platform) == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('platform')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- 리뷰 내용 --}}
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">리뷰 내용 <span class="text-red-500">*</span></label>
                <textarea name="content" rows="5" required
                          class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 @error('content') border-red-500 @enderror">{{ old('content', $review->content) }}</textarea>
                @error('content')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- 평점 & 작성자 --}}
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">평점 <span class="text-red-500">*</span></label>
                    <select name="rating" required
                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 @error('rating') border-red-500 @enderror">
                        @for($i = 5; $i >= 1; $i--)
                        <option value="{{ $i }}" {{ old('rating', $review->rating) == $i ? 'selected' : '' }}>{{ $i }}점</option>
                        @endfor
                    </select>
                    @error('rating')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">작성자</label>
                    <input type="text" name="author" value="{{ old('author', $review->author) }}"
                           class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 @error('author') border-red-500 @enderror"
                           placeholder="익명">
                    @error('author')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- 옵션 --}}
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">옵션</label>
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_visible" value="1"
                               {{ old('is_visible', $review->is_visible) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">노출</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_featured" value="1"
                               {{ old('is_featured', $review->is_featured) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">추천 리뷰</span>
                    </label>
                </div>
            </div>

            {{-- 메타 정보 --}}
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h4 class="text-sm font-medium text-gray-900 mb-2">리뷰 정보</h4>
                <dl class="grid grid-cols-2 gap-2 text-sm">
                    <dt class="text-gray-500">리뷰 ID:</dt>
                    <dd class="text-gray-900">#{{ $review->id }}</dd>
                    <dt class="text-gray-500">외부 ID:</dt>
                    <dd class="text-gray-900 font-mono text-xs">{{ $review->external_id ?? '-' }}</dd>
                    <dt class="text-gray-500">작성일:</dt>
                    <dd class="text-gray-900">{{ $review->reviewed_at?->format('Y-m-d H:i') ?? '-' }}</dd>
                    <dt class="text-gray-500">등록일:</dt>
                    <dd class="text-gray-900">{{ $review->created_at->format('Y-m-d H:i') }}</dd>
                </dl>
            </div>

            {{-- 제출 버튼 --}}
            <div class="flex justify-between">
                <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST"
                      onsubmit="return confirm('정말 삭제하시겠습니까?')">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" size="lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        삭제
                    </x-button>
                </form>

                <div class="flex gap-3">
                    <x-button :href="route('admin.reviews.index')" variant="secondary" size="lg">
                        취소
                    </x-button>
                    <x-button type="submit" variant="primary" size="lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        저장
                    </x-button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
