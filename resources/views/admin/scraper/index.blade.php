@extends('layouts.admin')

@section('title', '스크래퍼 관리')

@section('content')
<div class="max-w-7xl mx-auto" x-data="scraperPage()">
    {{-- 페이지 헤더 --}}
    <x-page-header title="스크래퍼 관리" description="리뷰 수집 서비스 관리">
        <div class="flex items-center gap-2">
            @if($serviceStatus['online'])
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    서비스 정상
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                    <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                    서비스 오프라인
                </span>
            @endif
        </div>
    </x-page-header>

    {{-- 플래시 메시지 --}}
    <x-flash-messages />

    {{-- 통계 카드 --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-sm text-gray-500">전체 실행</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-sm text-gray-500">성공</div>
            <div class="text-2xl font-bold text-green-600 mt-1">{{ number_format($stats['success']) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-sm text-gray-500">실패</div>
            <div class="text-2xl font-bold text-red-600 mt-1">{{ number_format($stats['failed']) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="text-sm text-gray-500">실행중</div>
            <div class="text-2xl font-bold text-yellow-600 mt-1">{{ number_format($stats['running']) }}</div>
        </div>
    </div>

    {{-- 섹션 1: 수동 동기화 --}}
    <div class="bg-white rounded-xl shadow-sm p-5 lg:p-6 mb-6" x-data="syncManager()">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">수동 동기화</h2>

        @if(!$serviceStatus['online'])
            <p class="text-sm text-red-500 mb-4">Review Collector 서비스가 오프라인 상태입니다. 서비스를 먼저 시작해주세요.</p>
        @endif

        {{-- 플랫폼별 동기화 버튼 --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-4">
            @php
                $scraperPlatforms = [
                    'qoo10' => ['label' => 'Qoo10', 'icon' => 'Q', 'color' => 'red'],
                    'naver' => ['label' => '네이버', 'icon' => 'N', 'color' => 'green'],
                    'musinsa' => ['label' => '무신사', 'icon' => 'M', 'color' => 'gray'],
                    'shopee' => ['label' => 'Shopee', 'icon' => 'S', 'color' => 'orange'],
                ];
                $colorMap = [
                    'red' => 'bg-red-50 border-red-200 hover:bg-red-100 text-red-700',
                    'green' => 'bg-green-50 border-green-200 hover:bg-green-100 text-green-700',
                    'gray' => 'bg-gray-50 border-gray-200 hover:bg-gray-100 text-gray-700',
                    'orange' => 'bg-orange-50 border-orange-200 hover:bg-orange-100 text-orange-700',
                ];
            @endphp
            @foreach($scraperPlatforms as $key => $info)
                <form method="POST" action="{{ route('admin.scraper.sync') }}"
                      x-on:submit.prevent="submitSync('{{ $key }}', $event.target)">
                    @csrf
                    <input type="hidden" name="platform" value="{{ $key }}">
                    <button type="submit"
                            class="w-full flex items-center gap-2 px-4 py-3 rounded-lg border text-sm font-medium transition-all {{ $colorMap[$info['color']] }}"
                            :class="syncingPlatform === '{{ $key }}' && 'opacity-75 cursor-wait'"
                            :disabled="syncingPlatform !== null || !{{ $serviceStatus['online'] ? 'true' : 'false' }}">
                        <span class="w-7 h-7 rounded-full bg-white/80 flex items-center justify-center text-xs font-bold shrink-0"
                              :class="syncingPlatform === '{{ $key }}' && 'animate-spin'">
                            <template x-if="syncingPlatform === '{{ $key }}'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </template>
                            <template x-if="syncingPlatform !== '{{ $key }}'">
                                <span>{{ $info['icon'] }}</span>
                            </template>
                        </span>
                        <span x-text="syncingPlatform === '{{ $key }}' ? '동기화 중...' : '{{ $info['label'] }}'"></span>
                    </button>
                </form>
            @endforeach

            {{-- 전체 동기화 --}}
            <form method="POST" action="{{ route('admin.scraper.sync') }}"
                  x-on:submit.prevent="submitSync('all', $event.target)">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-2 px-4 py-3 rounded-lg border text-sm font-medium transition-all bg-blue-50 border-blue-200 hover:bg-blue-100 text-blue-700"
                        :class="syncingPlatform === 'all' && 'opacity-75 cursor-wait'"
                        :disabled="syncingPlatform !== null || !{{ $serviceStatus['online'] ? 'true' : 'false' }}">
                    <span class="w-7 h-7 rounded-full bg-white/80 flex items-center justify-center text-xs font-bold shrink-0"
                          :class="syncingPlatform === 'all' && 'animate-spin'">
                        <template x-if="syncingPlatform === 'all'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </template>
                        <template x-if="syncingPlatform !== 'all'">
                            <span>All</span>
                        </template>
                    </span>
                    <span x-text="syncingPlatform === 'all' ? '전체 동기화 중...' : '전체 동기화'"></span>
                </button>
            </form>
        </div>
    </div>

    {{-- 섹션 2: 리뷰 소스 관리 --}}
    <div class="bg-white rounded-xl shadow-sm p-5 lg:p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">리뷰 소스</h2>
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('admin.scraper.match-reviews') }}" class="inline">
                    @csrf
                    <x-button type="submit" variant="secondary" size="sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                        </svg>
                        일괄 매칭
                    </x-button>
                </form>
                <x-button variant="primary" size="sm" x-on:click="showAddSource = !showAddSource">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    소스 추가
                </x-button>
            </div>
        </div>

        {{-- 소스 추가 폼 (여러개 동시 등록) --}}
        <div x-show="showAddSource" x-cloak x-data="sourceForm()" class="border border-blue-200 bg-blue-50 rounded-lg p-4 mb-4">
            <form method="POST" action="{{ route('admin.scraper.store-source') }}">
                @csrf
                <template x-for="(row, index) in rows" :key="index">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_1fr_auto] gap-3 mb-3">
                        <div>
                            <template x-if="index === 0"><label class="block text-xs font-semibold text-gray-600 mb-1">플랫폼 *</label></template>
                            <select :name="'sources[' + index + '][platform]'" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">선택</option>
                                @foreach($platforms as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <template x-if="index === 0"><label class="block text-xs font-semibold text-gray-600 mb-1">연결 제품</label></template>
                            <select :name="'sources[' + index + '][product_id]'" class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">미지정</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <template x-if="index === 0"><label class="block text-xs font-semibold text-gray-600 mb-1">외부 URL</label></template>
                            <input type="url" :name="'sources[' + index + '][external_url]'" placeholder="https://..."
                                   class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <template x-if="index === 0"><label class="block text-xs font-semibold text-gray-600 mb-1">외부 ID</label></template>
                            <input type="text" :name="'sources[' + index + '][external_id]'" placeholder="플랫폼 상품코드"
                                   class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="flex items-end">
                            <template x-if="index === 0"><label class="block text-xs text-transparent mb-1">&nbsp;</label></template>
                            <button type="button" x-show="rows.length > 1" @click="removeRow(index)"
                                    class="p-2 text-red-400 hover:text-red-600 transition-colors" title="삭제">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
                <div class="flex items-center gap-3">
                    <x-button type="submit" variant="primary" size="sm">
                        <span x-text="rows.length > 1 ? rows.length + '개 일괄 등록' : '등록'"></span>
                    </x-button>
                    <button type="button" @click="addRow()"
                            class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-blue-700 hover:text-blue-800 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        행 추가
                    </button>
                    <x-button variant="secondary" size="sm" x-on:click.prevent="showAddSource = false">취소</x-button>
                </div>
            </form>
        </div>

        {{-- 소스 목록 --}}
        @if($sources->total() === 0)
            <div class="text-center py-8 text-gray-400">
                <svg class="w-10 h-10 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <p>등록된 리뷰 소스가 없습니다</p>
                <p class="text-sm mt-1">소스를 등록하면 수집된 리뷰가 자동으로 제품에 매칭됩니다 (선택사항)</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">플랫폼</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">제품</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">상태</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">리뷰수</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">평점</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">최종 동기화</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">관리</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($sources as $source)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ $source->platform_label }}
                                </span>
                                @if($source->external_id)
                                    <span class="text-xs text-gray-400 ml-1">{{ $source->external_id }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 hidden md:table-cell">
                                {{ $source->product?->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($source->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">활성</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">비활성</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-600 hidden md:table-cell">
                                {{ number_format($source->review_count ?? 0) }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-600 hidden lg:table-cell">
                                {{ $source->average_rating ? number_format($source->average_rating, 1) : '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 hidden lg:table-cell">
                                {{ $source->synced_at?->format('Y-m-d H:i') ?? '없음' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <form action="{{ route('admin.scraper.toggle-source', $source) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="{{ $source->is_active ? 'text-green-600 hover:text-green-700' : 'text-gray-400 hover:text-gray-600' }}" title="{{ $source->is_active ? '비활성화' : '활성화' }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                @if($source->is_active)
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                                @endif
                                            </svg>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.scraper.destroy-source', $source) }}" method="POST"
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
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- 소스 페이지네이션 --}}
            @if($sources->hasPages())
            <div class="mt-4">
                {{ $sources->links() }}
            </div>
            @endif
        @endif
    </div>

    {{-- 섹션 3: 쿠키 관리 --}}
    <div class="bg-white rounded-xl shadow-sm p-5 lg:p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">쿠키 관리</h2>
        <p class="text-sm text-gray-500 mb-4">플랫폼별 로그인 쿠키 파일을 관리합니다. 쿠키가 없으면 해당 플랫폼의 리뷰 수집이 불가능합니다.</p>

        @if(empty($cookies))
            <div class="text-center py-8 text-gray-400">
                <svg class="w-10 h-10 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <p>서비스에 연결할 수 없어 쿠키 상태를 확인할 수 없습니다.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($cookies as $cookie)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <h3 class="font-medium text-gray-900">{{ $cookie['platform_label'] }}</h3>
                                @if($cookie['exists'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">있음</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">없음</span>
                                @endif
                            </div>
                        </div>

                        @if($cookie['exists'])
                            <div class="text-xs text-gray-500 space-y-1 mb-3">
                                <p>파일 크기: {{ number_format($cookie['file_size'] ?? 0) }} bytes</p>
                                @if($cookie['modified_at'])
                                    <p>최종 수정: {{ \Carbon\Carbon::parse($cookie['modified_at'])->format('Y-m-d H:i') }}</p>
                                @endif
                            </div>
                        @endif

                        <div class="flex gap-2">
                            {{-- 업로드 폼 --}}
                            <form method="POST" action="{{ route('admin.scraper.upload-cookie', $cookie['platform']) }}"
                                  enctype="multipart/form-data" class="flex-1">
                                @csrf
                                <div class="flex gap-2">
                                    <input type="file" name="cookie_file" accept=".json"
                                           class="block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    <x-button type="submit" variant="primary" size="xs">
                                        업로드
                                    </x-button>
                                </div>
                            </form>

                            {{-- 삭제 버튼 --}}
                            @if($cookie['exists'])
                                <form method="POST" action="{{ route('admin.scraper.delete-cookie', $cookie['platform']) }}"
                                      onsubmit="return confirm('정말 삭제하시겠습니까? 삭제 후 해당 플랫폼의 리뷰 수집이 불가능합니다.')">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" variant="danger-outline" size="xs">
                                        삭제
                                    </x-button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- 섹션 3: 실행 기록 --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-5 lg:p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">실행 기록</h2>

            {{-- 필터 --}}
            <form method="GET" action="{{ route('admin.scraper.index') }}">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
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
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">상태</label>
                        <select name="status" class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">전체</option>
                            <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>성공</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>실패</option>
                            <option value="running" {{ request('status') == 'running' ? 'selected' : '' }}>실행중</option>
                        </select>
                    </div>

                    <div class="col-span-2 flex items-end gap-2">
                        <x-button type="submit" variant="primary" size="md" class="flex-1">
                            검색
                        </x-button>
                        <x-button :href="route('admin.scraper.index')" variant="secondary" size="md">
                            초기화
                        </x-button>
                    </div>
                </div>
            </form>
        </div>

        {{-- 테이블 --}}
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">플랫폼</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">트리거</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">상태</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">추가/업데이트</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">소요시간</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">실행일</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">에러</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($syncLogs as $log)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                {{ $log->platform_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm {{ $log->trigger_type === 'scheduled' ? 'text-purple-600' : 'text-blue-600' }}">
                                {{ $log->trigger_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @php
                                $statusColors = [
                                    'running' => 'bg-yellow-100 text-yellow-800',
                                    'success' => 'bg-green-100 text-green-800',
                                    'failed' => 'bg-red-100 text-red-800',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusColors[$log->status] ?? 'bg-gray-100 text-gray-800' }}">
                                @if($log->status === 'running')
                                    <svg class="w-3 h-3 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                @endif
                                {{ $log->status_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center text-sm text-gray-600 hidden md:table-cell">
                            @if($log->status === 'success')
                                <span class="text-green-600">+{{ $log->reviews_added }}</span>
                                /
                                <span class="text-blue-600">{{ $log->reviews_updated }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center text-sm text-gray-500 hidden lg:table-cell">
                            {{ $log->duration_formatted }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $log->started_at?->format('Y-m-d H:i:s') }}
                        </td>
                        <td class="px-6 py-4 hidden lg:table-cell">
                            @if($log->error_message)
                                <p class="text-xs text-red-500 max-w-xs truncate" title="{{ $log->error_message }}">
                                    {{ $log->error_message }}
                                </p>
                            @else
                                <span class="text-xs text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <p class="text-gray-500">실행 기록이 없습니다</p>
                            <p class="text-gray-400 text-sm mt-1">동기화를 실행하면 기록이 표시됩니다</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 페이지네이션 --}}
    @if($syncLogs->hasPages())
    <div class="mt-6">
        {{ $syncLogs->links() }}
    </div>
    @endif
</div>

@push('scripts')
<script>
function scraperPage() {
    return {
        syncing: false,
        showAddSource: false,
    }
}

function sourceForm() {
    return {
        rows: [{}],
        addRow() { this.rows.push({}); },
        removeRow(index) { this.rows.splice(index, 1); },
    }
}

function syncManager() {
    return {
        syncingPlatform: null,
        submitSync(platform, form) {
            this.syncingPlatform = platform;
            form.submit();
        }
    }
}
</script>
@endpush
@endsection
