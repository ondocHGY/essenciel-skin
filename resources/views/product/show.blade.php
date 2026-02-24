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

    // 강조 컬러 (accent_color가 없으면 포인트 컬러의 55% 어둡게)
    if (!empty($product->accent_color)) {
        $accentColor = $product->accent_color;
    } else {
        $accentColor = sprintf('#%02x%02x%02x',
            round($rgb[0] * 0.55),
            round($rgb[1] * 0.55),
            round($rgb[2] * 0.55)
        );
    }

    $ingredients = $product->activeIngredients;
@endphp

@section('content')
<div x-data="productPage()" class="bg-white min-h-screen">
    <x-top-header :product="$product" :other-products="$otherProducts" />

    {{-- ==================== 메인 컨텐츠 ==================== --}}
    <div class="max-w-lg mx-auto">

        {{-- 히어로 섹션 --}}
        <div class="px-5 pt-10 pb-6">
            {{-- 제목 --}}
            <h1 class="text-4xl font-bold text-gray-900 text-center mb-2 leading-tight">내 피부엔 얼마나 맞을까?</h1>
            <p class="text-base text-gray-500 text-center mb-8">내 피부에 맞는지, 지금 바로 확인해보세요</p>

            {{-- 메인 썸네일 + 성분 카드 --}}
            <div class="relative w-full">
                {{-- 메인 제품 이미지 (main_thumbnail 우선, 없으면 image) --}}
                @php
                    $thumbnailSrc = $product->main_thumbnail
                        ? asset('storage/' . $product->main_thumbnail)
                        : ($product->image ? asset('storage/' . $product->image) : null);
                @endphp
                @if($thumbnailSrc)
                <div class="w-full overflow-hidden rounded-2xl bg-gray-50" style="aspect-ratio: 343 / 395;">
                    <img src="{{ $thumbnailSrc }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                </div>
                @endif

                {{-- 성분 카드 오버레이 --}}
                @foreach($ingredients as $index => $ingredient)
                @php
                    $position = $ingredient->card_position ?? null;
                    // 기본 위치: 카드들을 분산 배치 (관리자와 동일하게 left 기준)
                    $defaultPositions = [
                        ['top' => '10%', 'left' => '0%'],
                        ['top' => '30%', 'left' => '70%'],
                        ['top' => '55%', 'left' => '5%'],
                        ['top' => '75%', 'left' => '65%'],
                        ['top' => '45%', 'left' => '0%'],
                    ];
                    $defaultPos = $defaultPositions[$index % count($defaultPositions)];
                    $top = $position['top'] ?? ($defaultPos['top'] ?? '0%');
                    $left = $position['left'] ?? ($defaultPos['left'] ?? '0%');
                @endphp
                <div class="absolute z-10 w-max"
                     style="top: {{ $top }}; left: {{ $left }};">
                    <div class="flex items-center gap-1.5 rounded-full px-2.5 py-1.5"
                         style="background-color: rgba(255,255,255,0.55); box-shadow: 0 4px 15px rgba(0,0,0,0.12);">
                        <div class="w-5 h-5 bg-black rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 whitespace-nowrap">{{ $ingredient->name }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ==================== 하단 정보 섹션 ==================== --}}
        <div style="background-color: #F4F6F8;" class="mx-5 px-6 py-8 rounded-2xl mt-6">
            <h2 class="text-2xl font-bold text-gray-900 text-center mb-4">개인별 맞춤, 분석 솔루션</h2>
            <p class="text-lg text-gray-600 text-center leading-relaxed">
                개인 설문 응답과<br>
                전 세계 실제 사용자 리뷰 데이터를<br>
                바탕으로 제품의 기대 효과를<br>
                <span style="color: #3182F6; font-weight: 600;">AI가 분석 예측</span>합니다.
            </p>
        </div>

        {{-- 서비스 안내 --}}
        <div class="mx-5 mt-4 mb-5" x-data="{ serviceInfoOpen: false }">
            <button @click="serviceInfoOpen = !serviceInfoOpen"
                    class="flex items-center gap-1 mx-auto text-sm text-gray-500 hover:text-gray-700 transition-colors">
                <span>서비스 안내</span>
                <svg class="w-4 h-4 transition-transform" :class="serviceInfoOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="serviceInfoOpen" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 max-h-0" x-transition:enter-end="opacity-100 max-h-96"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 max-h-96" x-transition:leave-end="opacity-0 max-h-0"
                 class="overflow-hidden">
                <div class="mt-3 p-4 bg-gray-50 rounded-xl text-xs text-gray-500 leading-relaxed">
                    해당 서비스는 개인 설문과 실제 사용자 리뷰 데이터를 바탕으로 AI가 분석·예측한 참고 정보이며, 개인차가 있을 수 있습니다.
                    <br><br>
                    제품 리뷰는 네이버스토어, 쿠팡, 화해, 무신사, W컨셉, 아마존 US, Qoo10 등 10개 이상의 주요 쇼핑 플랫폼에 축적된 실제 사용자 리뷰를 에센시엘의 AI 분석 시스템으로 통합 분석·정량화한 데이터 결과입니다.
                </div>
            </div>
        </div>

        {{-- 하단 버튼 영역 여백 --}}
        <div class="h-24"></div>
    </div>

    {{-- ==================== 하단 고정 버튼 ==================== --}}
    <div class="fixed bottom-0 left-0 right-0 z-40">
        <div class="max-w-lg mx-auto flex gap-0">
            <button @click="showProductDetail = true"
                    class="flex-1 py-5 text-white text-center font-bold text-xl"
                    style="background-color: #000000; border-radius: 16px 0 0 0;">
                AI 리뷰 분석
            </button>
            <a href="{{ route('survey.index', $product->code) }}"
               class="flex-1 py-5 text-white text-center font-bold text-xl"
               style="background-color: #3F78EB; border-radius: 0 16px 0 0;">
                AI 효과 예측
            </a>
        </div>
    </div>

    {{-- ==================== 제품 상세 오버레이 ==================== --}}
    <div x-show="showProductDetail" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[70] bg-white overflow-y-auto">

        {{-- 제품 상세 헤더 --}}
        <div class="bg-black sticky top-0 z-10">
            <div class="max-w-lg mx-auto flex items-center justify-between px-4 py-3">
                <button @click="showProductDetail = false; cleanupDetailCharts()" class="text-white p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <img src="{{ asset('logo_white.png') }}" alt="Essenciel" class="h-5">
                <button @click="overlayMenuOpen = !overlayMenuOpen" class="text-white p-1">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <circle cx="5" cy="12" r="2"/>
                        <circle cx="12" cy="12" r="2"/>
                        <circle cx="19" cy="12" r="2"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- 오버레이 메뉴 드롭다운 --}}
        <div x-show="overlayMenuOpen" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
             @click.away="overlayMenuOpen = false"
             class="fixed top-12 right-2 z-[75] bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden min-w-[180px]">
            @if($product->sales_url)
            <a href="{{ $product->sales_url }}" target="_blank" @click="overlayMenuOpen = false"
               class="w-full text-left px-5 py-3.5 text-sm font-medium text-gray-800 hover:bg-gray-50 flex items-center gap-3 border-b border-gray-100">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                제품 상세
            </a>
            @endif
            <button @click="overlayMenuOpen = false; showProductDetail = false; cleanupDetailCharts(); $nextTick(() => showProductSelector = true)"
                    class="w-full text-left px-5 py-3.5 text-sm font-medium text-gray-800 hover:bg-gray-50 flex items-center gap-3">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                다른 제품 분석
            </button>
        </div>

        <div class="px-4 py-6 max-w-lg mx-auto pb-40">

            {{-- AI 리뷰 분석 섹션 --}}
            <div class="bg-white rounded-2xl overflow-hidden mb-6" style="border: 1px solid #D9D9D9;">
                {{-- 헤더 --}}
                <div class="px-5 pt-4 pb-4 border-b border-gray-100">
                    {{-- 실시간 집계중 표시 (클릭 시 모달 열기) --}}
                    <button @click="showModal = true" class="flex items-center gap-1.5 mb-1 cursor-pointer">
                        <img src="{{ asset('product/realtime_survey.svg') }}" alt="" class="w-3 h-3">
                        <span class="text-xs text-gray-400" x-text="collectionComplete ? '실시간 집계완료' : '실시간 집계중'"></span>
                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900">AI 리뷰 분석</h2>
                    <div class="text-right">
                        <p class="text-sm text-gray-400">분석한 리뷰</p>
                        <p class="text-xl font-bold text-gray-900" x-text="totalCollected.toLocaleString() + ' 개'">0 개</p>
                    </div>
                    </div>
                </div>

                {{-- 구분선 + 평점 영역 --}}
                @php
                    $avgRating = $averageRating ? round($averageRating, 2) : 0;
                    $fullStars = (int) floor($avgRating);
                    $halfStar = ($avgRating - $fullStars) >= 0.5 ? 1 : 0;
                    $emptyStars = 5 - $fullStars - $halfStar;

                    $ratingCounts = [];
                    $maxCount = 0;
                    for ($s = 5; $s >= 1; $s--) {
                        $count = $ratingDistribution[$s] ?? 0;
                        $ratingCounts[$s] = $count;
                        if ($count > $maxCount) $maxCount = $count;
                    }
                    $totalRatings = array_sum($ratingCounts);
                @endphp
                <div class="px-5 py-4">
                    <div class="grid grid-cols-2 gap-0">
                        {{-- 좌측: 총 평점 --}}
                        <div class="text-center flex flex-col items-center border-r border-gray-100 pr-4">
                            <p class="text-sm font-bold text-gray-900 mb-5">사용자 총 평점</p>
                            <p class="text-4xl font-bold text-gray-900">{{ number_format($avgRating, 1) }}<span class="text-base font-normal text-gray-400">점</span></p>
                            <div class="flex items-center justify-center gap-1 mt-2">
                                @for($i = 0; $i < $fullStars; $i++)
                                <svg class="w-7 h-7" fill="#3F78EB" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                @endfor
                                @if($halfStar)
                                <svg class="w-7 h-7" viewBox="0 0 20 20">
                                    <defs><linearGradient id="halfGrad"><stop offset="50%" stop-color="#3F78EB"/><stop offset="50%" stop-color="#D1D5DB"/></linearGradient></defs>
                                    <path fill="url(#halfGrad)" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                @endif
                                @for($i = 0; $i < $emptyStars; $i++)
                                <svg class="w-7 h-7" fill="#D1D5DB" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                @endfor
                            </div>
                        </div>

                        {{-- 우측: 평점 비율 세로 막대 그래프 --}}
                        <div class="pl-4">
                            <p class="text-sm font-bold text-gray-900 mb-5 text-center">평점비율</p>
                            <div class="flex items-end justify-center" style="height: 90px; gap: 12px;">
                                @for($s = 5; $s >= 1; $s--)
                                @php
                                    $count = $ratingCounts[$s];
                                    $pct = $totalRatings > 0 ? ($count / $totalRatings) * 100 : 0;
                                    $isMax = $count === $maxCount && $count > 0;
                                @endphp
                                <div class="flex flex-col items-center relative">
                                    @if($count > 0)
                                    <div class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2">
                                        <div class="relative text-white text-[9px] px-1.5 py-0.5 rounded whitespace-nowrap" style="background-color: {{ $isMax ? '#3F78EB' : '#999999' }};">
                                            {{ number_format($count) }}
                                            <div class="absolute left-1/2 -translate-x-1/2 -bottom-0.5 w-1.5 h-1.5 rotate-45" style="background-color: {{ $isMax ? '#3F78EB' : '#999999' }};"></div>
                                        </div>
                                    </div>
                                    @endif
                                    <div class="rounded-t overflow-hidden bg-gray-200 relative" style="width: 8px; height: 60px;">
                                        <div class="absolute bottom-0 left-0 w-full rounded-t" style="height: {{ $count > 0 ? max($pct, 8) : 0 }}%; background-color: {{ $isMax ? '#3F78EB' : '#999999' }};"></div>
                                    </div>
                                    <span class="text-xs text-gray-400 mt-1">{{ $s }}점</span>
                                </div>
                                @endfor
                            </div>
                        </div>
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
                    <div class="rounded-xl px-6 py-4 bg-gray-100" style="margin-top:24px">
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

                                // **텍스트** 를 볼드 강조로 변환
                                $formatSummary = function($text) {
                                    return preg_replace(
                                        '/\*\*(.+?)\*\*/',
                                        '<strong class="font-bold text-gray-900">$1</strong>',
                                        $text
                                    );
                                };
                            @endphp
                            <div class="space-y-2 text-base text-gray-700 leading-relaxed">
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
                                <div class="w-2 h-2 rounded-full" style="background-color: #ACDDA5;"></div>
                            </template>
                            <span class="text-base font-medium text-white" x-text="collectionComplete ? '실시간 데이터 집계완료' : '실시간 데이터 집계중'"></span>
                        </div>
                        <div class="flex items-center gap-1" style="color: #999999;">
                            <span class="text-sm">상세보기</span>
                            <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </button>
                </div>
            </div>

            {{-- 서비스 안내 --}}
            <div class="mt-6 p-4 bg-gray-50 rounded-xl text-xs text-gray-500 leading-relaxed">
                해당 서비스는 개인 설문과 실제 사용자 리뷰 데이터를 바탕으로 AI가 분석·예측한 참고 정보이며, 개인차가 있을 수 있습니다.
                <br><br>
                제품 리뷰는 네이버스토어, 쿠팡, 화해, 무신사, W컨셉, 아마존 US, Qoo10 등 10개 이상의 주요 쇼핑 플랫폼에 축적된 실제 사용자 리뷰를 에센시엘의 AI 분석 시스템으로 통합 분석·정량화한 데이터 결과입니다.
            </div>
        </div>

        {{-- 하단 고정 UI --}}
        <div class="fixed bottom-0 left-0 right-0 bg-white rounded-t-2xl px-5 py-4 z-20" style="box-shadow: 0 -8px 24px rgba(0, 0, 0, 0.1);">
            <div class="max-w-lg mx-auto">
                <p class="text-center font-bold text-black text-sm mb-3">🤔 나는 얼마나 효과 있을까?</p>
                <a href="{{ route('survey.index', $product->code) }}"
                   class="block w-full py-3.5 text-center text-white font-bold rounded-xl"
                   style="background-color: #3F78EB;">
                    1분 안에 효과 예측
                </a>
            </div>
        </div>
    </div>

    {{-- ==================== 실시간 데이터 수집 모달 ==================== --}}
    <div x-show="showModal" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[80] bg-black/70 flex items-center justify-center p-4"
         @click.self="collectionComplete ? showModal = false : null">
        <div class="bg-slate-900 rounded-2xl p-6 w-full max-w-sm">
            {{-- 수집 중 헤더 --}}
            <div x-show="!collectionComplete" class="text-center mb-6">
                <div class="w-16 h-16 mx-auto mb-4 relative">
                    <div class="absolute inset-0 border-4 rounded-full animate-ping" style="border-color: rgba(63, 120, 235, 0.3)"></div>
                    <div class="absolute inset-2 border-4 rounded-full animate-pulse" style="border-color: rgba(63, 120, 235, 0.5)"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <x-loading-spinner size="8" customColor="#3F78EB" />
                    </div>
                </div>
                <h3 class="text-white font-bold text-lg mb-1">실시간 데이터 수집 중</h3>
                <p class="text-slate-400 text-sm">다양한 플랫폼에서 리뷰를 수집하고 있습니다</p>
            </div>

            {{-- 완료 헤더 --}}
            <div x-show="collectionComplete" class="text-center mb-6">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center" style="background-color: #3F78EB;">
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
                    <div class="flex items-center justify-between text-sm"
                         x-show="!collectionComplete || platform.count > 0">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full"
                                 :class="platform.collected ? '' : 'bg-slate-600 animate-pulse'"
                                 :style="platform.collected ? 'background-color: #3F78EB' : ''"></div>
                            <span class="text-slate-300" x-text="platform.name"></span>
                        </div>
                        <span class="font-mono text-xs" style="color: #3F78EB;" x-text="platform.count.toLocaleString() + '건'"></span>
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
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function productPage() {
    const productCode = '{{ $product->code }}';
    const storageKey = `product_data_collected_${productCode}`;

    // 플랫폼별 실제 DB 리뷰 수
    const dbPlatformCounts = @json($platformReviewCounts);
    const platformKeys = ['naver', 'coupang', 'hwahae', 'musinsa', 'wconcept', 'amazon', 'qoo10', 'shopee'];
    const targetCounts = platformKeys.map(key => dbPlatformCounts[key] ?? 0);
    const totalReviewCount = targetCounts.reduce((a, b) => a + b, 0);

    @php
        $efficacyType = $product->efficacy_type ?? 'moisture';

        // intro_metrics가 있으면 그대로 사용
        if (!empty($product->intro_metrics) && count($product->intro_metrics) > 0) {
            $metricsJson = collect($product->intro_metrics)
                ->filter(fn($m) => !empty($m['name']))
                ->map(fn($m) => [
                    'name' => $m['name'],
                    'value' => min((int)($m['value'] ?? 0), 4),
                ])
                ->values()
                ->toArray();
        }

        if (empty($metricsJson)) {
            $metricsDefaults = [
                'moisture' => [
                    ['name' => '보습력', 'value' => 4],
                    ['name' => '보습지속력', 'value' => 4],
                    ['name' => '끈적임', 'value' => 2],
                    ['name' => '자극여부', 'value' => 1],
                    ['name' => '효과체감', 'value' => 4],
                ],
                'elasticity' => [
                    ['name' => '탄력 개선', 'value' => 4],
                    ['name' => '리프팅감', 'value' => 4],
                    ['name' => '끈적임', 'value' => 2],
                    ['name' => '자극여부', 'value' => 1],
                    ['name' => '효과체감', 'value' => 4],
                ],
                'tone' => [
                    ['name' => '톤 개선', 'value' => 4],
                    ['name' => '화사함', 'value' => 4],
                    ['name' => '끈적임', 'value' => 2],
                    ['name' => '자극여부', 'value' => 1],
                    ['name' => '효과체감', 'value' => 4],
                ],
                'pore' => [
                    ['name' => '모공 축소', 'value' => 4],
                    ['name' => '피지 조절', 'value' => 4],
                    ['name' => '끈적임', 'value' => 2],
                    ['name' => '자극여부', 'value' => 1],
                    ['name' => '효과체감', 'value' => 4],
                ],
                'wrinkle' => [
                    ['name' => '주름 개선', 'value' => 4],
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
    let detailChartsInitialized = false;

    return {
        menuOpen: false,
        overlayMenuOpen: false,
        showProductDetail: false,
        showProductSelector: false,
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
            { name: 'Shopee', count: 0, collected: false },
        ],

        init() {
            // 데이터 수집 시작 (차트는 제품 상세 열 때 초기화)
            this.$nextTick(() => {
                if (localStorage.getItem(storageKey)) {
                    this.showCompletedState();
                } else {
                    this.startDataCollection();
                }
            });

            // 제품 상세 오버레이 열릴 때 차트 초기화
            this.$watch('showProductDetail', (value) => {
                if (value && !detailChartsInitialized) {
                    this.$nextTick(() => {
                        setTimeout(() => {
                            this.initRadarChart();
                            detailChartsInitialized = true;

                            // 레이더 차트 애니메이션
                            if (this.collectionComplete) {
                                this.currentMetricValues = this.metrics.map(() => 0);
                                this.animateRadarChart();
                            }
                        }, 100);
                    });
                }
            });
        },

        cleanupDetailCharts() {
            // 오버레이 닫을 때 차트 정리 (다시 열 때 재초기화)
            if (radarChart) {
                radarChart.destroy();
                radarChart = null;
            }
            detailChartsInitialized = false;
        },

        initRadarChart() {
            const ctx = document.getElementById('radarChart');
            if (!ctx) return;

            const chartCtx = ctx.getContext('2d');
            const centerX = ctx.offsetWidth / 2;
            const centerY = ctx.offsetHeight / 2;
            const radius = Math.min(centerX, centerY) * 0.7;

            const gradient = chartCtx.createRadialGradient(
                centerX, centerY, 0,
                centerX, centerY, radius
            );
            gradient.addColorStop(0, 'rgba(63, 120, 235, 0.9)');
            gradient.addColorStop(0.6, 'rgba(63, 120, 235, 0.5)');
            // gradient.addColorStop(0.9, 'rgba(63, 120, 235, 0.2)');
            gradient.addColorStop(1, 'rgba(255, 255, 255, 0.4)');

            radarChart = new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: this.metrics.map(m => m.name),
                    datasets: [{
                        data: this.currentMetricValues,
                        backgroundColor: gradient,
                        borderColor: '#DDE8FF',
                        borderWidth: 1,
                        pointRadius: 0,
                        pointHoverRadius: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    animation: { duration: 0 },
                    plugins: { legend: { display: false } },
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 4,
                            min: 0,
                            ticks: { stepSize: 1, display: false },
                            grid: { circular: true, color: 'transparent', lineWidth: 0 },
                            angleLines: { color: 'transparent', lineWidth: 0 },
                            pointLabels: { font: { size: 15, weight: '600' }, color: '#374151' },
                            backgroundColor: '#F5F5F5'
                        }
                    }
                },
                plugins: [{
                    id: 'gridUnder',
                    beforeDatasetsDraw: (chart) => {
                        const ctx = chart.ctx;
                        const scale = chart.scales.r;
                        const centerX = scale.xCenter;
                        const centerY = scale.yCenter;
                        const maxRadius = scale.drawingArea;
                        const labelCount = chart.data.labels.length;
                        const maxValue = scale.max;

                        ctx.save();
                        ctx.strokeStyle = '#D9D9D9';
                        ctx.lineWidth = 1;
                        for (let i = 1; i <= maxValue; i++) {
                            const r = (i / maxValue) * maxRadius;
                            ctx.beginPath();
                            ctx.arc(centerX, centerY, r, 0, Math.PI * 2);
                            ctx.stroke();
                        }
                        for (let i = 0; i < labelCount; i++) {
                            const angle = scale.getIndexAngle(i) - Math.PI / 2;
                            const x = centerX + Math.cos(angle) * maxRadius;
                            const y = centerY + Math.sin(angle) * maxRadius;
                            ctx.beginPath();
                            ctx.moveTo(centerX, centerY);
                            ctx.lineTo(x, y);
                            ctx.stroke();
                        }
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
            this.currentMetricValues = this.metrics.map(m => m.value);
        },

        async animateRadarChart() {
            for (let m = 0; m < this.metrics.length; m++) {
                const metric = this.metrics[m];
                for (let v = 0; v <= metric.value; v++) {
                    await this.delay(50);
                    this.currentMetricValues[m] = v;
                    this.updateRadarChart();
                }
                await this.delay(100);
            }
            this.startRadarChartLoop();
        },

        startRadarChartLoop() {
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

        async startDataCollection() {
            this.showModal = false;
            this.collectionComplete = false;
            this.totalCollected = 0;
            this.platforms.forEach(p => { p.count = 0; p.collected = false; });
            this.currentMetricValues = this.metrics.map(() => 0);

            for (let i = 0; i < this.platforms.length; i++) {
                await this.delay(150 + Math.random() * 100);
                const target = targetCounts[i];
                await this.animateCount(i, target);
                this.platforms[i].collected = true;
            }

            await this.delay(200);
            this.collectionComplete = true;
            localStorage.setItem(storageKey, 'true');
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

</script>
@endpush
