@extends('layouts.admin')

@section('title', '설문 결과 상세')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- 페이지 헤더 -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.surveys.index') }}" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">설문 결과 #{{ $result->id }}</h1>
                <p class="text-gray-600 mt-1">{{ $result->created_at->format('Y년 m월 d일 H:i:s') }}</p>
            </div>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('result.show', $result->product?->code) }}" target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 text-gray-600 bg-white border border-gray-300 font-medium rounded-lg hover:bg-gray-50 transition-colors text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
                결과 페이지
            </a>
            <form action="{{ route('admin.surveys.destroy', $result) }}" method="POST"
                  onsubmit="return confirm('정말 삭제하시겠습니까?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    삭제
                </button>
            </form>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6 lg:gap-8 mb-8">
        <!-- 제품 정보 -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                제품 정보
            </h2>
            @if($result->product)
            <div class="space-y-4">
                <div class="flex justify-between py-3 border-b border-gray-100">
                    <span class="text-gray-500">제품명</span>
                    <span class="font-medium text-gray-900">{{ $result->product->name }}</span>
                </div>
                <div class="flex justify-between py-3 border-b border-gray-100">
                    <span class="text-gray-500">브랜드</span>
                    <span class="text-gray-900">{{ $result->product->brand }}</span>
                </div>
                <div class="flex justify-between py-3 border-b border-gray-100">
                    <span class="text-gray-500">카테고리</span>
                    <span class="text-gray-900">{{ $result->product->category }}</span>
                </div>
                <div class="flex justify-between py-3">
                    <span class="text-gray-500">제품 코드</span>
                    <span class="font-mono bg-gray-100 px-2 py-1 rounded text-sm">{{ $result->product->code }}</span>
                </div>
            </div>
            @else
            <p class="text-gray-500 py-4">제품 정보 없음</p>
            @endif
        </div>

        <!-- 사용자 프로필 -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                사용자 프로필
            </h2>
            @if($result->profile)
            <div class="space-y-4">
                <div class="flex justify-between py-3 border-b border-gray-100">
                    <span class="text-gray-500">연령대</span>
                    <span class="font-medium text-gray-900">{{ $result->profile->age_group }}</span>
                </div>
                <div class="flex justify-between py-3 border-b border-gray-100">
                    <span class="text-gray-500">성별</span>
                    <span class="text-gray-900">{{ $result->profile->gender }}</span>
                </div>
                <div class="flex justify-between py-3 border-b border-gray-100">
                    <span class="text-gray-500">피부타입</span>
                    <span class="text-gray-900">{{ $result->profile->skin_type }}</span>
                </div>
                <div class="flex justify-between py-3">
                    <span class="text-gray-500">만족도</span>
                    <span class="text-gray-900">{{ $result->profile->satisfaction }}/10</span>
                </div>
            </div>
            @else
            <p class="text-gray-500 py-4">프로필 정보 없음</p>
            @endif
        </div>

        <!-- 피부 고민 -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                피부 고민
            </h2>
            @if($result->profile && $result->profile->concerns)
            <div class="flex flex-wrap gap-2">
                @foreach($result->profile->concerns as $concern)
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-orange-100 text-orange-800">
                    {{ $concernLabels[$concern] ?? $concern }}
                </span>
                @endforeach
            </div>
            @else
            <p class="text-gray-500 py-4">고민 정보 없음</p>
            @endif
        </div>

        <!-- 라이프스타일 -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                </div>
                라이프스타일
            </h2>
            @if($result->profile && $result->profile->lifestyle)
            <div class="space-y-4">
                @foreach($result->profile->lifestyle as $key => $value)
                @if(isset($lifestyleLabels[$key]))
                <div class="flex justify-between py-3 border-b border-gray-100 last:border-0">
                    <span class="text-gray-500">{{ $lifestyleLabels[$key]['label'] }}</span>
                    <span class="text-gray-900">{{ $lifestyleLabels[$key][$value] ?? $value }}</span>
                </div>
                @endif
                @endforeach
            </div>
            @else
            <p class="text-gray-500 py-4">라이프스타일 정보 없음</p>
            @endif
        </div>
    </div>

    <!-- 분석 결과 -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
            <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            분석 결과 (12주 예상)
        </h2>

        @if($result->metrics)
        <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-4">
            @foreach($result->metrics as $key => $metric)
            @php
                $improvement = $metric['isImprovement'] ?? false;
                $changePercent = $metric['initial'] != 0
                    ? abs(($metric['final'] - $metric['initial']) / $metric['initial'] * 100)
                    : 0;
            @endphp
            <div class="bg-gray-50 rounded-xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-900">{{ $labels[$key] ?? $key }}</h3>
                    @if($improvement)
                    <span class="text-xs font-bold text-green-600 bg-green-100 px-2 py-1 rounded-full">+{{ round($changePercent, 1) }}%</span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 mb-4">{{ $metric['name'] ?? '' }}</p>

                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">초기</span>
                        <span class="font-mono font-medium text-gray-900">{{ $metric['initial'] }} {{ $metric['unit'] }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">예상 최종</span>
                        <span class="font-mono font-medium {{ $improvement ? 'text-green-600' : 'text-gray-900' }}">
                            {{ $metric['final'] }} {{ $metric['unit'] }}
                        </span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">변화량</span>
                        <span class="font-mono font-medium {{ $improvement ? 'text-green-600' : 'text-red-600' }}">
                            {{ $metric['changeText'] ?? '' }}
                        </span>
                    </div>
                </div>

                <!-- 레이더 점수 바 -->
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="flex justify-between text-xs text-gray-500 mb-2">
                        <span>점수</span>
                        <span class="font-medium">{{ $metric['radarBefore'] ?? 0 }} → {{ $metric['radarAfter'] ?? 0 }}</span>
                    </div>
                    <div class="relative h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="absolute h-full bg-gray-400 rounded-full"
                             style="width: {{ $metric['radarBefore'] ?? 0 }}%"></div>
                        <div class="absolute h-full bg-blue-500 rounded-full"
                             style="width: {{ $metric['radarAfter'] ?? 0 }}%"></div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-gray-500 py-4">분석 결과 없음</p>
        @endif
    </div>

    <!-- 타임라인 데이터 -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
            <div class="w-8 h-8 bg-cyan-100 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                </svg>
            </div>
            주차별 개선율 (%)
        </h2>

        @if($result->timeline)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">지표</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">1주</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">2주</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">4주</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">8주</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">12주</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($result->timeline as $key => $weeks)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $labels[$key] ?? $key }}</td>
                        @foreach([1, 2, 4, 8, 12] as $week)
                        <td class="px-6 py-4 text-center text-sm text-gray-600">{{ $weeks[$week] ?? '-' }}%</td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-gray-500 py-4">타임라인 데이터 없음</p>
        @endif
    </div>

    <!-- 마일스톤 -->
    @if($result->milestones && count($result->milestones) > 0)
    <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
            <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                </svg>
            </div>
            예상 마일스톤
        </h2>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($result->milestones as $milestone)
            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl">
                <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                    <span class="text-sm font-bold text-blue-600">{{ $milestone['week'] }}주</span>
                </div>
                <div>
                    <p class="font-medium text-gray-900">{{ $milestone['message'] }}</p>
                    <p class="text-sm text-gray-500">
                        {{ $labels[$milestone['category']] ?? $milestone['category'] }}
                        {{ $milestone['value'] }}% 달성
                    </p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Raw 데이터 (개발자용) -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <details class="group">
            <summary class="cursor-pointer text-sm font-medium text-gray-500 hover:text-gray-700 flex items-center gap-2">
                <svg class="w-4 h-4 transform group-open:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                Raw JSON 데이터 보기 (개발자용)
            </summary>
            <div class="mt-6 space-y-6">
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Timeline</h4>
                    <pre class="bg-gray-50 rounded-xl p-4 text-xs overflow-x-auto border border-gray-200">{{ json_encode($result->timeline, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Metrics</h4>
                    <pre class="bg-gray-50 rounded-xl p-4 text-xs overflow-x-auto border border-gray-200">{{ json_encode($result->metrics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Comparison</h4>
                    <pre class="bg-gray-50 rounded-xl p-4 text-xs overflow-x-auto border border-gray-200">{{ json_encode($result->comparison, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        </details>
    </div>
</div>
@endsection
