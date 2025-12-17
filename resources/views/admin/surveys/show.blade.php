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
                <div class="flex justify-between py-3 border-b border-gray-100">
                    <span class="text-gray-500">효능 타입</span>
                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm font-medium">
                        {{ $efficacyLabels[$result->product->efficacy_type ?? 'moisture'] ?? '수분 공급' }}
                    </span>
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
                    <span class="text-gray-900">{{ $genderLabels[$result->profile->gender] ?? $result->profile->gender }}</span>
                </div>
                <div class="flex justify-between py-3 border-b border-gray-100">
                    <span class="text-gray-500">음주</span>
                    <span class="text-gray-900">{{ $alcoholLabels[$result->profile->alcohol] ?? $result->profile->alcohol ?? '-' }}</span>
                </div>
                <div class="flex justify-between py-3">
                    <span class="text-gray-500">흡연</span>
                    <span class="text-gray-900">{{ $smokingLabels[$result->profile->smoking] ?? $result->profile->smoking ?? '-' }}</span>
                </div>
            </div>
            @else
            <p class="text-gray-500 py-4">프로필 정보 없음</p>
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
                    <span class="text-gray-900">{{ $lifestyleLabels[$key]['values'][$value] ?? $value }}</span>
                </div>
                @endif
                @endforeach
            </div>
            @else
            <p class="text-gray-500 py-4">라이프스타일 정보 없음</p>
            @endif
        </div>

        <!-- 스킨케어 습관 -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                스킨케어 습관
            </h2>
            @if($result->profile && $result->profile->skincare_habit)
            <div class="space-y-4">
                <div class="flex justify-between py-3">
                    <span class="text-gray-500">케어 단계</span>
                    <span class="text-gray-900">{{ $careStepsLabels[$result->profile->skincare_habit['care_steps'] ?? ''] ?? '-' }}</span>
                </div>
            </div>
            @else
            <p class="text-gray-500 py-4">스킨케어 정보 없음</p>
            @endif
        </div>
    </div>

    <!-- 분석 결과 (새로운 단일 효능 구조) -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
            <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            분석 결과 (28일 예측)
        </h2>

        @if($result->metrics && isset($result->metrics['efficacy_type']))
        @php
            $metrics = $result->metrics;
            $efficacyType = $metrics['efficacy_type'] ?? 'moisture';
            $changePercent = $metrics['change_percent'] ?? 0;
        @endphp
        <div class="grid md:grid-cols-2 gap-6">
            <!-- 효능 요약 -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6">
                <div class="flex items-center gap-3 mb-4">
                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                        {{ $efficacyLabels[$efficacyType] ?? $efficacyType }}
                    </span>
                    <span class="text-green-600 font-bold text-lg">+{{ round($changePercent, 1) }}%</span>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">{{ $metrics['name'] ?? '' }}</h3>
                <p class="text-sm text-gray-600 mb-4">{{ $metrics['description'] ?? '' }}</p>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white rounded-lg p-4">
                        <p class="text-xs text-gray-500 mb-1">초기값</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $metrics['initial'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500">{{ $metrics['unit'] ?? '' }}</p>
                    </div>
                    <div class="bg-white rounded-lg p-4">
                        <p class="text-xs text-gray-500 mb-1">28일 후 예상</p>
                        <p class="text-2xl font-bold text-green-600">{{ $metrics['final'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500">{{ $metrics['unit'] ?? '' }}</p>
                    </div>
                </div>
            </div>

            <!-- 일자별 수치 -->
            <div class="bg-gray-50 rounded-xl p-6">
                <h3 class="font-semibold text-gray-900 mb-4">일자별 예측 수치</h3>
                @if(isset($metrics['daily']))
                <div class="space-y-3">
                    @foreach($metrics['daily'] as $day => $value)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">D{{ $day }}</span>
                        <div class="flex-1 mx-4">
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                @php
                                    $percent = isset($metrics['timeline_percent'][$day]) ? $metrics['timeline_percent'][$day] : 0;
                                @endphp
                                <div class="h-full bg-blue-500 rounded-full" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                        <span class="text-sm font-medium text-gray-900">{{ $value }} {{ $metrics['unit'] ?? '' }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @else
        <p class="text-gray-500 py-4">분석 결과 없음</p>
        @endif
    </div>

    <!-- 라이프스타일 영향 요인 -->
    @if($result->lifestyle_factors && count($result->lifestyle_factors) > 0)
    <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
            <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            라이프스타일 영향 요인
        </h2>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($result->lifestyle_factors as $key => $factor)
            <div class="p-4 rounded-xl {{ $factor['status'] === 'positive' ? 'bg-green-50 border border-green-200' : ($factor['status'] === 'negative' ? 'bg-red-50 border border-red-200' : 'bg-gray-50 border border-gray-200') }}">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-medium text-gray-900">{{ $factor['name'] ?? $key }}</span>
                    <span class="text-sm font-bold {{ $factor['impact'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $factor['impact'] >= 0 ? '+' : '' }}{{ $factor['impact'] }}%
                    </span>
                </div>
                <p class="text-sm text-gray-600">modifier: {{ $factor['modifier'] ?? 1.0 }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- 마일스톤 -->
    @if($result->milestones && count($result->milestones) > 0)
    <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
            <div class="w-8 h-8 bg-cyan-100 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                </svg>
            </div>
            예상 마일스톤
        </h2>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($result->milestones as $milestone)
            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl">
                <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                    <span class="text-sm font-bold text-blue-600">D{{ $milestone['day'] }}</span>
                </div>
                <div>
                    <p class="font-medium text-gray-900">{{ $milestone['title'] ?? '' }}</p>
                    <p class="text-sm text-gray-500">{{ $milestone['value'] ?? 0 }}% 달성</p>
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
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Metrics</h4>
                    <pre class="bg-gray-50 rounded-xl p-4 text-xs overflow-x-auto border border-gray-200">{{ json_encode($result->metrics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Timeline</h4>
                    <pre class="bg-gray-50 rounded-xl p-4 text-xs overflow-x-auto border border-gray-200">{{ json_encode($result->timeline, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Lifestyle Factors</h4>
                    <pre class="bg-gray-50 rounded-xl p-4 text-xs overflow-x-auto border border-gray-200">{{ json_encode($result->lifestyle_factors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Skin Profile</h4>
                    <pre class="bg-gray-50 rounded-xl p-4 text-xs overflow-x-auto border border-gray-200">{{ json_encode($result->skin_profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        </details>
    </div>
</div>
@endsection
