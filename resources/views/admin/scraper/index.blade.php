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
    <div class="bg-white rounded-xl shadow-sm p-5 lg:p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">수동 동기화</h2>
        <form method="POST" action="{{ route('admin.scraper.sync') }}" x-ref="syncForm">
            @csrf
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">플랫폼 (선택)</label>
                    <select name="platform" class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">전체 (모든 활성 소스)</option>
                        @foreach($platforms as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <x-button type="submit" variant="primary" size="md"
                              x-on:click="syncing = true"
                              x-bind:disabled="syncing || !{{ $serviceStatus['online'] ? 'true' : 'false' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                             :class="syncing && 'animate-spin'">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span x-text="syncing ? '동기화 중...' : '동기화 실행'"></span>
                    </x-button>
                </div>
            </div>
            @if(!$serviceStatus['online'])
                <p class="text-sm text-red-500 mt-2">Review Collector 서비스가 오프라인 상태입니다. 서비스를 먼저 시작해주세요.</p>
            @endif
        </form>
    </div>

    {{-- 섹션 2: 쿠키 관리 --}}
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
    }
}
</script>
@endpush
@endsection
