@extends('layouts.admin')

@section('title', '리뷰 관리')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- 페이지 헤더 --}}
    <x-page-header title="리뷰 관리" :description="'총 ' . $stats['total'] . '건의 리뷰'">
        <x-button :href="route('admin.reviews.upload')" variant="success" size="md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
            </svg>
            엑셀 업로드
        </x-button>
    </x-page-header>

    {{-- 플래시 메시지 --}}
    <x-flash-messages />

    {{-- 통계 카드 --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-sm text-gray-500">전체 리뷰</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-sm text-gray-500">노출 리뷰</div>
            <div class="text-2xl font-bold text-green-600 mt-1">{{ number_format($stats['visible']) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-sm text-gray-500">추천 리뷰</div>
            <div class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($stats['featured']) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-sm text-gray-500">평균 평점</div>
            <div class="text-2xl font-bold text-yellow-600 mt-1">{{ $stats['average_rating'] }}</div>
        </div>
    </div>

    {{-- 필터 --}}
    <div class="bg-white rounded-xl shadow-sm p-5 lg:p-6 mb-6">
        <form method="GET" action="{{ route('admin.reviews.index') }}">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">제품</label>
                    <select name="product_id" class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">전체</option>
                        @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">플랫폼</label>
                    <select name="platform" class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">전체</option>
                        @foreach($platforms as $key => $label)
                        <option value="{{ $key }}" {{ request('platform') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">최소 평점</label>
                    <select name="rating" class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">전체</option>
                        @for($i = 5; $i >= 1; $i--)
                        <option value="{{ $i }}" {{ request('rating') == $i ? 'selected' : '' }}>{{ $i }}점 이상</option>
                        @endfor
                    </select>
                </div>

                <div class="col-span-2 md:col-span-1 lg:col-span-2 flex items-end gap-2">
                    <x-button type="submit" variant="primary" size="md" class="flex-1">
                        검색
                    </x-button>
                    <x-button :href="route('admin.reviews.index')" variant="secondary" size="md">
                        초기화
                    </x-button>
                </div>
            </div>
        </form>
    </div>

    {{-- 리뷰 테이블 --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">리뷰</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">제품</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">플랫폼</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">평점</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">상태</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">작성일</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">관리</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($reviews as $review)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="max-w-md">
                                <p class="text-sm text-gray-900 line-clamp-2">{{ $review->content }}</p>
                                @if($review->author)
                                <p class="text-xs text-gray-500 mt-1">{{ $review->author }}</p>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 hidden md:table-cell">
                            <p class="text-sm font-medium text-gray-900">{{ $review->product?->name ?? '-' }}</p>
                        </td>
                        <td class="px-6 py-4 hidden lg:table-cell">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                {{ $platforms[$review->platform] ?? $review->platform }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <span class="text-sm font-medium">{{ number_format($review->rating, 1) }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center hidden md:table-cell">
                            <div class="flex items-center justify-center gap-2">
                                @if($review->is_visible)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">노출</span>
                                @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">숨김</span>
                                @endif
                                @if($review->is_featured)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">추천</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 hidden lg:table-cell">
                            {{ $review->reviewed_at?->format('Y-m-d') ?? '-' }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <form action="{{ route('admin.reviews.toggle-visibility', $review) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="{{ $review->is_visible ? 'text-green-600 hover:text-green-700' : 'text-gray-400 hover:text-gray-600' }}" title="{{ $review->is_visible ? '숨기기' : '노출하기' }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if($review->is_visible)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                            @endif
                                        </svg>
                                    </button>
                                </form>
                                <a href="{{ route('admin.reviews.edit', $review) }}" class="text-blue-600 hover:text-blue-700" title="수정">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST"
                                      onsubmit="return confirm('정말 삭제하시겠습니까?')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700" title="삭제">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                            </svg>
                            <p class="text-gray-500">리뷰가 없습니다</p>
                            <a href="{{ route('admin.reviews.upload') }}" class="text-blue-600 hover:text-blue-700 text-sm mt-2 inline-block">엑셀로 리뷰 업로드하기</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 페이지네이션 --}}
    @if($reviews->hasPages())
    <div class="mt-6">
        {{ $reviews->links() }}
    </div>
    @endif
</div>
@endsection
