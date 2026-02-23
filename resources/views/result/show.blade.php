@extends('layouts.app')

@section('title', '나의 피부 분석 결과 - ' . $product->name)

@php
    // 조사 처리 함수들
    $hasFinalConsonant = function($word) {
        $lastChar = mb_substr($word, -1);
        $code = mb_ord($lastChar) - 0xAC00;
        if ($code < 0 || $code > 11171) return true;
        return ($code % 28) > 0;
    };

    $josa = function($word, $with, $without) use ($hasFinalConsonant) {
        return $hasFinalConsonant($word) ? $with : $without;
    };

    $eunNeun = fn($word) => $josa($word, '은', '는');
    $iGa = fn($word) => $josa($word, '이', '가');
    $eulReul = fn($word) => $josa($word, '을', '를');

    // {중괄호} 텍스트를 파란색 볼드 강조로 변환
    $blueHighlight = function($text) {
        return preg_replace('/\{(.+?)\}/', '<span style="color: #3F78EB; font-weight: 700;">$1</span>', e($text));
    };

    $efficacyNames = \App\Models\Product::$efficacyTypes;
    $efficacyType = $result->metrics['efficacy_type'] ?? 'moisture';
    $efficacyName = $efficacyNames[$efficacyType] ?? '수분 공급';

    $pointColor = $product->point_color ?? '#10B981';
    $accentColor = $product->accent_color; // 제품에 설정된 강조 컬러
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

    // 흰색에 포인트컬러 15% 섞은 연한 색상
    $lightTintR = round(255 * 0.85 + $rgb[0] * 0.15);
    $lightTintG = round(255 * 0.85 + $rgb[1] * 0.15);
    $lightTintB = round(255 * 0.85 + $rgb[2] * 0.15);
    $lightTintColor = "rgb($lightTintR, $lightTintG, $lightTintB)";

    // 강조 컬러 사용 (제품에 설정된 값이 있으면 사용, 없으면 자동 계산)
    if ($accentColor) {
        $darkerPointColor = $accentColor;
        $textPointColor = $accentColor;
    } else {
        // 포인트컬러 기반 진한 색상 계산 (주 색상 강조, 채도 높임)
        // 게이지 그라데이션용 약간 어두운 색상 (끝부분이 너무 어둡지 않게)
        $darkenColor = function($rgb) {
            $maxVal = max($rgb[0], $rgb[1], $rgb[2]);
            $minVal = min($rgb[0], $rgb[1], $rgb[2]);

            return array_map(function($val) use ($maxVal, $minVal) {
                if ($val == $maxVal) {
                    return max(0, round($val * 0.92));  // 주 색상 유지 (0.88 -> 0.92)
                } elseif ($val == $minVal) {
                    return max(0, round($val * 0.88));  // 보조 색상 (0.82 -> 0.88)
                } else {
                    return max(0, round($val * 0.78));  // 중간값 (0.62 -> 0.78)
                }
            }, $rgb);
        };
        $darkerRgb = $darkenColor($rgb);
        $darkerPointColor = sprintf('#%02x%02x%02x', $darkerRgb[0], $darkerRgb[1], $darkerRgb[2]);

        // 텍스트용 진한 색상 - 포인트 컬러의 색상비를 유지하면서 어둡게
        // 각 채널을 동일 비율(0.6)로 줄여서 원래 색조 유지
        $textDarkerRgb = [
            max(0, round($rgb[0] * 0.55)),  // R
            max(0, round($rgb[1] * 0.55)),  // G
            max(0, round($rgb[2] * 0.55)),  // B
        ];
        $textPointColor = sprintf('#%02x%02x%02x', $textDarkerRgb[0], $textDarkerRgb[1], $textDarkerRgb[2]);
    }

    $improvementPercent = round($result->metrics['change_percent'] ?? 0, 1);
    $milestoneLabels = $product->getEfficacyMilestoneLabels();
    $milestoneCenterTexts = $product->getMilestoneCenterTexts();
    $descriptions = $product->getEfficacyPhaseDescriptions();

    // 피부 프로파일 데이터
    $skinProfile = $result->skin_profile ?? [];
    $characteristics = $skinProfile['characteristics'] ?? [];

    // 특성별 레벨 및 설명 (1-5 스케일) - level 기반으로 description 생성
    $regenerationLevel = $characteristics['regeneration']['level'] ?? 3;
    $moistureLevel = $characteristics['moisture_retention']['level'] ?? 3;
    $pigmentLevel = $characteristics['pigment_reactivity']['level'] ?? 3;
    $sensitivityLevel = $characteristics['sensitivity']['level'] ?? 3;

    $profileData = [
        'regeneration' => [
            'label' => '피부 재생 속도',
            'level' => $regenerationLevel,
            'description' => match($regenerationLevel) {
                5 => '매우 빠른 편',
                4 => '빠른 편',
                3 => '보통',
                2 => '느린 편',
                default => '매우 느린 편',
            },
        ],
        'moisture_retention' => [
            'label' => '피부 수분 유지력',
            'level' => $moistureLevel,
            'description' => match($moistureLevel) {
                5 => '매우 많은 편',
                4 => '많은 편',
                3 => '보통',
                2 => '적은 편',
                default => '매우 적은 편',
            },
        ],
        'pigment_reactivity' => [
            'label' => '피부 색소 반응성',
            'level' => $pigmentLevel,
            'description' => match($pigmentLevel) {
                5 => '매우 높은 편',
                4 => '높은 편',
                3 => '보통',
                2 => '낮은 편',
                default => '매우 낮은 편',
            },
        ],
    ];

    // 피부 설명 텍스트 생성 (결과 상단용) - 느낌 기반 문구
    // 1번 문구: 수분 유지력 기반 증상
    $skinDescText1 = match(true) {
        $moistureLevel <= 2 => '속당김',
        $moistureLevel >= 4 => '촉촉함',
        default => '가벼운 건조함',
    };

    // 2번 문구: 민감도 수준
    $skinDescText2 = match(true) {
        $sensitivityLevel >= 4 => '높은',
        $sensitivityLevel <= 2 => '낮은',
        default => '보통인',
    };

    // 3번 문구: 효능 타입 기반 피부 타입
    $skinTypeText = match($efficacyType) {
        'moisture' => '건조 피부',
        'elasticity' => '탄력저하 피부',
        'tone' => '칙칙한 피부',
        'pore' => '모공성 피부',
        'soothing' => '민감성 피부',
        default => '복합성 피부',
    };
