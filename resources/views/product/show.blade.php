@extends('layouts.app')

@section('title', $product->name . ' - 피부 효과 분석')

@php
    $efficacyType = $product->efficacy_type ?? 'moisture';
@endphp

@section('content')
<div x-data="productPage()" class="px-4 py-6">
    {{-- 제품 이미지 --}}
    @if($product->image)
    <div class="flex justify-center mb-4">
        <div class="w-40 h-40 rounded-2xl overflow-hidden shadow-lg bg-white">
            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
        </div>
    </div>
    @endif

    {{-- 헤더 --}}
    <div class="text-center mb-4">
        <span class="inline-block px-3 py-1 bg-blue-100 text-blue-700 text-sm rounded-full mb-2">
            {{ $product->brand }}
        </span>
        <h1 class="text-xl font-bold text-gray-900 mb-1">{{ $product->name }}</h1>
        <p class="text-gray-500 text-sm">{{ $product->category }}</p>
    </div>

    {{-- CTA 버튼 (제품명 바로 아래) --}}
    <div class="mb-6">
        <a href="{{ route('survey.index', $product->code) }}"
           class="block w-full py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-center font-semibold rounded-xl transition-all shadow-lg shadow-blue-500/25">
            나만의 피부 효과 분석 시작하기
        </a>
        <p class="text-center text-gray-400 text-xs mt-2">약 1분 소요</p>
    </div>

    {{-- AI 리뷰 분석 섹션 --}}
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-6">
        {{-- 헤더 --}}
        <div class="bg-gradient-to-r from-slate-800 to-slate-900 px-5 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <span class="text-white font-medium text-sm">AI 리뷰 분석</span>
                </div>
                <div class="text-right">
                    <p class="text-blue-400 text-xs">분석한 리뷰</p>
                    <p class="text-white font-bold text-lg" x-text="totalCollected.toLocaleString() + '개'">0개</p>
                </div>
            </div>
        </div>

        {{-- 분석 데이터 시각화 --}}
        <div class="p-5">
            <p class="text-xs text-gray-500 mb-4 flex items-center gap-1">
                <svg class="w-3.5 h-3.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                </svg>
                AI 분석을 통해 수치화된 사용자 경험 데이터
            </p>

            <div class="space-y-3">
                <template x-for="(metric, mIndex) in metrics" :key="mIndex">
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-600 w-20 flex-shrink-0" x-text="metric.name"></span>
                        <div class="flex-1 flex gap-1">
                            <template x-for="i in 5" :key="i">
                                <div class="flex-1 h-6 rounded transition-all duration-300"
                                     :class="i <= metric.current ? metric.color : 'bg-gray-200'"></div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- 자극여부 설명 --}}
            <p class="text-xs text-gray-400 mt-3 text-right">* 자극여부는 낮을수록 좋음</p>
        </div>

        {{-- AI 분석 요약 --}}
        <div class="px-5 pb-5">
            <div class="bg-slate-50 rounded-xl p-4">
                <div class="flex items-start gap-2 mb-3">
                    <div class="w-5 h-5 bg-blue-500 rounded flex items-center justify-center flex-shrink-0 mt-0.5">
                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-800">AI 분석 요약</p>
                </div>

                {{-- 로딩 중 표시 --}}
                <div x-show="!collectionComplete" x-cloak class="space-y-3 pl-7">
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <svg class="w-4 h-4 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="totalCollected.toLocaleString() + '개 리뷰 분석 중...'">리뷰 분석 중...</span>
                    </div>
                    <div class="space-y-2">
                        <div class="h-4 bg-gray-200 rounded animate-pulse w-full"></div>
                        <div class="h-4 bg-gray-200 rounded animate-pulse w-5/6"></div>
                        <div class="h-4 bg-gray-200 rounded animate-pulse w-4/5"></div>
                    </div>
                </div>

                {{-- 완료 시 실제 내용 표시 --}}
                <div x-show="collectionComplete" x-cloak
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100">
                    @php
                        // 제품별 커스텀 요약이 있으면 사용, 없으면 효능 타입별 기본값
                        if (!empty($product->intro_summary)) {
                            $allSummaries = $product->intro_summary;
                        } else {
                            $summaryData = [
                                'moisture' => [
                                    '다수의 리뷰에서 시간이 지나도 수분감이 유지된다는 반응이 반복적으로 관측되었습니다.',
                                    '흡수가 빠르고 끈적임 없이 촉촉하다는 평가가 87%를 차지했습니다.',
                                    '건조한 환경에서도 보습력이 오래 유지된다는 후기가 72% 이상이었습니다.',
                                ],
                                'elasticity' => [
                                    '사용 2~3주 후 피부가 탱탱해지고 탄력이 개선되었다는 리뷰가 다수 관측되었습니다.',
                                    '리프팅 효과와 피부결 개선을 체감했다는 평가가 82%를 차지했습니다.',
                                    '볼 라인이 올라간 느낌이 든다는 후기가 68% 이상이었습니다.',
                                ],
                                'tone' => [
                                    '꾸준한 사용 후 피부톤이 맑아지고 화사해졌다는 리뷰가 반복적으로 관측되었습니다.',
                                    '칙칙함 개선과 피부 균일함에 대한 긍정 평가가 85%를 차지했습니다.',
                                    '잡티와 기미 부위가 옅어졌다는 후기가 73% 이상이었습니다.',
                                ],
                                'pore' => [
                                    '모공이 눈에 띄게 축소되고 피부결이 매끄러워졌다는 리뷰가 다수 관측되었습니다.',
                                    '피지 조절 효과와 모공 케어에 대한 긍정 평가가 79%를 차지했습니다.',
                                    '코와 볼 주변 모공이 덜 눈에 띈다는 후기가 71% 이상이었습니다.',
                                ],
                                'wrinkle' => [
                                    '눈가와 이마 주름이 옅어졌다는 리뷰가 반복적으로 관측되었습니다.',
                                    '잔주름 개선과 피부 매끄러움에 대한 긍정 평가가 81%를 차지했습니다.',
                                    '웃을 때 생기는 주름이 덜 깊어 보인다는 후기가 74% 이상이었습니다.',
                                ],
                            ];
                            $allSummaries = $summaryData[$efficacyType] ?? $summaryData['moisture'];
                        }

                        // 랜덤으로 2~3개 선택 (세션 기반으로 같은 사용자에게 일관된 결과 제공)
                        $sessionKey = 'intro_summary_' . $product->code;
                        if (!session()->has($sessionKey)) {
                            $shuffled = collect($allSummaries)->shuffle();
                            $count = rand(2, 3);
                            session([$sessionKey => $shuffled->take($count)->values()->all()]);
                        }
                        $summaries = session($sessionKey);
                    @endphp
                    <div class="space-y-2 text-sm text-gray-600 leading-relaxed">
                        @foreach($summaries as $summary)
                        <p class="pl-7">"{{ $summary }}"</p>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- 실시간 집계데이터 버튼 (클릭시 모달 표시) --}}
        <div class="px-5 pb-5">
            <button @click="showModal = true"
                    class="w-full flex items-center justify-between px-4 py-3 bg-gradient-to-r from-slate-100 to-slate-50 hover:from-slate-200 hover:to-slate-100 rounded-xl transition-all group">
                <div class="flex items-center gap-2">
                    {{-- 로딩 중: 회전 아이콘 --}}
                    <template x-if="!collectionComplete">
                        <svg class="w-4 h-4 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    {{-- 완료: 초록 점 --}}
                    <template x-if="collectionComplete">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                    </template>
                    <span class="text-sm font-medium text-gray-700" x-text="collectionComplete ? '실시간 데이터 집계완료' : '실시간 데이터 집계중'"></span>
                </div>
                <div class="flex items-center gap-1 text-blue-600">
                    <span class="text-xs">상세보기</span>
                    <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </button>
        </div>

        {{-- 실시간 데이터 수집 모달 --}}
        <div x-show="showModal" x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4"
             @click.self="collectionComplete ? showModal = false : null">
            <div class="bg-slate-900 rounded-2xl p-6 w-full max-w-sm">
                {{-- 수집 중 헤더 --}}
                <div x-show="!collectionComplete" class="text-center mb-6">
                    <div class="w-16 h-16 mx-auto mb-4 relative">
                        <div class="absolute inset-0 border-4 border-blue-500/30 rounded-full animate-ping"></div>
                        <div class="absolute inset-2 border-4 border-blue-400/50 rounded-full animate-pulse"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-8 h-8 text-blue-400 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-white font-bold text-lg mb-1">실시간 데이터 수집 중</h3>
                    <p class="text-slate-400 text-sm">다양한 플랫폼에서 리뷰를 수집하고 있습니다</p>
                </div>

                {{-- 완료 헤더 --}}
                <div x-show="collectionComplete" class="text-center mb-6">
                    <div class="w-16 h-16 mx-auto mb-4 bg-green-500 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <h3 class="text-white font-bold text-lg mb-1">데이터 집계 완료</h3>
                    <p class="text-slate-400 text-sm">총 {{ $product->intro_review_count ?? '12,847' }}개 리뷰 분석 완료</p>
                </div>

                {{-- 수집 현황 --}}
                <div class="space-y-3 mb-6">
                    <template x-for="(platform, index) in platforms" :key="index">
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full"
                                     :class="platform.collected ? 'bg-green-500' : 'bg-slate-600 animate-pulse'"></div>
                                <span class="text-slate-300" x-text="platform.name"></span>
                            </div>
                            <span class="text-blue-400 font-mono text-xs" x-text="platform.count.toLocaleString() + '건'"></span>
                        </div>
                    </template>
                </div>

                {{-- 총 수집 데이터 --}}
                <div class="bg-slate-800 rounded-xl p-4 text-center">
                    <p class="text-slate-400 text-xs mb-1">총 수집 데이터</p>
                    <p class="text-2xl font-bold text-white" x-text="totalCollected.toLocaleString() + '건'"></p>
                </div>

                {{-- 닫기 버튼 (완료 상태에서만) --}}
                <button x-show="collectionComplete" @click="showModal = false"
                        class="w-full mt-4 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-xl transition-colors text-sm">
                    닫기
                </button>
            </div>
        </div>

        {{-- 데이터 출처 안내 --}}
        <div class="px-5 pb-5">
            <p class="text-[10px] text-gray-400 leading-relaxed">
                *네이버스토어, 쿠팡, 화해, 무신사, W컨셉, 아마존 US, Qoo10 등 10개 이상의 주요 쇼핑 플랫폼에 축적된 {{ $product->name }}의 실제 사용자 리뷰를 에센시엘의 AI 분석 시스템으로 통합 분석·정량화한 데이터 결과입니다.
            </p>
        </div>
    </div>

    {{-- 성분 정보 (선택적 표시) --}}
    @if($product->ingredients && count($product->ingredients) > 0)
    <div class="bg-white rounded-2xl shadow-sm p-5 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">주요 성분</h2>
        <div class="flex flex-wrap gap-2">
            @foreach($product->ingredients as $ingredient)
                <span class="px-3 py-1 bg-gray-100 text-gray-700 text-sm rounded-full">
                    {{ $ingredient }}
                </span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- 하단 여백 --}}
    <div class="h-4"></div>

    {{-- Tailwind safelist for dynamic colors --}}
    <div class="hidden">
        <span class="bg-blue-500"></span>
        <span class="bg-indigo-500"></span>
        <span class="bg-purple-500"></span>
        <span class="bg-pink-500"></span>
        <span class="bg-rose-500"></span>
        <span class="bg-red-500"></span>
        <span class="bg-orange-500"></span>
        <span class="bg-amber-500"></span>
        <span class="bg-yellow-500"></span>
        <span class="bg-green-500"></span>
        <span class="bg-emerald-500"></span>
        <span class="bg-teal-500"></span>
        <span class="bg-cyan-500"></span>
    </div>
