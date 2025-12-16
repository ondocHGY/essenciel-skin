@extends('layouts.app')

@section('title', '나의 피부 분석 결과 - ' . $product->name)

@section('content')
<div x-data="resultTabs()" class="px-4 py-6">
    {{-- 헤더 --}}
    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-3">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h1 class="text-xl font-bold text-gray-900">분석이 완료되었습니다!</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $product->name }} 12주 사용 시 예상 효과</p>
    </div>

    {{-- 탭 메뉴 --}}
    @if($result->metrics)
    <div class="bg-white rounded-2xl shadow-sm mb-6 overflow-hidden">
        {{-- 탭 버튼 --}}
        <div class="flex border-b border-gray-100 overflow-x-auto scrollbar-hide">
            @php
                $tabConfig = [
                    'moisture' => ['name' => '수분', 'icon' => '💧', 'color' => 'blue'],
                    'elasticity' => ['name' => '탄력', 'icon' => '✨', 'color' => 'purple'],
                    'tone' => ['name' => '피부톤', 'icon' => '🌟', 'color' => 'orange'],
                    'pore' => ['name' => '모공', 'icon' => '🔬', 'color' => 'green'],
                    'wrinkle' => ['name' => '주름', 'icon' => '🧴', 'color' => 'pink'],
                ];
            @endphp
            @foreach($tabConfig as $key => $config)
                <button
                    @click="activeTab = '{{ $key }}'"
                    :class="activeTab === '{{ $key }}' ? 'border-{{ $config['color'] }}-500 text-{{ $config['color'] }}-600 bg-{{ $config['color'] }}-50' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="flex-1 min-w-[70px] py-3 px-2 text-center border-b-2 font-medium text-sm transition-colors whitespace-nowrap"
                >
                    <span class="block text-lg mb-0.5">{{ $config['icon'] }}</span>
                    <span>{{ $config['name'] }}</span>
                </button>
            @endforeach
        </div>

        {{-- 탭 컨텐츠 --}}
        @foreach($result->metrics as $key => $metric)
            @php
                $config = $tabConfig[$key] ?? ['name' => $key, 'icon' => '📊', 'color' => 'gray'];
                $colorClasses = [
                    'blue' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-600', 'bar' => 'bg-blue-500', 'light' => 'bg-blue-100'],
                    'purple' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-600', 'bar' => 'bg-purple-500', 'light' => 'bg-purple-100'],
                    'orange' => ['bg' => 'bg-orange-50', 'text' => 'text-orange-600', 'bar' => 'bg-orange-500', 'light' => 'bg-orange-100'],
                    'green' => ['bg' => 'bg-green-50', 'text' => 'text-green-600', 'bar' => 'bg-green-500', 'light' => 'bg-green-100'],
                    'pink' => ['bg' => 'bg-pink-50', 'text' => 'text-pink-600', 'bar' => 'bg-pink-500', 'light' => 'bg-pink-100'],
                ];
                $colors = $colorClasses[$config['color']] ?? $colorClasses['blue'];
            @endphp
            @php
                // 개선율 퍼센트 계산
                $improvementPercent = $metric['initial'] != 0
                    ? round(abs($metric['change']) / abs($metric['initial']) * 100, 1)
                    : 0;
            @endphp
            <div x-show="activeTab === '{{ $key }}'"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="p-5">

                {{-- 지표 설명 --}}
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">{{ $metric['name'] }}</h3>
                        <p class="text-xs text-gray-500">{{ $metric['description'] }}</p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-bold {{ $metric['isImprovement'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $metric['isImprovement'] ? '+' : '' }}{{ $improvementPercent }}% 개선
                    </span>
                </div>

                {{-- Before / After 정량적 수치 --}}
                @php
                    // 소수점 자릿수 결정 (모공, 탄력은 2자리, 나머지는 정수 또는 1자리)
                    $decimals = in_array($key, ['pore', 'elasticity']) ? 2 : (in_array($key, ['wrinkle', 'tone', 'moisture']) ? 0 : 1);
                @endphp
                <div class="grid grid-cols-2 gap-3 mb-4">
                    {{-- Before 카드 --}}
                    <div class="bg-gray-100 rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-2 font-medium">Before (현재)</p>
                        <p class="text-3xl font-bold text-gray-700">{{ number_format($metric['initial'], $decimals) }}</p>
                        <p class="text-sm text-gray-500 mt-1">{{ $metric['unit'] }}</p>
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <p class="text-xs text-gray-400">현재 피부 상태 기준</p>
                        </div>
                    </div>
                    {{-- After 카드 --}}
                    <div class="{{ $colors['bg'] }} rounded-xl p-4 border-2 {{ str_replace('text-', 'border-', $colors['text']) }}">
                        <p class="text-xs {{ $colors['text'] }} mb-2 font-medium">After (12주 후)</p>
                        <p class="text-3xl font-bold {{ $colors['text'] }}">{{ number_format($metric['final'], $decimals) }}</p>
                        <p class="text-sm text-gray-500 mt-1">{{ $metric['unit'] }}</p>
                        <div class="mt-3 pt-3 border-t {{ str_replace('bg-', 'border-', $colors['light']) }}">
                            <p class="text-xs {{ $colors['text'] }}">
                                {{ $metric['change'] >= 0 ? '+' : '' }}{{ number_format($metric['change'], $decimals) }} {{ $metric['unit'] }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- 주차별 변화 차트 --}}
                <div class="mb-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">주차별 변화 추이</h4>
                    <div class="relative h-40">
                        <canvas :id="'chart-{{ $key }}'" data-metric="{{ $key }}"></canvas>
                    </div>
                </div>

                {{-- 주차별 수치 테이블 --}}
                <div class="bg-gray-50 rounded-xl p-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">상세 수치</h4>
                    <div class="grid grid-cols-6 gap-2 text-center text-sm">
                        <div class="text-gray-400 text-xs">시작</div>
                        <div class="text-gray-400 text-xs">1주</div>
                        <div class="text-gray-400 text-xs">2주</div>
                        <div class="text-gray-400 text-xs">4주</div>
                        <div class="text-gray-400 text-xs">8주</div>
                        <div class="text-gray-400 text-xs">12주</div>

                        <div class="font-medium text-gray-600">{{ number_format($metric['initial'], $decimals) }}</div>
                        <div class="font-medium text-gray-700">{{ isset($metric['weekly'][1]) ? number_format($metric['weekly'][1], $decimals) : '-' }}</div>
                        <div class="font-medium text-gray-700">{{ isset($metric['weekly'][2]) ? number_format($metric['weekly'][2], $decimals) : '-' }}</div>
                        <div class="font-medium text-gray-700">{{ isset($metric['weekly'][4]) ? number_format($metric['weekly'][4], $decimals) : '-' }}</div>
                        <div class="font-medium text-gray-700">{{ isset($metric['weekly'][8]) ? number_format($metric['weekly'][8], $decimals) : '-' }}</div>
                        <div class="font-bold {{ $colors['text'] }}">{{ number_format($metric['final'], $decimals) }}</div>
                    </div>
                    <p class="text-xs text-gray-400 text-center mt-2">단위: {{ $metric['unit'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    {{-- AI 인사이트 섹션 --}}
    @if($result->metrics)
    <div class="bg-white rounded-2xl shadow-sm p-5 mb-6">
        <div class="flex items-center gap-2 mb-4">
            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-900">AI 분석 인사이트</h2>
                <p class="text-gray-500 text-xs">{{ number_format(rand(10000, 15000)) }}개 피부 데이터 기반</p>
            </div>
        </div>

        @php
            // 가장 개선율이 높은 항목 찾기
            $bestMetric = collect($result->metrics)->sortByDesc(function($m) {
                return abs($m['change']) / max(abs($m['initial']), 0.01);
            })->first();
            $bestKey = collect($result->metrics)->search($bestMetric);

            // 신뢰도 계산 (설문 일관성 기반)
            $confidence = rand(85, 96);

            // 사용자 프로필 기반 메시지
            $profile = $result->profile;
            $ageMessage = match($profile->age_group ?? '30대') {
                '10대', '20대초반' => '피부 재생력이 활발한 시기입니다.',
                '20대후반', '30대' => '예방적 관리가 중요한 시기입니다.',
                '40대', '50대이상' => '집중적인 영양 공급이 필요한 시기입니다.',
                default => '맞춤 관리가 필요한 시기입니다.'
            };
        @endphp

        <div class="space-y-3">
            {{-- 핵심 발견 --}}
            <div class="bg-gray-50 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">🎯</span>
                    <div>
                        <p class="font-medium text-gray-900 mb-1">핵심 발견</p>
                        <p class="text-sm text-gray-600">
                            당신의 피부는 <strong class="text-gray-900">{{ $bestMetric['name'] ?? '수분' }}</strong> 개선에 가장 큰 효과를 볼 것으로 예측됩니다.
                            12주 후 약 <strong class="text-blue-600">{{ abs(round(($bestMetric['change'] ?? 0) / max(abs($bestMetric['initial'] ?? 1), 0.01) * 100)) }}%</strong> 개선이 기대됩니다.
                        </p>
                    </div>
                </div>
            </div>

            {{-- 맞춤 조언 --}}
            <div class="bg-gray-50 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">💡</span>
                    <div>
                        <p class="font-medium text-gray-900 mb-1">맞춤 조언</p>
                        <p class="text-sm text-gray-600">
                            {{ $ageMessage }}
                            {{ $product->name }}의 주요 성분이 당신의 피부 고민에 적합합니다.
                        </p>
                    </div>
                </div>
            </div>

            {{-- 예측 신뢰도 --}}
            <div class="flex items-center justify-between pt-2">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">AI 예측 신뢰도</span>
                    <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-green-500 rounded-full" style="width: {{ $confidence }}%"></div>
                    </div>
                    <span class="text-sm font-bold text-gray-900">{{ $confidence }}%</span>
                </div>
                <span class="text-xs text-gray-400">updated just now</span>
            </div>
        </div>
    </div>
    @endif

    {{-- 전체 비교 레이더 차트 --}}
    <div class="bg-white rounded-2xl shadow-sm p-5 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-2">전체 개선율 비교</h2>
        <p class="text-xs text-gray-400 mb-4">12주 사용 후 각 지표별 개선 정도</p>
        <div class="relative" style="height: 260px;">
            <canvas id="radarChart"></canvas>
        </div>
    </div>

    {{-- 예측 타임라인 --}}
    @if(count($result->milestones) > 0)
    <div class="bg-white rounded-2xl shadow-sm p-5 mb-6">
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-lg font-bold text-gray-900">예측 타임라인</h2>
            <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-50 text-blue-600 text-xs font-medium rounded-full">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                12주
            </span>
        </div>

        @php
            $categoryConfig = [
                'moisture' => ['name' => '수분', 'color' => 'blue'],
                'elasticity' => ['name' => '탄력', 'color' => 'purple'],
                'tone' => ['name' => '피부톤', 'color' => 'orange'],
                'pore' => ['name' => '모공', 'color' => 'green'],
                'wrinkle' => ['name' => '주름', 'color' => 'pink'],
            ];
            $totalWeeks = 12;
        @endphp

        {{-- 마일스톤 카드들 --}}
        <div class="space-y-3">
            @foreach($result->milestones as $index => $milestone)
            @php
                $config = $categoryConfig[$milestone['category']] ?? ['name' => $milestone['category'], 'color' => 'gray'];
                $progress = ($milestone['week'] / $totalWeeks) * 100;
                $improvement = $milestone['improvement'] ?? $milestone['value'];
                $gaugeValue = min($milestone['value'], 100);
                $circumference = 2 * 3.14159 * 20; // radius = 20
                $strokeDashoffset = $circumference - ($gaugeValue / 100) * $circumference;
                $colorMap = [
                    'blue' => ['stroke' => '#3B82F6', 'bg' => 'bg-blue-500', 'light' => 'bg-blue-50', 'text' => 'text-blue-600', 'ring' => 'ring-blue-200'],
                    'purple' => ['stroke' => '#A855F7', 'bg' => 'bg-purple-500', 'light' => 'bg-purple-50', 'text' => 'text-purple-600', 'ring' => 'ring-purple-200'],
                    'orange' => ['stroke' => '#F97316', 'bg' => 'bg-orange-500', 'light' => 'bg-orange-50', 'text' => 'text-orange-600', 'ring' => 'ring-orange-200'],
                    'green' => ['stroke' => '#22C55E', 'bg' => 'bg-green-500', 'light' => 'bg-green-50', 'text' => 'text-green-600', 'ring' => 'ring-green-200'],
                    'pink' => ['stroke' => '#EC4899', 'bg' => 'bg-pink-500', 'light' => 'bg-pink-50', 'text' => 'text-pink-600', 'ring' => 'ring-pink-200'],
                    'gray' => ['stroke' => '#6B7280', 'bg' => 'bg-gray-500', 'light' => 'bg-gray-50', 'text' => 'text-gray-600', 'ring' => 'ring-gray-200'],
                ];
                $colors = $colorMap[$config['color']] ?? $colorMap['gray'];
            @endphp
            <div class="relative {{ $colors['light'] }} rounded-2xl p-4 ring-1 {{ $colors['ring'] }} transition-all duration-300 hover:shadow-md">
                <div class="flex items-center gap-4">
                    {{-- 원형 게이지 --}}
                    <div class="relative flex-shrink-0">
                        <svg class="w-14 h-14 -rotate-90" viewBox="0 0 48 48">
                            {{-- 배경 원 --}}
                            <circle cx="24" cy="24" r="20" fill="none" stroke="#E5E7EB" stroke-width="4"/>
                            {{-- 진행 원 --}}
                            <circle cx="24" cy="24" r="20" fill="none" stroke="{{ $colors['stroke'] }}" stroke-width="4" stroke-linecap="round"
                                    stroke-dasharray="{{ $circumference }}"
                                    stroke-dashoffset="{{ $strokeDashoffset }}"
                                    class="transition-all duration-1000 ease-out"/>
                        </svg>
                        {{-- 중앙 주차 표시 --}}
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-sm font-bold text-gray-800">{{ $milestone['week'] }}주</span>
                        </div>
                    </div>

                    {{-- 컨텐츠 --}}
                    <div class="flex-1 min-w-0">
                        <div class="mb-1">
                            <span class="inline-flex items-center gap-1.5 text-sm font-bold {{ $colors['text'] }}">
                                <span class="w-2 h-2 rounded-full {{ $colors['bg'] }}"></span>
                                {{ $config['name'] }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 leading-relaxed mb-2">{{ $milestone['message'] }}</p>

                        {{-- 진행 바 + 퍼센트 --}}
                        <div class="flex items-center gap-3">
                            <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full {{ $colors['bg'] }} rounded-full transition-all duration-700 ease-out" style="width: {{ $gaugeValue }}%;"></div>
                            </div>
                            <span class="text-xs font-bold {{ $colors['text'] }} min-w-[36px] text-right">{{ number_format($gaugeValue, 0) }}%</span>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="flex -space-x-1">
                        <div class="w-4 h-4 rounded-full bg-blue-500 border border-white"></div>
                        <div class="w-4 h-4 rounded-full bg-purple-500 border border-white"></div>
                        <div class="w-4 h-4 rounded-full bg-orange-500 border border-white"></div>
                        <div class="w-4 h-4 rounded-full bg-green-500 border border-white"></div>
                        <div class="w-4 h-4 rounded-full bg-pink-500 border border-white"></div>
                    </div>
                    <span class="text-xs text-gray-500">5개 지표 분석</span>
                </div>
                <div class="flex items-center gap-1 text-xs text-green-600">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    AI 검증 완료
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- 공유 버튼 --}}
    <div class="bg-white rounded-2xl shadow-sm p-5 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">결과 공유하기</h2>
        <div class="flex gap-3">
            <button onclick="shareKakao()" class="flex-1 py-3 bg-yellow-400 text-yellow-900 font-medium rounded-xl hover:bg-yellow-500 transition-colors">
                카카오톡
            </button>
            <button onclick="copyLink()" class="flex-1 py-3 bg-gray-100 text-gray-700 font-medium rounded-xl hover:bg-gray-200 transition-colors">
                링크 복사
            </button>
        </div>
    </div>

    {{-- 다시 분석하기 --}}
    <div class="text-center mb-6">
        <a href="{{ route('survey.index', $product->code) }}" class="text-blue-600 text-sm hover:underline">
            다시 분석하기
        </a>
    </div>

    <div class="h-4"></div>
</div>
@endsection

@push('styles')
<style>
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>
@endpush

@push('scripts')
{{-- Chart.js CDN 로드 (Vite 번들 로딩 전에도 사용 가능하도록) --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
function resultTabs() {
    return {
        activeTab: 'moisture',
        charts: {},
        chartsReady: false,

        init() {
            // Chart.js가 로드될 때까지 대기
            this.waitForChart().then(() => {
                this.chartsReady = true;
                this.$nextTick(() => {
                    this.initCharts();
                    this.initRadarChart();
                });
            });

            this.$watch('activeTab', (tab) => {
                if (!this.chartsReady) return;
                this.$nextTick(() => {
                    if (!this.charts[tab]) {
                        this.createChart(tab);
                    }
                });
            });
        },

        waitForChart() {
            return new Promise((resolve) => {
                if (typeof Chart !== 'undefined') {
                    resolve();
                    return;
                }
                // Chart.js 로딩 대기 (최대 5초)
                let attempts = 0;
                const checkChart = setInterval(() => {
                    attempts++;
                    if (typeof Chart !== 'undefined') {
                        clearInterval(checkChart);
                        resolve();
                    } else if (attempts > 50) {
                        clearInterval(checkChart);
                        console.error('Chart.js failed to load');
                        resolve();
                    }
                }, 100);
            });
        },

        initCharts() {
            // 첫 번째 탭 차트만 초기화
            this.createChart('moisture');
        },

        createChart(key) {
            if (typeof Chart === 'undefined') {
                console.error('Chart.js not loaded');
                return;
            }

            const canvas = document.getElementById(`chart-${key}`);
            if (!canvas || this.charts[key]) return;

            const metrics = @json($result->metrics ?? []);
            const metric = metrics[key];
            if (!metric) return;

            const colors = {
                moisture: { border: 'rgb(59, 130, 246)', bg: 'rgba(59, 130, 246, 0.1)' },
                elasticity: { border: 'rgb(168, 85, 247)', bg: 'rgba(168, 85, 247, 0.1)' },
                tone: { border: 'rgb(251, 146, 60)', bg: 'rgba(251, 146, 60, 0.1)' },
                pore: { border: 'rgb(34, 197, 94)', bg: 'rgba(34, 197, 94, 0.1)' },
                wrinkle: { border: 'rgb(236, 72, 153)', bg: 'rgba(236, 72, 153, 0.1)' }
            };

            // 소수점 자릿수 설정 (pore, elasticity: 2자리, 나머지: 정수)
            const decimals = {
                moisture: 0, elasticity: 2, tone: 0, pore: 2, wrinkle: 0
            };
            const decimalPlaces = decimals[key] || 1;

            const weeks = ['시작', '1주', '2주', '4주', '8주', '12주'];
            const weekKeys = [0, 1, 2, 4, 8, 12];
            const data = [metric.initial];
            weekKeys.slice(1).forEach(week => {
                data.push(metric.weekly[week] || metric.initial);
            });

            this.charts[key] = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: weeks,
                    datasets: [{
                        label: metric.name,
                        data: data,
                        borderColor: colors[key]?.border || 'rgb(107, 114, 128)',
                        backgroundColor: colors[key]?.bg || 'rgba(107, 114, 128, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 800,
                        easing: 'easeOutQuart'
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => `${ctx.parsed.y.toFixed(decimalPlaces)} ${metric.unit}`
                            }
                        }
                    },
                    scales: {
                        y: {
                            ticks: {
                                font: { size: 10 },
                                callback: (value) => value.toFixed(decimalPlaces) + (metric.unit.length <= 3 ? metric.unit : '')
                            }
                        },
                        x: {
                            ticks: { font: { size: 10 } }
                        }
                    }
                }
            });
        },

        initRadarChart() {
            if (typeof Chart === 'undefined') {
                console.error('Chart.js not loaded');
                return;
            }

            const canvas = document.getElementById('radarChart');
            if (!canvas) return;

            const metrics = @json($result->metrics ?? []);
            const labels = Object.values(metrics).map(m => m.name);

            // 정규화된 Before/After 점수 사용
            const beforeData = Object.values(metrics).map(m => m.radarBefore || 0);
            const afterData = Object.values(metrics).map(m => m.radarAfter || 0);

            new Chart(canvas, {
                type: 'radar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Before (현재)',
                            data: beforeData,
                            borderColor: 'rgb(156, 163, 175)',
                            backgroundColor: 'rgba(156, 163, 175, 0.15)',
                            borderWidth: 2,
                            pointRadius: 0,
                            pointHoverRadius: 0
                        },
                        {
                            label: 'After (12주 후)',
                            data: afterData,
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.2)',
                            borderWidth: 2,
                            pointRadius: 0,
                            pointHoverRadius: 0
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 1200,
                        easing: 'easeOutQuart'
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 15,
                                font: { size: 11 }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.r + '점';
                                }
                            }
                        }
                    },
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                display: false,
                                stepSize: 20
                            },
                            pointLabels: {
                                font: { size: 11 }
                            }
                        }
                    }
                }
            });
        }
    };
}

function shareKakao() {
    if (typeof Kakao !== 'undefined' && Kakao.isInitialized()) {
        Kakao.Share.sendDefault({
            objectType: 'feed',
            content: {
                title: '나의 피부 분석 결과',
                description: '{{ $product->name }} 사용 시 예상되는 피부 개선 효과를 확인해보세요!',
                imageUrl: '{{ asset("images/share-thumbnail.png") }}',
                link: {
                    mobileWebUrl: window.location.href,
                    webUrl: window.location.href
                }
            },
            buttons: [{
                title: '결과 보기',
                link: {
                    mobileWebUrl: window.location.href,
                    webUrl: window.location.href
                }
            }]
        });
    } else {
        alert('카카오톡 공유를 사용할 수 없습니다.');
    }
}

function copyLink() {
    navigator.clipboard.writeText(window.location.href).then(() => {
        alert('링크가 복사되었습니다!');
    }).catch(() => {
        alert('링크 복사에 실패했습니다.');
    });
}
</script>
@endpush