@endphp

@section('content')
<div x-data="resultPage()" x-cloak class="min-h-screen bg-white">
    {{-- 로딩 오버레이 --}}
    <div x-show="isLoading"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[100] bg-white flex items-center justify-center">
        <div class="text-center">
            {{-- 로딩 애니메이션 --}}
            <div class="relative w-24 h-24 mx-auto mb-6">
                <div class="absolute inset-0 border-4 border-gray-200 rounded-full"></div>
                <div class="absolute inset-0 border-4 border-black rounded-full border-t-transparent animate-spin"></div>
            </div>
            <p class="text-gray-600 font-medium">분석 결과를 불러오는 중...</p>
        </div>
    </div>

    <div x-show="!isLoading">
        <x-top-header :product="$product" :other-products="$otherProducts ?? collect()" />
    </div>

    {{-- 분석 완료 섹션 --}}
    <div x-show="!isLoading" class="text-center pt-10 pb-6 bg-white">
        <div class="max-w-[375px] mx-auto px-5">
            {{-- 파란 원형 체크 아이콘 --}}
            <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center" style="background-color: #3F78EB;">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900 mb-6">분석 완료되었습니다.</h2>

            {{-- 피부 설명 (회색 박스) --}}
            <div class="bg-gray-50 rounded-xl px-5 py-5 text-center">
                <p class="text-base text-gray-700 leading-relaxed font-semibold">
                    당신의 피부는<br>
                    <span style="color: #3F78EB;">{{ $skinDescText1 }}</span>{{ $iGa($skinDescText1) }} 느껴지고,<br>
                    <span style="color: #3F78EB;">민감도</span>가 {{ $skinDescText2 }} <span style="color: #3F78EB;">{{ $skinTypeText }}</span>입니다.
                </p>
            </div>
        </div>
    </div>

    {{-- 탭 메뉴 --}}
    <div x-show="!isLoading" class="sticky top-[52px] z-40 bg-white">
        <div class="flex max-w-lg mx-auto border-b border-gray-200">
            <button @click="scrollToSection('analysis')"
                    :class="activeTab === 'analysis' ? 'text-black border-black' : 'text-gray-400 border-transparent'"
                    class="flex-1 py-3 text-base font-medium border-b-2 transition-colors">피부 분석</button>
            <button @click="scrollToSection('prediction')"
                    :class="activeTab === 'prediction' ? 'text-black border-black' : 'text-gray-400 border-transparent'"
                    class="flex-1 py-3 text-base font-medium border-b-2 transition-colors">효과 예측</button>
            <button @click="scrollToSection('guide')"
                    :class="activeTab === 'guide' ? 'text-black border-black' : 'text-gray-400 border-transparent'"
                    class="flex-1 py-3 text-base font-medium border-b-2 transition-colors">사용 가이드</button>
        </div>
    </div>

    <div x-show="!isLoading" class="px-5 py-8 max-w-lg mx-auto pb-52">
        {{-- ===== 탭 1: 피부 분석 ===== --}}
        <div id="section-analysis" class="pt-2">
        {{-- 1. 나의 피부 분석 --}}
        <h2 class="text-2xl font-bold text-gray-900 mb-6">나의 피부 분석</h2>

        <div class="space-y-4">
            @foreach($profileData as $key => $data)
            @php
                $level = $data['level'];
                if ($level >= 4) {
                    $badgeBg = '#E8F0FE'; $badgeColor = '#3F78EB';
                    $badgeEmoji = '👍'; $badgeText = 'GOOD';
                } elseif ($level >= 3) {
                    $badgeBg = '#F0F0F0'; $badgeColor = '#888888';
                    $badgeEmoji = '≈'; $badgeText = 'NORMAL';
                } else {
                    $badgeBg = '#FEE8E8'; $badgeColor = '#E05050';
                    $badgeEmoji = '⚠️'; $badgeText = 'CARE';
                }
                $descHighlight = match(true) {
                    $level >= 4 => '평균 이상',
                    $level >= 3 => '평균',
                    default => '평균 이하',
                };
                $gaugeLabels = [
                    'regeneration' => ['느림', '보통', '빠름'],
                    'moisture_retention' => ['낮음', '보통', '높음'],
                    'pigment_reactivity' => ['낮음', '보통', '높음'],
                ];
                $labels = $gaugeLabels[$key] ?? ['낮음', '보통', '높음'];
            @endphp
            <div class="rounded-2xl p-5" style="background-color: #F5F6F8;"
                 x-data="profileGauge({{ $loop->index }}, {{ ($level / 5) * 100 }})" x-init="startAnimation()">
                {{-- 제목 + 뱃지 --}}
                <div class="flex items-center gap-2 mb-2">
                    <h3 class="text-lg font-bold text-gray-900">{{ $data['label'] }}</h3>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold"
                          style="background-color: {{ $badgeBg }}; color: {{ $badgeColor }};">
                        {{ $badgeEmoji }} {{ $badgeText }}
                    </span>
                </div>
                {{-- 설명 텍스트 --}}
                <p class="text-sm text-gray-500 mb-4">
                    {{ $data['label'] }}{{ $iGa($data['label']) }} <span style="color: #3F78EB;">{{ $descHighlight }}</span>입니다.
                </p>
                {{-- 게이지 바 --}}
                <div class="relative rounded-full overflow-hidden" style="background-color: #E0E2E8; height: 8px;">
                    <div class="absolute top-0 left-0 h-full rounded-full transition-all duration-1000 ease-out"
                         :style="'width: ' + currentWidth + '%; background-color: #3F78EB'">
                    </div>
                </div>
                {{-- 하단 라벨 --}}
                <div class="flex justify-between mt-2 px-1">
                    <span class="text-xs text-gray-400">{{ $labels[0] }}</span>
                    <span class="text-xs text-gray-400">{{ $labels[1] }}</span>
                    <span class="text-xs text-gray-400">{{ $labels[2] }}</span>
                </div>
            </div>
            @endforeach
        </div>

        </div>

        {{-- 섹션 구분선 --}}
        <div class="border-t border-gray-200 my-8"></div>

        {{-- ===== 탭 2: 효과 예측 ===== --}}
        <div id="section-prediction" class="pt-6">
        {{-- 2. 효능 발현 예측 --}}
        <div class="mb-10">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">효능 발현 예측</h2>

            {{-- 원형 틱 게이지 애니메이션 --}}
            <div class="relative w-60 h-60 mx-auto mb-8">
                {{-- 원형 틱 마크 (6시부터 시계방향, 72개) --}}
                <svg class="absolute inset-0 w-full h-full" viewBox="0 0 200 200" style="z-index: 1;">
                    @php
                        $totalTicks = 72;
                        $outerRadius = 98;
                        $tickLength = 14;
                    @endphp
                    @for($i = 0; $i < $totalTicks; $i++)
                    @php
                        $angle = deg2rad(($i * 360 / $totalTicks) + 90);
                        $x1 = 100 + ($outerRadius - $tickLength) * cos($angle);
                        $y1 = 100 + ($outerRadius - $tickLength) * sin($angle);
                        $x2 = 100 + $outerRadius * cos($angle);
                        $y2 = 100 + $outerRadius * sin($angle);
                    @endphp
                    <line
                        x1="{{ round($x1, 2) }}" y1="{{ round($y1, 2) }}"
                        x2="{{ round($x2, 2) }}" y2="{{ round($y2, 2) }}"
                        stroke-width="1.5"
                        stroke-linecap="round"
                        class="tick-mark"
                        data-index="{{ $i }}"
                        stroke="#D9D9D9"/>
                    @endfor
                </svg>

                {{-- 중앙 텍스트 --}}
                <div class="absolute inset-0 flex items-center justify-center" style="z-index: 2;">
                    <div class="w-36 h-36 rounded-full bg-white flex flex-col items-center justify-center text-center">
                        <span class="text-sm text-gray-500 mb-1">한 달 사용 후</span>
                        <span class="text-xl font-bold text-gray-900">{{ $efficacyName }}</span>
                        <span class="text-2xl font-bold text-gray-900">{{ $improvementPercent }}% 개선</span>
                    </div>
                </div>
            </div>

            {{-- 설명 텍스트 (회색 배경) --}}
            <div class="rounded-xl p-5" style="background-color: #F5F6F8;">
                <p class="text-sm leading-relaxed text-gray-500">
                    고객님이 <span class="font-bold text-black">{{ $product->name }}</span>{{ $eulReul($product->name) }}
                    꾸준히 사용할 경우 <span class="font-bold" style="color: #3F7BEB;">한 달 뒤 {{ $efficacyName }} {{ $improvementPercent }}% 개선</span>될 것으로 예측됩니다.
                </p>
            </div>
        </div>

        {{-- 마일스톤 카드 (한눈에 보기) --}}
        @php
            $milestoneIcons = [
                'moisture' => '💧',
                'elasticity' => '🔬',
                'tone' => '✨',
                'pore' => '🔍',
                'soothing' => '🌿',
            ];
            $icon1 = $milestoneIcons[$efficacyType] ?? '💧';
            $icon2 = '⚖️';
        @endphp
        <div class="grid grid-cols-2 gap-3 mb-10">
            <div class="rounded-2xl p-5 text-center bg-white" style="border: 1px solid #D9D9D9;">
                <div class="text-3xl mb-3">{{ $icon1 }}</div>
                <p class="text-xs text-gray-400 mb-1">7-10일 사용 시</p>
                <p class="text-sm font-bold text-gray-900">{!! nl2br(e($milestoneLabels[0] ?? '보습 개선 체감')) !!}</p>
            </div>
            <div class="rounded-2xl p-5 text-center bg-white" style="border: 1px solid #D9D9D9;">
                <div class="text-3xl mb-3">{{ $icon2 }}</div>
                <p class="text-xs text-gray-400 mb-1">21-28일 사용 시</p>
                <p class="text-sm font-bold text-gray-900">{!! nl2br(e($milestoneLabels[1] ?? '수분 밸런스 안정화')) !!}</p>
            </div>
        </div>

        {{-- 3. 단계별 효과 --}}
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">단계별 효과</h2>

            {{-- 그래프 영역 --}}
            <div class="rounded-xl p-4 mb-6" style="background-color: #F6F6F6;">
                <div class="h-48">
                    <canvas id="efficacyPhaseChart"></canvas>
                </div>
            </div>

            {{-- 타임라인 + 버튼 + 설명 --}}
            <div class="space-y-0" x-data="timelineAnimation()" x-init="startAnimation()">
                {{-- Phase 1: D0-5 --}}
                <div class="flex items-start gap-5">
                    <div class="flex flex-col items-center flex-shrink-0" style="width: 20px;">
                        <div class="w-5 h-5 rounded-full flex items-center justify-center" style="background-color: #3F7BEB;">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="relative w-full flex justify-center mt-2" style="height: 80px;">
                            <div class="absolute inset-y-0 border-l-2 border-solid" style="border-color: #000000;"></div>
                            <div class="absolute top-0 w-0.5 transition-all duration-700 ease-out" style="background-color: #3F7BEB;"
                                 :style="'height: ' + line1Height + '%'"></div>
                        </div>
                    </div>
                    <div class="flex-1 pb-10">
                        <button class="px-4 py-2 text-base font-bold rounded-lg mb-3 text-gray-900" style="background-color: #EEEEEE;">
                            ~5일 사용 시
                        </button>
                        <p class="text-base text-gray-700 leading-relaxed">{!! $blueHighlight($descriptions['phase1']) !!}</p>
                    </div>
                </div>

                {{-- Phase 2: 7~10일 --}}
                <div class="flex items-start gap-5">
                    <div class="flex flex-col items-center flex-shrink-0" style="width: 20px;">
                        <div class="w-5 h-5 rounded-full flex items-center justify-center" style="background-color: #3F7BEB;">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="relative w-full flex justify-center mt-2" style="height: 80px;">
                            <div class="absolute inset-y-0 border-l-2 border-solid" style="border-color: #000000;"></div>
                            <div class="absolute top-0 w-0.5 transition-all duration-700 ease-out" style="background-color: #3F7BEB;"
                                 :style="'height: ' + line2Height + '%'"></div>
                        </div>
                    </div>
                    <div class="flex-1 pb-10">
                        <button class="px-4 py-2 text-base font-bold rounded-lg mb-3 text-gray-900" style="background-color: #EEEEEE;">
                            7~10일 사용 시
                        </button>
                        <p class="text-base text-gray-700 leading-relaxed">{!! $blueHighlight($descriptions['phase2']) !!}</p>
                    </div>
                </div>

                {{-- Phase 3: 21~28일 --}}
                <div class="flex items-start gap-5">
                    <div class="flex flex-col items-center flex-shrink-0" style="width: 20px;">
                        <div class="w-5 h-5 rounded-full flex items-center justify-center" style="background-color: #3F7BEB;">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="relative w-full flex justify-center mt-2" style="height: 80px;">
                            <div class="absolute inset-y-0 border-l-2 border-solid" style="border-color: #000000;"></div>
                            <div class="absolute top-0 w-0.5 transition-all duration-700 ease-out" style="background-color: #3F7BEB;"
                                 :style="'height: ' + line3Height + '%'"></div>
                        </div>
                    </div>
                    <div class="flex-1 pb-10">
                        <button class="px-4 py-2 text-base font-bold rounded-lg mb-3 text-gray-900" style="background-color: #EEEEEE;">
                            21~28일 사용 시
                        </button>
                        <p class="text-base text-gray-700 leading-relaxed">{!! $blueHighlight($descriptions['phase3']) !!}</p>
                    </div>
                </div>
            </div>
        </div>

        </div>

        {{-- 섹션 구분선 --}}
        <div class="border-t border-gray-200 my-8"></div>

        {{-- ===== 탭 3: 사용 가이드 ===== --}}
        <div id="section-guide" class="pt-6">
        {{-- 5. 최적 사용 시간 --}}
        <div class="mb-8">
            {{-- 최적 사용 시간 --}}
            @php
                $optimalUsage = $result->usage_guide['optimal_usage'] ?? [];
                $morningEffect = $optimalUsage['timing']['morning_effect'] ?? 100;
                $eveningEffect = $optimalUsage['timing']['evening_effect'] ?? 100;
                $timingReason = $optimalUsage['timing']['reason'] ?? '피부 재생이 활발한 시간대';
                $bestTime = $optimalUsage['best_time'] ?? '저녁';
                $bestEffect = max($morningEffect, $eveningEffect);
                $isMorningGood = $morningEffect > 100;
                $isEveningGood = $eveningEffect > 100;
            @endphp
            <div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4">최적 사용 시간</h3>

                {{-- 아침/저녁 효과 카드 --}}
                <div class="grid grid-cols-2 gap-3">
                    {{-- 아침 효과 --}}
                    <div>
                        @if($isMorningGood)
                            <div class="relative rounded-xl p-[3px]" style="background: linear-gradient(to bottom left, #4C5CEB 0%, #000000 51%, #3F78EB 100%);">
                                <div class="bg-white rounded-[9px] p-4 text-center">
                                    <div class="text-3xl mb-2">🌤️</div>
                                    <p class="text-sm text-gray-500 mb-1">아침 효과</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ $morningEffect }}%</p>
                                </div>
                            </div>
                            <div class="flex justify-center mt-3">
                                <div class="relative bg-black text-white text-xs font-bold px-3 py-1.5 rounded-lg">
                                    <div class="absolute -top-2 left-1/2 -translate-x-1/2 w-0 h-0" style="border-left: 6px solid transparent; border-right: 6px solid transparent; border-bottom: 8px solid black;"></div>
                                    사용추천
                                </div>
                            </div>
                        @else
                            <div class="bg-white rounded-xl p-4 text-center" style="border: 1px solid #E5E5E5;">
                                <div class="text-3xl mb-2">🌤️</div>
                                <p class="text-sm text-gray-500 mb-1">아침 효과</p>
                                <p class="text-2xl font-bold text-gray-900">{{ $morningEffect }}%</p>
                            </div>
                        @endif
                    </div>
                    {{-- 저녁 효과 --}}
                    <div>
                        @if($isEveningGood)
                            <div class="relative rounded-xl p-[3px]" style="background: linear-gradient(to bottom left, #4C5CEB 0%, #000000 51%, #3F78EB 100%);">
                                <div class="bg-white rounded-[9px] p-4 text-center">
                                    <div class="text-3xl mb-2">🌙</div>
                                    <p class="text-sm text-gray-500 mb-1">저녁 효과</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ $eveningEffect }}%</p>
                                </div>
                            </div>
                            <div class="flex justify-center mt-3">
                                <div class="relative bg-black text-white text-xs font-bold px-3 py-1.5 rounded-lg">
                                    <div class="absolute -top-2 left-1/2 -translate-x-1/2 w-0 h-0" style="border-left: 6px solid transparent; border-right: 6px solid transparent; border-bottom: 8px solid black;"></div>
                                    사용추천
                                </div>
                            </div>
                        @else
                            <div class="bg-white rounded-xl p-4 text-center" style="border: 1px solid #E5E5E5;">
                                <div class="text-3xl mb-2">🌙</div>
                                <p class="text-sm text-gray-500 mb-1">저녁 효과</p>
                                <p class="text-2xl font-bold text-gray-900">{{ $eveningEffect }}%</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- 설명 문구 --}}
                <div class="rounded-xl px-5 py-4 mt-3" style="background-color: #F5F6F8;">
                    <p class="text-sm text-gray-700 leading-relaxed">
                        @if($bestEffect > 100)
                            <span style="color: #3F78EB; font-weight: 700;">{{ $bestTime }}</span>에 사용하면 <span style="color: #3F78EB; font-weight: 700;">효과가 {{ $bestEffect }}%</span>로, {!! $blueHighlight($timingReason) !!}
                        @else
                            {!! $blueHighlight($timingReason) !!}
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- 6. 건강한 피부습관 --}}
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">건강한 피부습관</h2>

            @php
                $recommendations = array_slice($result->usage_guide['recommendations'] ?? [], 0, 3); // 최대 3개
                $boostImages = ['boost_line', 'boost_bar'];
                $fasterImages = ['faster_timeline', 'faster_clock'];
                $recCount = count($recommendations);

                // 이미지 할당 계획 수립
                // 1개: 단축/향상 랜덤 1개
                // 2개: 향상 1개, 단축 1개
                // 3개: 향상 1개, 단축 1개, 랜덤 1개
                $assignedImages = [];
                $randomSeed = crc32($result->session_id ?? 'default'); // 세션별 일관된 랜덤

                if ($recCount == 1) {
                    // 1개: 랜덤 선택
                    $allImages = array_merge($boostImages, $fasterImages);
                    $assignedImages[] = $allImages[$randomSeed % 4];
                } elseif ($recCount == 2) {
                    // 2개: 향상 1개, 단축 1개
                    $assignedImages[] = $boostImages[$randomSeed % 2];
                    $assignedImages[] = $fasterImages[$randomSeed % 2];
                } elseif ($recCount >= 3) {
                    // 3개: 향상 1개, 단축 1개, 나머지에서 랜덤 1개
                    $usedBoostIdx = $randomSeed % 2;
                    $usedFasterIdx = $randomSeed % 2;
                    $assignedImages[] = $boostImages[$usedBoostIdx];
                    $assignedImages[] = $fasterImages[$usedFasterIdx];
                    // 3번째: 사용하지 않은 이미지 중 랜덤
                    $remainingImages = [$boostImages[1 - $usedBoostIdx], $fasterImages[1 - $usedFasterIdx]];
                    $assignedImages[] = $remainingImages[$randomSeed % 2];
                }
            @endphp

            @if(count($recommendations) > 0)
            <div class="space-y-4">
                @foreach($recommendations as $index => $rec)
                    @php
                        $effectBoost = $rec['effect_boost'] ?? 0;
                        $daysSaved = $rec['days_saved'] ?? 0;
                        $actionShort = $rec['action_short'] ?? '';
                        $icon = $rec['icon'] ?? '✨';

                        // 표시 타입 강제 할당
                        // 1개: 데이터 기반, 2개: 첫번째=향상, 두번째=단축, 3개: 향상, 단축, 데이터 기반
                        if ($recCount == 1) {
                            $isBoostType = ($effectBoost >= $daysSaved * 8);
                        } elseif ($recCount == 2) {
                            $isBoostType = ($index == 0); // 첫번째=향상, 두번째=단축
                        } else {
                            // 3개: 첫번째=향상, 두번째=단축, 세번째=데이터 기반
                            if ($index == 0) {
                                $isBoostType = true;
                            } elseif ($index == 1) {
                                $isBoostType = false;
                            } else {
                                $isBoostType = ($effectBoost >= $daysSaved * 8);
                            }
                        }

                        // 이미지 타입: 미리 계산된 할당 사용
                        $imageType = $assignedImages[$index] ?? 'boost_line';
                    @endphp

                    <div>
                        <div class="bg-white rounded-2xl p-3 h-[200px] overflow-hidden relative" style="border: 1px solid #D9D9D9;">
                            {{-- 라벨 (카드 오른쪽 상단, 이미지 영역 위) --}}
                            @if($isBoostType)
                                <div class="absolute top-12 right-16 bg-black text-white text-xs font-bold px-3 py-1.5 rounded-md z-20">
                                    <div class="leading-tight text-center">효과 향상</div>
                                    <div class="text-[7px] font-normal text-gray-400 text-center">Improved effectiveness</div>
                                </div>
                            @else
                                <div class="absolute top-12 right-16 bg-black text-white text-xs font-bold px-3 py-1.5 rounded-md z-20">
                                    <div class="leading-tight text-center">효능 도달</div>
                                    <div class="text-[7px] font-normal text-gray-400 text-center">Time to results</div>
                                </div>
                            @endif
                            <div class="inline-flex items-center gap-2 mb-4 relative z-10 backdrop-blur-sm rounded-full px-2 py-0.5" style="background: linear-gradient(to right, rgba(255,255,255,0.8), rgba(255,255,255,0.2));">
                                <span class="text-2xl">{{ $icon }}</span>
                                <span class="font-bold text-lg text-gray-900">{{ $actionShort }}</span>
                            </div>
                            {{-- 텍스트 영역 (카드 왼쪽 하단) --}}
                            <div class="absolute bottom-4 left-6 z-10">
                                @if($isBoostType)
                                    <p class="text-3xl font-bold text-gray-900 mb-4">{{ $effectBoost }}% 향상</p>
                                    <div class="flex items-center gap-2 text-sm text-gray-500">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                            </svg>
                                        </span>
                                        <span>Boost</span>
                                    </div>
                                @else
                                    <p class="text-3xl font-bold text-gray-900 mb-4">{{ $daysSaved }}일 단축</p>
                                    <div class="flex items-center gap-2 text-sm text-gray-500">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                            </svg>
                                        </span>
                                        <span>Faster</span>
                                    </div>
                                @endif
                            </div>

                            {{-- 이미지 영역 (카드 오른쪽) --}}
                            <div class="absolute bottom-4 right-4 flex justify-end z-0 {{ $imageType === 'faster_clock' ? 'w-[55%]' : 'w-[50%]' }}">
                                @if($imageType === 'boost_line')
                                    <img src="/images/effects/향상2.png" alt="효과 향상" class="w-full h-auto object-contain">
                                @elseif($imageType === 'boost_bar')
                                    <img src="/images/effects/향상1.png" alt="효과 향상" class="w-full h-auto object-contain">
                                @elseif($imageType === 'faster_timeline')
                                    <img src="/images/effects/단축1.png" alt="효능 도달" class="w-full h-auto object-contain -translate-y-10">
                                @elseif($imageType === 'faster_clock')
                                    <img src="/images/effects/단축2.png" alt="효능 도달" class="w-full h-auto object-contain translate-y-4">
                                @endif
                            </div>
                        </div>
                        <div class="rounded-xl px-6 py-3 mt-2 mb-10" style="background-color: #F5F6F8;">
                            <p class="text-sm text-gray-700">
                                @if($isBoostType)
                                    <span class="text-gray-700">{{ $actionShort }}{{ preg_match('/[를을]$/', $actionShort) ? '' : (preg_match('/[가-힣]/', mb_substr($actionShort, -1)) && in_array(mb_ord(mb_substr($actionShort, -1)) % 28, [0]) ? '를' : '을') }} 할 경우</span> <span style="color: #3F78EB;">효과가 최대 {{ $effectBoost }}% 향상</span>될 것으로 예상됩니다.
                                @else
                                    <span class="text-gray-700">{{ $actionShort }}{{ preg_match('/[를을]$/', $actionShort) ? '' : (preg_match('/[가-힣]/', mb_substr($actionShort, -1)) && in_array(mb_ord(mb_substr($actionShort, -1)) % 28, [0]) ? '를' : '을') }} 할 경우</span> <span style="color: #3F78EB;">효능 도달시점이 최대 {{ $daysSaved }}일 단축</span>될 것으로 예상됩니다.
                                @endif
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
            @else
            <p class="text-gray-500 text-center py-4">현재 생활 습관이 최적 상태입니다.</p>
            @endif
        </div>

        </div>

        {{-- 결과 공유하기 (모든 탭 공통) --}}
        <div class="bg-white rounded-2xl mb-8 p-5" style="border: 1px solid #D9D9D9;">
            <h3 class="text-lg font-bold text-gray-900 mb-4">결과 공유하기</h3>
            <div class="grid grid-cols-2 gap-3">
                <button onclick="shareKakao()" class="py-3 rounded-lg font-semibold text-gray-900" style="background-color: #FDC700;">
                    카카오톡
                </button>
                <button onclick="copyLink()" class="py-3 rounded-lg font-semibold text-gray-700 bg-white" style="border: 1px solid #D9D9D9;">
                    링크 복사
                </button>
            </div>
        </div>

        {{-- 서비스 안내 (클릭하면 펼침) --}}
        <div class="mb-8" x-data="{ serviceInfoOpen: false }">
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
    </div>

    {{-- 하단 고정 UI --}}
    <div x-show="!isLoading" class="fixed bottom-0 left-0 right-0 bg-white rounded-t-2xl px-5 py-4 z-50" style="box-shadow: 0 -8px 24px rgba(0, 0, 0, 0.1);">
        <div class="max-w-lg mx-auto">
            <p class="text-center font-bold text-black text-sm mb-3">📊 AI 분석 기반 추천</p>
            <a href="{{ $product->sales_url ?: route('product.show', $product->code) }}" {{ $product->sales_url ? 'target="_blank"' : '' }}
               class="block w-full py-3.5 text-center text-white font-bold rounded-xl"
               style="background-color: #3F78EB;">
                나에게 딱 맞는 제품 보기
            </a>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }

    /* 마퀴 애니메이션 (1.5개 보이면서 무한 롤링) */
    .marquee-container {
        mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);
        -webkit-mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);
    }
    .marquee-track {
        display: flex;
        width: max-content;
        animation: marquee 12s linear infinite;
    }
    .marquee-text {
        padding-right: 3rem;
        white-space: nowrap;
    }
    @keyframes marquee {
        0% { transform: translateX(0); }
        100% { transform: translateX(-33.333%); }
    }