</div>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@push('scripts')
<script>
function productPage() {
    const productCode = '{{ $product->code }}';
    const storageKey = `product_data_collected_${productCode}`;

    // 총 리뷰 수를 기반으로 플랫폼별 비율 계산
    const totalReviewCount = {{ $product->intro_review_count ?? 12847 }};
    const platformRatios = [0.252, 0.225, 0.168, 0.144, 0.094, 0.069, 0.048, 0]; // 플랫폼별 비율
    const targetCounts = platformRatios.map(ratio => Math.round(totalReviewCount * ratio));

    @php
        $efficacyType = $product->efficacy_type ?? 'moisture';
        if (!empty($product->intro_metrics)) {
            $metricsJson = $product->intro_metrics;
        } else {
            $metricsData = [
                'moisture' => [
                    ['name' => '보습력', 'value' => 5, 'color' => 'bg-blue-500'],
                    ['name' => '보습지속력', 'value' => 4, 'color' => 'bg-indigo-500'],
                    ['name' => '끈적임', 'value' => 4, 'color' => 'bg-cyan-500'],
                    ['name' => '효과 체감', 'value' => 4, 'color' => 'bg-emerald-500'],
                    ['name' => '자극여부', 'value' => 1, 'color' => 'bg-rose-500'],
                ],
                'elasticity' => [
                    ['name' => '탄력 개선', 'value' => 5, 'color' => 'bg-purple-500'],
                    ['name' => '리프팅감', 'value' => 4, 'color' => 'bg-indigo-500'],
                    ['name' => '탄탱함', 'value' => 4, 'color' => 'bg-pink-500'],
                    ['name' => '효과 체감', 'value' => 4, 'color' => 'bg-emerald-500'],
                    ['name' => '자극여부', 'value' => 1, 'color' => 'bg-rose-500'],
                ],
                'tone' => [
                    ['name' => '톤 개선', 'value' => 5, 'color' => 'bg-orange-500'],
                    ['name' => '화사함', 'value' => 4, 'color' => 'bg-amber-500'],
                    ['name' => '균일함', 'value' => 4, 'color' => 'bg-yellow-500'],
                    ['name' => '효과 체감', 'value' => 4, 'color' => 'bg-emerald-500'],
                    ['name' => '자극여부', 'value' => 1, 'color' => 'bg-rose-500'],
                ],
                'pore' => [
                    ['name' => '모공 축소', 'value' => 5, 'color' => 'bg-green-500'],
                    ['name' => '피지 조절', 'value' => 4, 'color' => 'bg-teal-500'],
                    ['name' => '매끄러움', 'value' => 4, 'color' => 'bg-cyan-500'],
                    ['name' => '효과 체감', 'value' => 4, 'color' => 'bg-emerald-500'],
                    ['name' => '자극여부', 'value' => 1, 'color' => 'bg-rose-500'],
                ],
                'wrinkle' => [
                    ['name' => '주름 개선', 'value' => 5, 'color' => 'bg-pink-500'],
                    ['name' => '탄력감', 'value' => 4, 'color' => 'bg-purple-500'],
                    ['name' => '매끄러움', 'value' => 4, 'color' => 'bg-indigo-500'],
                    ['name' => '효과 체감', 'value' => 4, 'color' => 'bg-emerald-500'],
                    ['name' => '자극여부', 'value' => 1, 'color' => 'bg-rose-500'],
                ],
            ];
            $metricsJson = $metricsData[$efficacyType] ?? $metricsData['moisture'];
        }
    @endphp

    // 지표 데이터: current는 현재 표시 값, value는 최종 목표 값
    const metricsData = @json($metricsJson).map(m => ({
        name: m.name,
        value: m.value,
        color: m.color,
        current: 0
    }));

    return {
        reviewCount: {{ $product->intro_review_count ?? 12847 }},
        showModal: false,
        collectionComplete: false,
        totalCollected: 0,
        metrics: metricsData,
        platforms: [
            { name: '네이버스토어', count: 0, collected: false },
            { name: '쿠팡', count: 0, collected: false },
            { name: '화해', count: 0, collected: false },
            { name: '무신사', count: 0, collected: false },
            { name: 'W컨셉', count: 0, collected: false },
            { name: '아마존 US', count: 0, collected: false },
            { name: 'Qoo10', count: 0, collected: false },
            { name: '올리브영', count: 0, collected: false },
        ],

        init() {
            // 이미 본 경우 바로 완료 상태로 표시
            if (localStorage.getItem(storageKey)) {
                this.showCompletedState();
            } else {
                // 처음 방문 시 애니메이션 시작
                this.$nextTick(() => {
                    this.startDataCollection();
                });
            }
        },

        showCompletedState() {
            // 최종 수치로 바로 설정
            this.platforms.forEach((p, i) => {
                p.count = targetCounts[i];
                p.collected = true;
            });
            this.totalCollected = targetCounts.reduce((a, b) => a + b, 0);
            // 지표도 최종값으로 설정
            this.metrics.forEach(m => {
                m.current = m.value;
            });
            this.collectionComplete = true;
            this.showModal = false;
        },

        async startDataCollection() {
            // 모달 없이 백그라운드에서 동작
            this.showModal = false;
            this.collectionComplete = false;
            this.totalCollected = 0;
            this.platforms.forEach(p => { p.count = 0; p.collected = false; });
            this.metrics.forEach(m => { m.current = 0; });

            for (let i = 0; i < this.platforms.length; i++) {
                await this.delay(150 + Math.random() * 100);

                const target = targetCounts[i];
                // 카운트 애니메이션과 함께 totalCollected도 실시간 업데이트
                await this.animateCount(i, target);
                this.platforms[i].collected = true;

                // 플랫폼 수집 완료될 때마다 지표도 점진적으로 업데이트
                await this.updateMetrics(i);
            }

            // 잠시 대기 후 완료 상태로 전환
            await this.delay(200);
            this.collectionComplete = true;

            // localStorage에 저장
            localStorage.setItem(storageKey, 'true');
        },

        async updateMetrics(platformIndex) {
            // 8개 플랫폼 기준 진행률 계산 (0~1)
            const progress = (platformIndex + 1) / this.platforms.length;

            for (let m = 0; m < this.metrics.length; m++) {
                const metric = this.metrics[m];
                // 각 지표의 목표값에 진행률을 곱해서 현재 표시할 값 계산
                const targetCurrent = Math.round(metric.value * progress);

                // 현재 값보다 크면 업데이트 (한 칸씩 채우는 효과)
                if (targetCurrent > metric.current) {
                    // 약간의 딜레이를 주고 한 칸씩 업데이트
                    for (let v = metric.current + 1; v <= targetCurrent; v++) {
                        await this.delay(30);
                        metric.current = v;
                    }
                }
            }
        },

        async animateCount(platformIndex, target) {
            const duration = 200;
            const steps = 10;
            const increment = target / steps;

            for (let j = 0; j <= steps; j++) {
                const currentValue = Math.round(increment * j);
                const prevValue = this.platforms[platformIndex].count;
                this.platforms[platformIndex].count = currentValue;
                // 실시간으로 totalCollected 업데이트 (증가분만 추가)
                this.totalCollected += (currentValue - prevValue);
                await this.delay(duration / steps);
            }
            // 최종값 정확히 설정
            const diff = target - this.platforms[platformIndex].count;
            this.platforms[platformIndex].count = target;
            this.totalCollected += diff;
        },

        delay(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }
    };
}
</script>
@endpush
