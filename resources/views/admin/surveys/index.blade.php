@extends('layouts.admin')

@section('title', '설문 결과')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- 페이지 헤더 --}}
    <x-page-header title="설문 결과 관리" :description="'총 ' . $results->total() . '건의 분석 결과'">
        <x-button :href="route('admin.surveys.export', request()->query())" variant="success" size="md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
            </svg>
            CSV 내보내기
        </x-button>
    </x-page-header>

    {{-- 플래시 메시지 --}}
    <x-flash-messages />

    <!-- 필터 -->
    <div class="bg-white rounded-xl shadow-sm p-5 lg:p-6 mb-6">
        <form method="GET" action="{{ route('admin.surveys.index') }}">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
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
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">연령대</label>
                    <select name="age_group" class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">전체</option>
                        @foreach($ageGroups as $age)
                        <option value="{{ $age }}" {{ request('age_group') == $age ? 'selected' : '' }}>{{ $age }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">시작일</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">종료일</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="flex items-end gap-2">
                    <x-button type="submit" variant="primary" size="md" class="flex-1">
                        검색
                    </x-button>
                    <x-button :href="route('admin.surveys.index')" variant="secondary" size="md">
                        초기화
                    </x-button>
                </div>
            </div>
        </form>
    </div>

    <!-- 결과 테이블 -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">제품</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">연령대</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">효능</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">성별</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">개선율</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">일시</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">관리</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($results as $result)
                    @php
                        $metrics = $result->metrics ?? [];
                        $efficacyType = $metrics['efficacy_type'] ?? 'moisture';
                        $changePercent = $metrics['change_percent'] ?? 0;
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm text-gray-500">#{{ $result->id }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-900">{{ $result->product?->name ?? '-' }}</p>
                            <p class="text-sm text-gray-500">{{ $result->product?->brand ?? '' }}</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 hidden md:table-cell">{{ $result->profile?->age_group ?? '-' }}</td>
                        <td class="px-6 py-4 hidden md:table-cell">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $efficacyLabels[$efficacyType] ?? $efficacyType }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 hidden lg:table-cell">
                            @php
                                $genderLabels = ['female' => '여성', 'male' => '남성', 'other' => '기타'];
                            @endphp
                            {{ $genderLabels[$result->profile?->gender] ?? $result->profile?->gender ?? '-' }}
                        </td>
                        <td class="px-6 py-4 hidden lg:table-cell">
                            @if($changePercent > 0)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                +{{ round($changePercent, 1) }}%
                            </span>
                            @else
                            <span class="text-gray-400 text-sm">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $result->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.surveys.show', $result) }}"
                                   class="text-blue-600 hover:text-blue-700" title="상세보기">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                <form action="{{ route('admin.surveys.destroy', $result) }}" method="POST"
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
                        <td colspan="8" class="px-6 py-12 text-center">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <p class="text-gray-500">검색 결과가 없습니다</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 페이지네이션 -->
    @if($results->hasPages())
    <div class="mt-6">
        {{ $results->links() }}
    </div>
    @endif
</div>
@endsection