</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
function resultPage() {
    return {
        isLoading: true,
        activeTab: 'analysis',
        menuOpen: false,
        showProductSelector: false,
        isScrolling: false,

        init() {
            // 로딩 완료 후 컨텐츠 표시
            setTimeout(() => {
                this.isLoading = false;

                // 로딩 해제 후 애니메이션 시작
                setTimeout(() => {
                    this.animateTickGauge();
                    this.initScrollObserver();
                }, 300);
            }, 800);
        },

        scrollToSection(tab) {
            const el = document.getElementById('section-' + tab);
            if (!el) return;
            this.isScrolling = true;
            this.activeTab = tab;
            // 헤더(52px) + 탭바(약 49px) = 약 101px 오프셋
            const offset = 105;
            const top = el.getBoundingClientRect().top + window.pageYOffset - offset;
            window.scrollTo({ top: top, behavior: 'smooth' });
            // 스크롤 완료 후 옵저버 재활성화
            setTimeout(() => { this.isScrolling = false; }, 800);
        },

        initScrollObserver() {
            const sections = ['analysis', 'prediction', 'guide'];
            const observer = new IntersectionObserver((entries) => {
                if (this.isScrolling) return;
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const id = entry.target.id.replace('section-', '');
                        this.activeTab = id;
                    }
                });
            }, {
                rootMargin: '-120px 0px -60% 0px',
                threshold: 0
            });
            sections.forEach(s => {
                const el = document.getElementById('section-' + s);
                if (el) observer.observe(el);
            });
        },

        animateTickGauge() {
            const ticks = document.querySelectorAll('.tick-mark');
            const totalTicks = ticks.length; // 72개
            const tickDelay = 25; // 틱당 딜레이 (ms)
            const animationDuration = totalTicks * tickDelay;

            const runAnimation = () => {
                // 먼저 모든 틱을 초기 색상으로 리셋
                ticks.forEach(tick => {
                    tick.style.transition = 'none';
                    tick.setAttribute('stroke', '#D9D9D9');
                });

                // 약간의 딜레이 후 순차적으로 #3F78EB로 채우기
                setTimeout(() => {
                    ticks.forEach((tick, index) => {
                        setTimeout(() => {
                            tick.style.transition = 'stroke 0.15s ease-out';
                            tick.setAttribute('stroke', '#3F78EB');
                        }, index * tickDelay);
                    });
                }, 100);
            };

            // 첫 애니메이션 실행
            runAnimation();

            // 완료 후 3초 대기하고 반복
            setInterval(() => {
                runAnimation();
            }, animationDuration + 3000);
        },

        initEfficacyPhaseChart() {
            const canvas = document.getElementById('efficacyPhaseChart');
            if (!canvas || typeof Chart === 'undefined') return;

            const metrics = @json($result->metrics ?? []);
            const daily = metrics.daily || {};
            const initial = metrics.initial || 0;
            const final = metrics.final || 0;
            const unit = metrics.unit || '';

            const labels = ['0일', '5일', '7일', '14일', '21일', '28일'];
            const dayKeys = [0, 5, 7, 14, 21, 28];

            const getValueForDay = (day) => {
                if (day === 0) return initial;
                if (daily[day]) return daily[day];
                const keys = Object.keys(daily).map(Number).sort((a, b) => a - b);
                for (let i = 0; i < keys.length - 1; i++) {
                    if (day > keys[i] && day < keys[i + 1]) {
                        const ratio = (day - keys[i]) / (keys[i + 1] - keys[i]);
                        return daily[keys[i]] + ratio * (daily[keys[i + 1]] - daily[keys[i]]);
                    }
                }
                if (keys.length > 0 && day < keys[0]) {
                    return initial + (daily[keys[0]] - initial) * (day / keys[0]);
                }
                return initial;
            };

            const data = dayKeys.map(day => getValueForDay(day));

            const range = final - initial;
            const decimals = range < 1 ? 2 : (range < 10 ? 1 : 0);
            const minVal = range < 1 ? Math.floor(initial * 10) / 10 : Math.floor(initial * 0.9);
            const maxVal = range < 1 ? Math.ceil(final * 10) / 10 : Math.ceil(final * 1.1);

            new Chart(canvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '효과',
                        data: data,
                        borderColor: '#000000',
                        backgroundColor: (context) => {
                            const chart = context.chart;
                            const {ctx, chartArea} = chart;
                            if (!chartArea) return 'rgba(255, 255, 255, 0.5)';

                            // 구간별 색상 (흰색 → 파란색 → 검은색)
                            const gradient = ctx.createLinearGradient(chartArea.left, 0, chartArea.right, 0);
                            gradient.addColorStop(0, 'rgba(255, 255, 255, 0.5)');           // 0일 (흰색)
                            gradient.addColorStop(0.2, 'rgba(255, 255, 255, 0.5)');         // 5일 (흰색 끝)
                            gradient.addColorStop(0.2, 'rgba(63, 123, 235, 0.5)');          // 5일 (파란색 시작)
                            gradient.addColorStop(0.6, 'rgba(63, 123, 235, 0.5)');          // 14일 (파란색 끝)
                            gradient.addColorStop(0.6, 'rgba(0, 0, 0, 0.5)');               // 14일 (검은색 시작)
                            gradient.addColorStop(1, 'rgba(0, 0, 0, 0.5)');                 // 28일 (검은색)
                            return gradient;
                        },
                        tension: 0,
                        fill: true,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#FFFFFF',
                        pointBorderColor: '#000000',
                        pointBorderWidth: 2.5,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 1200,
                        easing: 'easeOutQuart'
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => `${ctx.parsed.y.toFixed(decimals)} ${unit}`
                            }
                        }
                    },
                    scales: {
                        y: {
                            min: minVal,
                            max: maxVal,
                            ticks: {
                                font: { size: 10 },
                                color: '#666666',
                                count: 4,
                                callback: (value) => Math.round(value) + (unit ? ' ' + unit : '')
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        },
                        x: {
                            ticks: { font: { size: 10 }, color: '#666666' },
                            grid: {
                                display: true,
                                color: 'rgba(0,0,0,0.15)'
                            }
                        }
                    }
                }
            });
        }
    };
}

