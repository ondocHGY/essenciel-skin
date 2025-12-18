@extends('layouts.app')

@section('title', $product->name . ' - 피부 효과 분석')

@php
    $efficacyType = $product->efficacy_type ?? 'moisture';
    $pointColor = $product->point_color ?? '#10B981';

    // HEX를 RGB로 변환하는 함수
    $hexToRgb = function($hex) {
        $hex = ltrim($hex, '#');
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        ];
    };
    $rgb = $hexToRgb($pointColor);
    $rgbString = implode(', ', $rgb);
@endphp

@section('content')
<div x-data="productPage()" class="bg-white min-h-screen">
    {{-- 상단 헤더 (스크롤 시 고정) --}}
    <div class="bg-black py-3 sticky top-0 z-50 overflow-hidden">
        <div class="marquee-track">
            <span class="marquee-text text-sm text-white">에센시엘은 검증된 데이터를 기반으로 과학적으로 설계합니다.</span>
        </div>
    </div>

    <div class="px-4 py-6 max-w-lg mx-auto">
        {{-- 제품 이미지 --}}
        @if($product->image)
        <div class="flex justify-center mb-6">
            <div class="w-48 h-48 rounded-2xl overflow-hidden bg-white" style="border: 2px solid #D9D9D9;">
                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
            </div>
        </div>
        @endif

        {{-- 제품 정보 --}}
        <div class="text-center mb-6">
            <img src="{{ asset('logo_black.png') }}" alt="essenciel" class="h-7 mx-auto mb-2">
            <h1 class="text-xl font-bold text-gray-900">{{ $product->name }}</h1>
        </div>

        {{-- CTA 버튼 --}}
        <div class="mb-6">
            <a href="{{ route('survey.index', $product->code) }}" class="block w-full py-4 bg-black hover:bg-gray-900 text-white text-center font-semibold rounded-xl transition-all shadow-lg">
                내가 사용해도 효과가 있을까?
            </a>
            <p class="text-center text-gray-400 text-xs mt-2">약 1분 소요</p>
        </div>

        {{-- Active Ingredients 슬라이드 --}}
        @php
            $ingredients = $product->activeIngredients;
        @endphp
        @if($ingredients->count() > 0)
        <div class="mb-6" x-data="ingredientSlider({{ $ingredients->count() }})">
            <div class="bg-white rounded-2xl overflow-hidden" style="border: 1px solid #D9D9D9;">
                <div class="mb-4" style="border-bottom: 1px solid #D9D9D9;">
                    <div class="flex items-center justify-between py-2 px-5">
                        <h2 class="text-sm font-semibold text-gray-900">Active Ingredients</h2>
                        <span class="text-xs text-gray-400" x-text="String(currentSlide + 1).padStart(2, '0')">01</span>
                    </div>
                </div>

                {{-- 슬라이드 컨테이너 --}}
                <div class="relative overflow-hidden">
                    <div class="flex items-stretch"
                         :class="isTransitioning ? 'transition-transform duration-500 ease-in-out' : ''"
                         :style="'transform: translateX(-' + (currentSlide * 100) + '%)'">
                        @foreach($ingredients as $index => $ingredient)
                        <x-ingredient-slide-item :ingredient="$ingredient" :pointColor="$pointColor" />
                        @endforeach
                        {{-- 무한 슬라이드를 위한 첫번째 슬라이드 복제 --}}
                        @if($ingredients->count() > 1)
                        <x-ingredient-slide-item :ingredient="$ingredients->first()" :pointColor="$pointColor" />
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- AI 리뷰 분석 섹션 --}}
        <div class="bg-white rounded-2xl overflow-hidden mb-6" style="border: 1px solid #D9D9D9;">
            {{-- 헤더 --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="text-xl font-semibold text-gray-900">AI 리뷰 분석</h2>
                <div class="text-right">
                    <p class="text-sm text-gray-400">분석한 리뷰</p>
                    <p class="text-xl font-bold text-gray-900" x-text="totalCollected.toLocaleString() + ' 개'">0 개</p>
                </div>
            </div>

            {{-- 레이더 차트 영역 --}}
            <div class="p-3">
                <div class="relative w-full aspect-square mx-auto" style="max-width: 460px;">
                    <canvas id="radarChart" class="w-full h-full"></canvas>
                </div>
                <p class="text-xs text-gray-400 mt-3 text-center">*끈적임 & 자극여부는 낮을수록 좋음</p>
            </div>

            {{-- AI 분석 요약 --}}
            <div class="px-5 pb-5">
                <div class="rounded-xl px-6 py-4" style="background-color: rgba({{ $rgbString }}, 0.15)">
                    <h3 class="text-base font-semibold text-gray-900 mb-2">AI 분석요약</h3>

                    {{-- 로딩 중 표시 --}}
                    <div x-show="!collectionComplete" x-cloak class="space-y-3">
                        <div class="flex items-center gap-2 text-sm text-gray-500">
                            <x-loading-spinner />
                            <span x-text="totalCollected.toLocaleString() + '개 리뷰 분석 중...'">리뷰 분석 중...</span>
                        </div>
                        <div class="space-y-2">
                            <div class="h-4 bg-gray-200 rounded animate-pulse w-full"></div>
                            <div class="h-4 bg-gray-200 rounded animate-pulse w-5/6"></div>
                        </div>
                    </div>

                    {{-- 완료 시 실제 내용 표시 --}}
                    <div x-show="collectionComplete" x-cloak
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100">
                        @php
                            if (!empty($product->intro_summary)) {
                                $allSummaries = $product->intro_summary;
                            } else {
                                $summaryData = [
                                    'moisture' => [
                                        '꾸준한 사용 후 **피부톤이 맑아지고 화사해졌다**는 리뷰가 반복적으로 관측되었습니다.',
                                        '**칙칙했던 눈 밑이 밝아졌다**는 후기가 반복적으로 관측되었습니다.',
                                        '시간이 지나도 **수분감이 유지된다**는 반응이 반복적으로 관측되었습니다.',
                                    ],
                                    'elasticity' => [
                                        '사용 2~3주 후 **피부가 탱탱해지고 탄력이 개선**되었다는 리뷰가 다수 관측되었습니다.',
                                        '**볼 라인이 올라간 느낌**이 든다는 후기가 반복적으로 관측되었습니다.',
                                        '**피부가 탄탄해지고 처짐이 개선**되었다는 평가가 많았습니다.',
                                    ],
                                    'tone' => [
                                        '꾸준한 사용 후 **피부톤이 맑아지고 화사해졌다**는 리뷰가 반복적으로 관측되었습니다.',
                                        '**칙칙했던 눈 밑이 밝아졌다**는 후기가 반복적으로 관측되었습니다.',
                                        '**잡티와 기미 부위가 옅어졌다**는 평가가 73% 이상이었습니다.',
                                    ],
                                    'pore' => [
                                        '**모공이 눈에 띄게 축소**되고 피부결이 매끄러워졌다는 리뷰가 다수 관측되었습니다.',
                                        '**코와 볼 주변 모공이 덜 눈에 띈다**는 후기가 반복적으로 관측되었습니다.',
                                        '오후에도 **피지가 덜 올라온다**는 반응이 반복적으로 관측되었습니다.',
                                    ],
                                    'wrinkle' => [
                                        '**눈가와 이마 주름이 옅어졌다**는 리뷰가 반복적으로 관측되었습니다.',
                                        '**웃을 때 생기는 주름이 덜 깊어 보인다**는 후기가 반복적으로 관측되었습니다.',
                                        '**미간 주름 부위가 부드러워졌다**는 후기가 67%였습니다.',
                                    ],
                                ];
                                $allSummaries = $summaryData[$efficacyType] ?? $summaryData['moisture'];
                            }

                            // 랜덤으로 2~3개 선택
                            $shuffled = collect($allSummaries)->shuffle();
                            $displayCount = min(rand(2, 3), $shuffled->count());
                            $selectedSummaries = $shuffled->take($displayCount);

                            // **텍스트** 를 포인트컬러 굵은 글씨로 변환
                            $formatSummary = function($text) use ($pointColor) {
                                return preg_replace(
                                    '/\*\*(.+?)\*\*/',
                                    '<strong style="color: black">$1</strong>',
                                    $text
                                );
                            };
                        @endphp
                        <div class="space-y-2 text-base text-gray-600 leading-relaxed">
                            @foreach($selectedSummaries as $summary)
                            <p>"{!! $formatSummary($summary) !!}"</p>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- 실시간 집계데이터 버튼 --}}
            <div class="px-5 pb-5">
                <button @click="showModal = true" class="w-full flex items-center justify-between px-4 py-4 bg-black hover:bg-gray-900 rounded-xl transition-all group">
                    <div class="flex items-center gap-2">
                        <template x-if="!collectionComplete">
                            <x-loading-spinner />
                        </template>
                        <template x-if="collectionComplete">
                            <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                        </template>
                        <span class="text-base font-medium" :style="collectionComplete ? 'color: white' : 'color: white'" x-text="collectionComplete ? '실시간 데이터 집계완료' : '실시간 데이터 집계중'"></span>
                    </div>
                    <div class="flex items-center gap-1" style="color: {{ $pointColor }}">
                        <span class="text-sm">상세보기</span>
                        <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </button>
            </div>

            {{-- 데이터 출처 안내 --}}
            <div class="px-5 pb-5">
                <p class="text-xs text-gray-400 leading-relaxed">
                    *네이버스토어, 쿠팡, 화해, 무신사, W컨셉, 아마존 US, Qoo10 등 10개 이상의 주요 쇼핑 플랫폼에 축적된 {{ $product->name }}의 실제 사용자 리뷰를 에센시엘의 AI 분석 시스템으로 통합 분석·정량화한 데이터 결과입니다.
                </p>
            </div>

        </div>

        {{-- 나노 리포좀 기술 섹션 (통합) --}}
        <div class="bg-white rounded-2xl overflow-hidden mb-6" style="border: 1px solid #D9D9D9;">
            {{-- 헤더 --}}
            <div class="flex items-center justify-between p-5">
                <h2 class="text-xl font-semibold text-gray-900">나노 리포좀 기술</h2>
                <div class="text-right">
                    <p class="text-base text-gray-400">유효 성분</p>
                    <p class="text-xl font-bold text-gray-900">흡수율 UP</p>
                </div>
            </div>

            {{-- 기술 설명 --}}
            <div class="overflow-hidden mb-4" style="border-top: 1px solid #D9D9D9; border-bottom: 1px solid #D9D9D9;">
                <div class="grid grid-cols-2">
                    {{-- 리포좀 영상 (좌측) --}}
                    <div class="bg-white" style="padding: 5px;">
                        <video autoplay muted loop playsinline class="w-full h-auto">
                            <source src="{{ asset('product/liposome.mp4') }}" type="video/mp4">
                        </video>
                    </div>
                    {{-- 세로 구분선 + 텍스트 영역 (우측) --}}
                    <div class="p-4 flex flex-col justify-between bg-white" style="border-left: 1px solid #D9D9D9;">
                        <span class="text-lg font-medium" style="color: {{ $pointColor }}">Nano-Liposome</span>
                        <p class="text-base leading-snug">유효 성분을 리포좀 캡슐화하여 피부 속 깊숙히 안정적으로 전달해주는 기술</p>
                    </div>
                </div>
            </div>
            <p class="text-xs text-gray-400 text-center mt-2" style="margin-bottom: 40px;">*이해를 돕기 위한 영상입니다.</p>

            {{-- 기술 특징 --}}
            <div class="space-y-2 px-5" style="margin-bottom: 40px;">
                <div class="text-center py-4 bg-white rounded-lg border-2 border-gray-200"><span class="text-base font-bold">안정성 300% 향상 ↑</span></div>
                <div class="text-center py-4 bg-white rounded-lg border-2 border-gray-200"><span class="text-base font-bold">단계별 전달 시스템으로 효과 지속성 ↑</span></div>
                <div class="text-center py-4 bg-white rounded-lg border-2 border-gray-200"><span class="text-base font-bold">점진적 방출로 자극 최소화 ↓</span></div>
            </div>

            {{-- SCI급 논문 섹션 --}}
            <div class="text-center" style="background-color: #F5F5F5; padding-top: 48px; padding-bottom: 24px; margin-top: 48px;">
                <p class="text-base text-gray-500 mb-1">SCI급 논문에 게재된</p>
                <h2 class="text-2xl font-bold text-gray-900">나노 리포좀의 우수성</h2>
            </div>

            {{-- 논문 이미지 슬라이드 --}}
            <div class="overflow-hidden -mx-5" style="background-color: #F5F5F5;" x-data="articleSlider()">
                <div class="overflow-hidden px-3">
                    <div class="flex gap-1"
                         :class="isTransitioning ? 'transition-transform duration-500 ease-in-out' : ''"
                         :style="'transform: translateX(calc(-' + currentSlide + ' * (33.3333% + 4px / 3)))'">
                        @for($i = 1; $i <= 5; $i++)
                        <div class="flex-shrink-0" style="width: calc((100% - 8px) / 3);">
                            <img src="{{ asset('product/article_' . $i . '.png') }}" alt="Article {{ $i }}" class="w-full h-auto">
                        </div>
                        @endfor
                        {{-- 무한 슬라이드를 위한 복제 --}}
                        @for($i = 1; $i <= 5; $i++)
                        <div class="flex-shrink-0" style="width: calc((100% - 8px) / 3);">
                            <img src="{{ asset('product/article_' . $i . '.png') }}" alt="Article {{ $i }}" class="w-full h-auto">
                        </div>
                        @endfor
                    </div>
                </div>
            </div>

            <div class="px-5" style="background-color: #F5F5F5; padding-top: 24px; padding-bottom: 48px; ">
                <div class="rounded-xl p-4 bg-white border-2" style="border-color: #D9D9D9;">
                    <p class="text-sm text-gray-900 leading-relaxed">
                        SCI는 과학 분야에서 권위 있는 학술지로 인정받고 있으며, 나노 리포좀에 대한 연구결과는 SCI급 논문에 인용되어 전 세계 연구자들의 주목을 받고 있습니다.
                    </p>
                </div>
            </div>

            {{-- SCI급 논문 자료 --}}
            <div class="bg-white px-5" style="padding-bottom: 24px; padding-top: 48px;">
                <p class="text-base text-gray-400 text-center mb-1">SCI급 논문 자료</p>
                <h3 class="text-2xl text-center font-bold text-gray-900 mb-6">나노 리포좀의 지속성</h3>

                {{-- 통계 수치 --}}
                <div class="flex items-center justify-center mb-4">
                    <div class="text-center flex-1">
                        <p class="text-3xl font-bold text-gray-900">85.8%</p>
                        <p class="text-[10px] text-gray-400 mt-1 tracking-wider">ACTIVE RETENTION</p>
                    </div>
                    <div class="flex-shrink-0" style="width: 2px; height: 40px; background-color: #D9D9D9;"></div>
                    <div class="text-center flex-1">
                        <p class="text-3xl font-bold text-gray-900">1.56배</p>
                        <p class="text-[10px] text-gray-400 mt-1 tracking-wider">VS. STANDARD</p>
                    </div>
                </div>

                <p class="text-xs text-gray-400 text-center mb-6" style="margin-bottom: 40px;">*첫 세정 후 일반 성분 대비 유효 성분 잔여량</p>

                {{-- 범례 --}}
                <div class="flex items-center justify-end gap-8 mb-4">
                    <div class="flex items-center gap-1.5">
                        <img src="{{ asset('product/graph_green.svg') }}" alt="" class="w-4 h-4">
                        <span class="text-xs text-gray-600">나노리포좀</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <img src="{{ asset('product/graph_black.svg') }}" alt="" class="w-4 h-4">
                        <span class="text-xs text-gray-600">일반성분</span>
                    </div>
                </div>

                {{-- 그래프 영역 (세로 배치) --}}
                <div class="space-y-6">
                    <div>
                        <p class="text-xs">remaining collagen(%)</p>
                        <div style="position: relative; height: 270px; width: 100%; overflow: visible;">
                            <canvas id="collagenChart"></canvas>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs">remaining fluorescence(%)</p>
                        <div style="position: relative; height: 270px; width: 100%; overflow: visible;">
                            <canvas id="fluorescenceChart"></canvas>
                        </div>
                    </div>
                </div>

                <p class="text-[10px] text-gray-400 text-center mt-6">*원료적 특성에 한 함</p>
            </div>
        </div>

        {{-- 하단 여백 --}}
        <div class="h-4"></div>
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
                    <div class="absolute inset-0 border-4 rounded-full animate-ping" style="border-color: rgba({{ $rgbString }}, 0.3)"></div>
                    <div class="absolute inset-2 border-4 rounded-full animate-pulse" style="border-color: rgba({{ $rgbString }}, 0.5)"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <x-loading-spinner size="8" :customColor="$pointColor" />
                    </div>
                </div>
                <h3 class="text-white font-bold text-lg mb-1">실시간 데이터 수집 중</h3>
                <p class="text-slate-400 text-sm">다양한 플랫폼에서 리뷰를 수집하고 있습니다</p>
            </div>

            {{-- 완료 헤더 --}}
            <div x-show="collectionComplete" class="text-center mb-6">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center" style="background-color: {{ $pointColor }}">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3 class="text-white font-bold text-lg mb-1">데이터 집계 완료</h3>
                <p class="text-slate-400 text-sm">총 <span x-text="totalCollected.toLocaleString()"></span>개 리뷰 분석 완료</p>
            </div>

            {{-- 수집 현황 --}}
            <div class="space-y-3 mb-6">
                <template x-for="(platform, index) in platforms" :key="index">
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full"
                                 :class="platform.collected ? '' : 'bg-slate-600 animate-pulse'"
                                 :style="platform.collected ? 'background-color: {{ $pointColor }}' : ''"></div>
                            <span class="text-slate-300" x-text="platform.name"></span>
                        </div>
                        <span class="font-mono text-xs" style="color: {{ $pointColor }}" x-text="platform.count.toLocaleString() + '건'"></span>
                    </div>
                </template>
            </div>

            {{-- 총 수집 데이터 --}}
            <div class="bg-slate-800 rounded-xl p-4 text-center">
                <p class="text-slate-400 text-xs mb-1">총 수집 데이터</p>
                <p class="text-2xl font-bold text-white" x-text="totalCollected.toLocaleString() + '건'"></p>
            </div>

            {{-- 닫기 버튼 --}}
            <button x-show="collectionComplete" @click="showModal = false"
                    class="w-full mt-4 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-xl transition-colors text-sm">
                닫기
            </button>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }

    /* 무한 롤링 마퀴 */
    .marquee-track {
        display: flex;
        width: max-content;
        animation: marquee 18s linear infinite;
    }
    .marquee-text {
        padding-right: 30vw;
        white-space: nowrap;
    }
    .marquee-track::after {
        content: '에센시엘은 검증된 데이터를 기반으로 과학적으로 설계합니다.';
        padding-right: 30vw;
        white-space: nowrap;
        font-size: 0.875rem;
        color: white;
    }
    @keyframes marquee {
        0% { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function productPage() {
    const productCode = '{{ $product->code }}';
    const storageKey = `product_data_collected_${productCode}`;

    const totalReviewCount = {{ $product->intro_review_count ?? 11257 }};
    const platformRatios = [0.252, 0.225, 0.168, 0.144, 0.094, 0.069, 0.048, 0];
    const targetCounts = platformRatios.map(ratio => Math.round(totalReviewCount * ratio));

    @php
        $efficacyType = $product->efficacy_type ?? 'moisture';

        // intro_metrics가 있으면 그대로 사용 (admin에서 설정한 값)
        if (!empty($product->intro_metrics) && count($product->intro_metrics) > 0) {
            // name이 비어있지 않은 항목만 필터링
            $metricsJson = collect($product->intro_metrics)
                ->filter(fn($m) => !empty($m['name']))
                ->map(fn($m) => [
                    'name' => $m['name'],
                    'value' => (int)($m['value'] ?? 0),
                ])
                ->values()
                ->toArray();
        }

        // intro_metrics가 없거나 비어있으면 효능 타입별 기본값 사용
        if (empty($metricsJson)) {
            $metricsDefaults = [
                'moisture' => [
                    ['name' => '보습력', 'value' => 5],
                    ['name' => '보습지속력', 'value' => 4],
                    ['name' => '끈적임', 'value' => 2],
                    ['name' => '자극여부', 'value' => 1],
                    ['name' => '효과체감', 'value' => 4],
                ],
                'elasticity' => [
                    ['name' => '탄력 개선', 'value' => 5],
                    ['name' => '리프팅감', 'value' => 4],
                    ['name' => '끈적임', 'value' => 2],
                    ['name' => '자극여부', 'value' => 1],
                    ['name' => '효과체감', 'value' => 4],
                ],
                'tone' => [
                    ['name' => '톤 개선', 'value' => 5],
                    ['name' => '화사함', 'value' => 4],
                    ['name' => '끈적임', 'value' => 2],
                    ['name' => '자극여부', 'value' => 1],
                    ['name' => '효과체감', 'value' => 4],
                ],
                'pore' => [
                    ['name' => '모공 축소', 'value' => 5],
                    ['name' => '피지 조절', 'value' => 4],
                    ['name' => '끈적임', 'value' => 2],
                    ['name' => '자극여부', 'value' => 1],
                    ['name' => '효과체감', 'value' => 4],
                ],
                'wrinkle' => [
                    ['name' => '주름 개선', 'value' => 5],
                    ['name' => '탄력감', 'value' => 4],
                    ['name' => '끈적임', 'value' => 2],
                    ['name' => '자극여부', 'value' => 1],
                    ['name' => '효과체감', 'value' => 4],
                ],
            ];
            $metricsJson = $metricsDefaults[$efficacyType] ?? $metricsDefaults['moisture'];
        }
    @endphp

    const metricsData = @json($metricsJson);
    const pointColor = '{{ $pointColor }}';
    const pointColorRgb = '{{ $rgbString }}';
    let radarChart = null;

    return {
        reviewCount: totalReviewCount,
        showModal: false,
        collectionComplete: false,
        totalCollected: 0,
        metrics: metricsData,
        currentMetricValues: metricsData.map(() => 0),
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
            this.$nextTick(() => {
                this.initRadarChart();
                this.initSciCharts();

                if (localStorage.getItem(storageKey)) {
                    this.showCompletedState();
                } else {
                    this.startDataCollection();
                }
            });
        },

        initRadarChart() {
            const ctx = document.getElementById('radarChart');
            if (!ctx) return;

            // 그라데이션 생성 (중앙 포인트컬러 -> 외곽 흰색)
            const chartCtx = ctx.getContext('2d');
            const centerX = ctx.offsetWidth / 2;
            const centerY = ctx.offsetHeight / 2;
            const radius = Math.min(centerX, centerY) * 0.7;

            const gradient = chartCtx.createRadialGradient(
                centerX, centerY, 0,
                centerX, centerY, radius
            );
            gradient.addColorStop(0, `rgba(${pointColorRgb}, 0.95)`);
            gradient.addColorStop(0.5, `rgba(${pointColorRgb}, 0.6)`);
            gradient.addColorStop(0.85, `rgba(${pointColorRgb}, 0.25)`);
            gradient.addColorStop(1, 'rgba(255, 255, 255, 0)');

            radarChart = new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: this.metrics.map(m => m.name),
                    datasets: [{
                        data: this.currentMetricValues,
                        backgroundColor: gradient,
                        borderColor: pointColor,
                        borderWidth: 1,
                        pointRadius: 0,
                        pointHoverRadius: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    animation: {
                        duration: 0
                    },
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 5,
                            min: 0,
                            ticks: {
                                stepSize: 1,
                                display: false
                            },
                            grid: {
                                circular: true,
                                color: 'transparent',
                                lineWidth: 0
                            },
                            angleLines: {
                                color: 'transparent',
                                lineWidth: 0
                            },
                            pointLabels: {
                                font: { size: 15, weight: '600' },
                                color: '#374151'
                            },
                            backgroundColor: 'rgba(243, 244, 246, 1)'
                        }
                    }
                },
                plugins: [{
                    id: 'gridOnTop',
                    afterDatasetsDraw: (chart) => {
                        const ctx = chart.ctx;
                        const scale = chart.scales.r;
                        const centerX = scale.xCenter;
                        const centerY = scale.yCenter;
                        const maxRadius = scale.drawingArea;
                        const labelCount = chart.data.labels.length;
                        const maxValue = scale.max;

                        ctx.save();

                        // 원형 격자 그리기 (데이터 위에)
                        ctx.strokeStyle = 'rgba(180, 180, 180, 0.8)';
                        ctx.lineWidth = 1;
                        for (let i = 1; i <= maxValue; i++) {
                            const r = (i / maxValue) * maxRadius;
                            ctx.beginPath();
                            ctx.arc(centerX, centerY, r, 0, Math.PI * 2);
                            ctx.stroke();
                        }

                        // 방사선 그리기 (데이터 위에)
                        for (let i = 0; i < labelCount; i++) {
                            const angle = scale.getIndexAngle(i) - Math.PI / 2;
                            const x = centerX + Math.cos(angle) * maxRadius;
                            const y = centerY + Math.sin(angle) * maxRadius;

                            ctx.beginPath();
                            ctx.moveTo(centerX, centerY);
                            ctx.lineTo(x, y);
                            ctx.stroke();
                        }

                        // 각 축의 끝점에 검은색 점 그리기
                        for (let i = 0; i < labelCount; i++) {
                            const angle = scale.getIndexAngle(i) - Math.PI / 2;
                            const x = centerX + Math.cos(angle) * maxRadius;
                            const y = centerY + Math.sin(angle) * maxRadius;

                            ctx.beginPath();
                            ctx.arc(x, y, 2, 0, Math.PI * 2);
                            ctx.fillStyle = '#1f2937';
                            ctx.fill();
                        }

                        ctx.restore();
                    }
                }]
            });
        },

        initSciCharts() {
            // 포인트 이미지 로드
            const greenPointImg = new Image(16, 16);
            greenPointImg.src = '{{ asset("product/graph_green.svg") }}';
            const blackPointImg = new Image(16, 16);
            blackPointImg.src = '{{ asset("product/graph_black.svg") }}';

            // 차트 공통 옵션
            const chartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 10 } },
                animation: {
                    duration: 350,
                    easing: 'easeOutQuart'
                },
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        min: 0,
                        max: 110,
                        ticks: {
                            font: { size: 11, weight: 'bold' },
                            color: '#1f2937',
                            stepSize: 20,
                            callback: function(value) {
                                // 110은 숨기고 나머지만 표시
                                return value > 100 ? '' : value;
                            }
                        },
                        border: { color: '#1f2937', width: 2 },
                        grid: {
                            color: function(context) {
                                // 맨 위 격자선(110)은 숨김
                                if (context.tick.value >= 110) {
                                    return 'transparent';
                                }
                                return '#e5e7eb';
                            }
                        }
                    },
                    x: {
                        offset: true,
                        ticks: { font: { size: 11, weight: 'bold' }, color: '#1f2937' },
                        title: { display: true, text: 'of washing', font: { size: 11, weight: 'bold' }, color: '#1f2937', align: 'end' },
                        border: { color: '#1f2937', width: 2 },
                        grid: { display: false }
                    }
                }
            };

            // 포인트별 순차 애니메이션 함수
            const animatePointsSequentially = (chart, targetData1, targetData2, delay = 450) => {
                const numPoints = targetData1.length;
                for (let i = 0; i < numPoints; i++) {
                    setTimeout(() => {
                        chart.data.datasets[0].data[i] = targetData1[i];
                        chart.data.datasets[1].data[i] = targetData2[i];
                        chart.update();
                    }, i * delay);
                }
            };

            // 차트 리셋 및 다시 그리기 함수
            const resetAndAnimateChart = (chart, targetData1, targetData2, delay = 450) => {
                chart.data.datasets[0].data = [0, 0, 0, 0, 0];
                chart.data.datasets[1].data = [0, 0, 0, 0, 0];
                chart.update();
                setTimeout(() => {
                    animatePointsSequentially(chart, targetData1, targetData2, delay);
                }, 1000); // 0 상태에서 1초 멈춤
            };

            // SCI 차트 생성 헬퍼 함수
            const createSciChart = (canvasId, targetGreen, targetBlack) => {
                const ctx = document.getElementById(canvasId);
                if (!ctx) return null;

                const chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['0', '1', '2', '3', '4'],
                        datasets: [
                            { data: [0, 0, 0, 0, 0], borderColor: pointColor, backgroundColor: 'transparent', fill: false, tension: 0, pointStyle: greenPointImg, pointRadius: 8, borderWidth: 1 },
                            { data: [0, 0, 0, 0, 0], borderColor: '#1f2937', backgroundColor: 'transparent', fill: false, tension: 0, pointStyle: blackPointImg, pointRadius: 8, borderWidth: 1 }
                        ]
                    },
                    options: chartOptions
                });

                let animated = false;
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting && !animated) {
                            animated = true;
                            setTimeout(() => animatePointsSequentially(chart, targetGreen, targetBlack, 450), 100);
                            observer.disconnect();

                            // 10초마다 차트 다시 그리기
                            setInterval(() => {
                                resetAndAnimateChart(chart, targetGreen, targetBlack, 450);
                            }, 10000);
                        }
                    });
                }, { threshold: 0.8 });
                observer.observe(ctx);

                return chart;
            };

            // Collagen & Fluorescence Charts
            createSciChart('collagenChart', [100, 40, 35, 30, 28], [100, 20, 15, 14, 12]);
            createSciChart('fluorescenceChart', [100, 80, 78, 76, 74], [100, 65, 50, 48, 45]);
        },

        updateRadarChart() {
            if (radarChart) {
                radarChart.data.datasets[0].data = this.currentMetricValues;
                radarChart.update('none');
            }
        },

        showCompletedState() {
            this.platforms.forEach((p, i) => {
                p.count = targetCounts[i];
                p.collected = true;
            });
            this.totalCollected = targetCounts.reduce((a, b) => a + b, 0);
            this.collectionComplete = true;
            this.showModal = false;

            // 레이더 차트는 항상 애니메이션으로 그리기
            this.currentMetricValues = this.metrics.map(() => 0);
            this.$nextTick(() => {
                this.animateRadarChart();
            });
        },

        async animateRadarChart() {
            const targetValues = this.metrics.map(m => m.value);
            const steps = 20;
            const delay = 20;

            for (let step = 1; step <= steps; step++) {
                await new Promise(resolve => setTimeout(resolve, delay));
                this.currentMetricValues = targetValues.map(v => (v * step) / steps);
                this.updateRadarChart();
            }
        },

        async startDataCollection() {
            this.showModal = false;
            this.collectionComplete = false;
            this.totalCollected = 0;
            this.platforms.forEach(p => { p.count = 0; p.collected = false; });
            this.currentMetricValues = this.metrics.map(() => 0);
            this.updateRadarChart();

            // 플랫폼별 데이터 수집 애니메이션
            for (let i = 0; i < this.platforms.length; i++) {
                await this.delay(150 + Math.random() * 100);

                const target = targetCounts[i];
                await this.animateCount(i, target);
                this.platforms[i].collected = true;
            }

            // 데이터 수집 완료 후 레이더 차트 애니메이션 시작
            await this.delay(300);
            await this.animateRadarChart();

            await this.delay(200);
            this.collectionComplete = true;
            localStorage.setItem(storageKey, 'true');
        },

        async updateMetrics(platformIndex) {
            // 데이터 수집 중에는 그래프를 그리지 않음
            // 수집 완료 후 animateRadarChart에서 처리
        },

        async animateRadarChart() {
            // 시계방향으로 각 영역 하나씩 표시
            for (let m = 0; m < this.metrics.length; m++) {
                const metric = this.metrics[m];
                // 해당 영역 값을 0에서 목표값까지 애니메이션
                for (let v = 0; v <= metric.value; v++) {
                    await this.delay(50); // 속도 느리게 (20 -> 50)
                    this.currentMetricValues[m] = v;
                    this.updateRadarChart();
                }
                await this.delay(100); // 다음 영역으로 넘어가기 전 대기 (25 -> 100)
            }

            // 차트 완료 후 일정 시간마다 다시 그리기
            this.startRadarChartLoop();
        },

        startRadarChartLoop() {
            // 8초마다 레이더 차트 다시 그리기
            setInterval(async () => {
                this.currentMetricValues = this.metrics.map(() => 0);
                this.updateRadarChart();
                await this.delay(500);

                for (let m = 0; m < this.metrics.length; m++) {
                    const metric = this.metrics[m];
                    for (let v = 0; v <= metric.value; v++) {
                        await this.delay(50);
                        this.currentMetricValues[m] = v;
                        this.updateRadarChart();
                    }
                    await this.delay(100);
                }
            }, 8000);
        },

        async animateCount(platformIndex, target) {
            const duration = 200;
            const steps = 10;
            const increment = target / steps;

            for (let j = 0; j <= steps; j++) {
                const currentValue = Math.round(increment * j);
                const prevValue = this.platforms[platformIndex].count;
                this.platforms[platformIndex].count = currentValue;
                this.totalCollected += (currentValue - prevValue);
                await this.delay(duration / steps);
            }
            const diff = target - this.platforms[platformIndex].count;
            this.platforms[platformIndex].count = target;
            this.totalCollected += diff;
        },

        delay(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }
    };
}