// 마일스톤 카드 캐러셀 (1.5개 노출, 무한 자동 슬라이드)
function milestoneCarousel() {
    return {
        cardWidth: 280,
        gap: 12,
        totalCards: 2,
        currentIndex: 0,
        autoSlideInterval: null,
        isTransitioning: false,
        track: null,

        init() {
            this.track = this.$el.querySelector('.milestone-track');
            // 3초마다 자동 슬라이드
            this.autoSlideInterval = setInterval(() => {
                this.nextSlide();
            }, 3000);
        },

        nextSlide() {
            if (this.isTransitioning) return;
            this.isTransitioning = true;

            this.currentIndex++;
            const offset = this.currentIndex * (this.cardWidth + this.gap);

            // 슬라이드 애니메이션 적용
            this.track.style.transform = `translateX(-${offset}px)`;

            // 복제 카드(인덱스 2)로 슬라이드 후 리셋
            if (this.currentIndex >= this.totalCards) {
                setTimeout(() => {
                    // 트랜지션 비활성화
                    this.track.style.transition = 'none';
                    // 인덱스 0으로 즉시 이동 (복제 카드와 원본이 동일하므로 시각적 변화 없음)
                    this.track.style.transform = 'translateX(0px)';
                    this.currentIndex = 0;

                    // 강제 리플로우로 변경사항 즉시 적용
                    void this.track.offsetWidth;

                    // 트랜지션 복원
                    this.track.style.transition = 'transform 500ms ease-out';
                    this.isTransitioning = false;
                }, 520);
            } else {
                setTimeout(() => {
                    this.isTransitioning = false;
                }, 500);
            }
        }
    };
}

// 피부 반응 프로파일 게이지 애니메이션 (동시 시작, 반복)
function profileGauge(index, targetWidth) {
    return {
        currentWidth: 0,
        targetWidth: targetWidth,
        repeatInterval: 5000, // 5초마다 반복

        startAnimation() {
            // 모든 게이지 동시에 시작
            setTimeout(() => {
                this.currentWidth = this.targetWidth;
            }, 500);

            // 반복 애니메이션 (동시에 리셋 후 동시에 시작)
            setInterval(() => {
                this.currentWidth = 0;
                setTimeout(() => {
                    this.currentWidth = this.targetWidth;
                }, 500);
            }, this.repeatInterval + 1500);
        }
    };
}

// 타임라인 애니메이션 (실선 + 그래프 동기화)
// Chart.js 인스턴스를 Alpine 외부에 저장 (무한 루프 방지)
window._efficacyChart = null;

function timelineAnimation() {
    return {
        line1Height: 0,
        line2Height: 0,
        line3Height: 0,

        startAnimation() {
            // Chart.js 로드 대기 후 첫 애니메이션 시작
            const waitForChart = () => {
                if (typeof Chart !== 'undefined') {
                    this.runAnimation();

                    // 완료 후 5초 대기하고 반복
                    setInterval(() => {
                        this.resetAnimation();
                        setTimeout(() => {
                            this.runAnimation();
                        }, 100);
                    }, 8500); // 애니메이션 시간(약 3초) + 대기 시간(5초)
                } else {
                    setTimeout(waitForChart, 100);
                }
            };
            setTimeout(waitForChart, 500);
        },

        runAnimation() {
            // 그래프 애니메이션 시작 (타임라인과 동기화)
            this.animateChart();

            // Line 1 그리기 (점선 위 검은 실선)
            setTimeout(() => {
                this.line1Height = 100;
            }, 300);

            // Line 2 그리기
            setTimeout(() => {
                this.line2Height = 100;
            }, 1000);

            // Line 3 그리기
            setTimeout(() => {
                this.line3Height = 100;
            }, 1700);
        },

        animateChart() {
            const canvas = document.getElementById('efficacyPhaseChart');
            if (!canvas || typeof Chart === 'undefined') return;

            // 기존 차트 삭제 (window 객체에서)
            if (window._efficacyChart) {
                window._efficacyChart.destroy();
                window._efficacyChart = null;
            }

            const metrics = @json($result->metrics ?? []);
            const daily = metrics.daily || {};
            const initial = metrics.initial || 0;
            const final = metrics.final || 0;
            const unit = metrics.unit || '';

            const labels = ['0일', '5일', '7일', '14일', '21일', '28일'];
            const dayKeys = [0, 5, 7, 14, 21, 28];

            const getValueForDay = (day) => {
                if (day === 0) return initial;
                if (daily[day]) return daily[day];
                const keys = Object.keys(daily).map(Number).sort((a, b) => a - b);
                for (let i = 0; i < keys.length - 1; i++) {
                    if (day > keys[i] && day < keys[i + 1]) {
                        const ratio = (day - keys[i]) / (keys[i + 1] - keys[i]);
                        return daily[keys[i]] + ratio * (daily[keys[i + 1]] - daily[keys[i]]);
                    }
                }
                if (keys.length > 0 && day < keys[0]) {
                    return initial + (daily[keys[0]] - initial) * (day / keys[0]);
                }
                return initial;
            };

            const fullData = dayKeys.map(day => getValueForDay(day));
            const range = final - initial;
            const decimals = range < 1 ? 2 : (range < 10 ? 1 : 0);
            const minVal = range < 1 ? Math.floor(initial * 10) / 10 : Math.floor(initial * 0.9);
            const maxVal = range < 1 ? Math.ceil(final * 10) / 10 : Math.ceil(final * 1.1);
            // 빈 데이터로 차트 생성 (window 객체에 저장)
            window._efficacyChart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '효과',
                        data: [null, null, null, null, null, null],
                        borderColor: '#000000',
                        backgroundColor: (context) => {
                            const chart = context.chart;
                            const {ctx, chartArea} = chart;
                            if (!chartArea) return 'rgba(255, 255, 255, 0.5)';

                            // 구간별 색상 (흰색 → 파란색 → 검은색)
                            const gradient = ctx.createLinearGradient(chartArea.left, 0, chartArea.right, 0);
                            gradient.addColorStop(0, 'rgba(255, 255, 255, 0.5)');
                            gradient.addColorStop(0.2, 'rgba(255, 255, 255, 0.5)');
                            gradient.addColorStop(0.2, 'rgba(63, 123, 235, 0.5)');
                            gradient.addColorStop(0.6, 'rgba(63, 123, 235, 0.5)');
                            gradient.addColorStop(0.6, 'rgba(0, 0, 0, 0.5)');
                            gradient.addColorStop(1, 'rgba(0, 0, 0, 0.5)');
                            return gradient;
                        },
                        tension: 0,
                        fill: true,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#FFFFFF',
                        pointBorderColor: '#000000',
                        pointBorderWidth: 2.5,
                        borderWidth: 2,
                        spanGaps: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 300,
                        easing: 'easeOutQuart'
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => `${ctx.parsed.y.toFixed(decimals)} ${unit}`
                            }
                        }
                    },
                    scales: {
                        y: {
                            min: minVal,
                            max: maxVal,
                            ticks: {
                                font: { size: 10 },
                                color: '#666666',
                                count: 4,
                                callback: (value) => Math.round(value) + (unit ? ' ' + unit : '')
                            },
                            grid: { color: 'rgba(0,0,0,0.1)' }
                        },
                        x: {
                            ticks: { font: { size: 10 }, color: '#666666' },
                            grid: { display: true, color: 'rgba(0,0,0,0.15)' }
                        }
                    }
                }
            });

            // 점 순차적으로 추가 (각 400ms 간격)
            fullData.forEach((value, index) => {
                setTimeout(() => {
                    if (window._efficacyChart) {
                        window._efficacyChart.data.datasets[0].data[index] = value;
                        window._efficacyChart.update('none');
                    }
                }, index * 400);
            });
        },

        resetAnimation() {
            this.line1Height = 0;
            this.line2Height = 0;
            this.line3Height = 0;
        }
    };
}