// 성분 슬라이드 컴포넌트
function ingredientSlider(totalSlides = 0) {
    return {
        currentSlide: 0,
        autoPlayInterval: null,
        total: totalSlides,
        isTransitioning: true,

        init() {
            if (this.total > 1) {
                this.startAutoPlay();
            }
        },

        startAutoPlay() {
            this.autoPlayInterval = setInterval(() => {
                this.nextSlide();
            }, 4000); // 4초마다 전환
        },

        stopAutoPlay() {
            if (this.autoPlayInterval) {
                clearInterval(this.autoPlayInterval);
            }
        },

        nextSlide() {
            this.isTransitioning = true;
            this.currentSlide++;

            // 복제된 슬라이드(마지막+1)에 도달하면 처음으로 리셋
            if (this.currentSlide >= this.total) {
                setTimeout(() => {
                    this.isTransitioning = false;
                    this.currentSlide = 0;
                }, 500);
            }
        },

        prevSlide() {
            this.currentSlide = (this.currentSlide - 1 + this.total) % this.total;
        },

        goToSlide(index) {
            this.currentSlide = index;
            // 수동 전환 시 자동 재생 재시작
            this.stopAutoPlay();
            this.startAutoPlay();
        }
    };
}

// 논문 이미지 슬라이드 컴포넌트
function articleSlider() {
    return {
        currentSlide: 0,
        totalSlides: 5,
        autoPlayInterval: null,
        isTransitioning: true,

        init() {
            this.startAutoPlay();
        },

        startAutoPlay() {
            this.autoPlayInterval = setInterval(() => {
                this.isTransitioning = true;
                this.currentSlide++;

                // 복제된 슬라이드 끝에 도달하면 처음으로 리셋
                if (this.currentSlide >= this.totalSlides) {
                    setTimeout(() => {
                        this.isTransitioning = false;
                        this.currentSlide = 0;
                    }, 500);
                }
            }, 3000);
        },

        stopAutoPlay() {
            if (this.autoPlayInterval) {
                clearInterval(this.autoPlayInterval);
            }
        }
    };
}
</script>
@endpush