// 공유용 URL
const shareUrl = '{{ $shareUrl ?? url()->current() }}';

// 카카오톡 공유
function shareKakao() {
    // 카카오 SDK가 없으면 일반 공유로 대체
    if (typeof Kakao !== 'undefined' && Kakao.isInitialized()) {
        Kakao.Share.sendDefault({
            objectType: 'feed',
            content: {
                title: '{{ $product->name }} 피부 분석 결과',
                description: '나의 피부 분석 결과를 확인해보세요!',
                imageUrl: '{{ asset("logo.png") }}',
                link: {
                    mobileWebUrl: shareUrl,
                    webUrl: shareUrl,
                },
            },
            buttons: [
                {
                    title: '결과 보기',
                    link: {
                        mobileWebUrl: shareUrl,
                        webUrl: shareUrl,
                    },
                },
            ],
        });
    } else {
        // 카카오 SDK가 없으면 기본 공유 기능 사용
        if (navigator.share) {
            navigator.share({
                title: '{{ $product->name }} 피부 분석 결과',
                text: '나의 피부 분석 결과를 확인해보세요!',
                url: shareUrl,
            });
        } else {
            copyLink();
        }
    }
}

// 링크 복사
function copyLink() {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(shareUrl).then(() => {
            alert('링크가 복사되었습니다!');
        }).catch(() => {
            fallbackCopyLink(shareUrl);
        });
    } else {
        fallbackCopyLink(shareUrl);
    }
}

function fallbackCopyLink(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    try {
        document.execCommand('copy');
        alert('링크가 복사되었습니다!');
    } catch (e) {
        alert('링크 복사에 실패했습니다. 직접 복사해주세요: ' + text);
    }
    document.body.removeChild(textarea);
}
</script>
@endpush
