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

    $efficacyNames = \App\Models\Product::$efficacyTypes;
    $efficacyType = $result->metrics['efficacy_type'] ?? 'moisture';
    $efficacyName = $efficacyNames[$efficacyType] ?? '수분 공급';

    $pointColor = $product->point_color ?? '#10B981';
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

    // 포인트컬러 기반 진한 색상 계산 (주 색상 강조, 채도 높임)
    // #acdda5 → #6BC287 기준: max채널 0.88, min채널 0.82, mid채널 0.62
    $darkenColor = function($rgb) {
        $maxVal = max($rgb[0], $rgb[1], $rgb[2]);
        $minVal = min($rgb[0], $rgb[1], $rgb[2]);

        return array_map(function($val) use ($maxVal, $minVal) {
            if ($val == $maxVal) {
                return max(0, round($val * 0.88));  // 주 색상 유지
            } elseif ($val == $minVal) {
                return max(0, round($val * 0.82));  // 보조 색상
            } else {
                return max(0, round($val * 0.62));  // 중간값 가장 많이 줄임
            }
        }, $rgb);
    };
    $darkerRgb = $darkenColor($rgb);
    $darkerPointColor = sprintf('#%02x%02x%02x', $darkerRgb[0], $darkerRgb[1], $darkerRgb[2]);

    $improvementPercent = round($result->metrics['change_percent'] ?? 0);
    $milestoneLabels = $product->getEfficacyMilestoneLabels();
    $milestoneCenterTexts = $product->getMilestoneCenterTexts();
    $descriptions = $product->getEfficacyPhaseDescriptions();

    // 피부 프로파일 데이터
    $skinProfile = $result->skin_profile ?? [];
    $characteristics = $skinProfile['characteristics'] ?? [];

    // 특성별 레벨 및 설명 (1-5 스케일)
    $profileData = [
        'regeneration' => [
            'label' => '피부재생속도',
            'level' => $characteristics['regeneration']['level'] ?? 3,
            'description' => $characteristics['regeneration']['description'] ?? '보통 수준',
        ],
        'moisture_retention' => [
            'label' => '피부 수분유지력',
            'level' => $characteristics['moisture_retention']['level'] ?? 3,
            'description' => $characteristics['moisture_retention']['description'] ?? '보통 수준',
        ],
        'pigment_reactivity' => [
            'label' => '피부 색소 반응성',
            'level' => $characteristics['pigment_reactivity']['level'] ?? 3,
            'description' => $characteristics['pigment_reactivity']['description'] ?? '보통 수준',
        ],
    ];
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

    {{-- 상단 헤더 (메인과 동일하게 고정) --}}
    <div x-show="!isLoading" class="bg-black py-4 sticky top-0 z-50 overflow-hidden">
        <div class="flex items-center gap-3">
            <img src="{{ asset('logo_white.png') }}" alt="essenciel" class="h-5 flex-shrink-0 ml-4">
            <div class="marquee-container overflow-hidden flex-1">
                <div class="marquee-track">
                    <span class="marquee-text text-sm text-white">에센시엘은 검증된 데이터를 기반으로 과학적으로 설계합니다.</span>
                    <span class="marquee-text text-sm text-white">에센시엘은 검증된 데이터를 기반으로 과학적으로 설계합니다.</span>
                    <span class="marquee-text text-sm text-white">에센시엘은 검증된 데이터를 기반으로 과학적으로 설계합니다.</span>
                </div>
            </div>
        </div>
    </div>

    {{-- 분석 완료 섹션 (전체 배경 그라데이션, 가로세로 비율 18:15) --}}
    <div x-show="!isLoading" class="text-center" style="background: linear-gradient(180deg, #FFFFFF 0%, {{ $darkerPointColor }}50 100%); aspect-ratio: 18 / 15;">
        <div class="max-w-[375px] mx-auto px-4 h-full flex flex-col justify-center">
            {{-- 반원 게이지 + 이동하는 점 --}}
            <div class="relative mx-auto" style="width: 340px; height: 180px;">
                {{-- 반원 게이지 배경 --}}
                <svg class="absolute top-0 left-0 w-full h-full" viewBox="0 0 210 110">
                    <defs>
                        {{-- 게이지 그라데이션 (연한색 → 진한색) --}}
                        <linearGradient id="gaugeGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="{{ $pointColor }}"/>
                            <stop offset="100%" stop-color="{{ $darkerPointColor }}"/>
                        </linearGradient>
                    </defs>
                    {{-- 배경 반원 --}}
                    <path d="M 15 95 A 90 90 0 0 1 195 95" fill="none" stroke="#E5E5E5" stroke-width="8" stroke-linecap="round"/>
                    {{-- 게이지 반원 (애니메이션 + 그라데이션) --}}
                    <path d="M 15 95 A 90 90 0 0 1 195 95" fill="none" stroke="url(#gaugeGradient)" stroke-width="8" stroke-linecap="round"
                          stroke-dasharray="283" :stroke-dashoffset="283 - (gaugeProgress * 283 / 100)"/>
                    {{-- 이동하는 점 마커 (흰색 점 + 포인트컬러 테두리) --}}
                    <circle :cx="markerX" :cy="markerY" r="6" fill="white" stroke="{{ $darkerPointColor }}" stroke-width="4"/>
                </svg>

                {{-- 중앙 텍스트 (퍼센트만) --}}
                <div class="absolute inset-0 flex items-center justify-center" style="padding-top: 50px;">
                    <span class="font-bold text-gray-900" style="font-size: 66px;" x-text="Math.round(gaugeProgress) + '%'">0%</span>
                </div>
            </div>

            {{-- 상품명 + 결과 생성 상태 (게이지 하단) --}}
            <div class="text-center -mt-2 mb-4">
                <p class="text-sm text-gray-500">{{ $product->name }}</p>
                <p class="text-sm text-gray-500" x-text="gaugeProgress >= 100 ? '결과 생성완료' : '결과 생성중'">결과 생성중</p>
            </div>

            {{-- 분석 완료 버튼 --}}
            <button class="w-3/5 mx-auto py-3 bg-black text-white text-center font-semibold rounded-xl">
                분석 완료
            </button>
        </div>
    </div>

    <div x-show="!isLoading" class="px-5 py-8 max-w-lg mx-auto">
        {{-- 1. 피부 반응 프로파일 요약 --}}
        <div class="bg-white rounded-2xl mb-10" style="border: 1px solid #D9D9D9;">
            <div class="p-6">
                {{-- 타이틀 --}}
                <div class="flex items-center gap-2 mb-6">
                    <div class="w-8 h-8 bg-black rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <h2 class="text-lg font-bold text-gray-900">피부 반응 프로파일 요약</h2>
                </div>

                @php
                    $averagePositions = [45, 50, 54]; // 각 항목별 평균 표시선 위치 (%)
                @endphp
                <div class="space-y-8">
                    @foreach($profileData as $key => $data)
                    @php $avgPos = $averagePositions[$loop->index] ?? 50; @endphp
                    <div x-data="profileGauge({{ $loop->index }}, {{ ($data['level'] / 5) * 100 }})" x-init="startAnimation()">
                        {{-- 텍스트 (두 줄) --}}
                        <p class="mb-1 font-semibold" style="font-size: 24px; color: #999999;">
                            당신의 <span style="color: #000000;">{{ $data['label'] }}</span>{{ $eunNeun($data['label']) }}
                        </p>
                        <p class="mb-8 font-semibold" style="font-size: 24px; color: #999999;">
                            <span style="color: #999999;">{{ $data['description'] }}</span>입니다.
                        </p>

                        {{-- 게이지 바 --}}
                        <div class="relative rounded-full overflow-visible" style="background-color: #E8E8E8; height: 28px;">
                            {{-- 평균 지점 표시 --}}
                            <div class="absolute transform -translate-x-1/2 text-base text-gray-500 font-medium" style="left: {{ $avgPos }}%; top: -28px;">평균</div>
                            <div class="absolute top-0 transform -translate-x-1/2 bg-gray-800 z-10" style="left: {{ $avgPos }}%; height: 28px; width: 3px;"></div>
                            {{-- 게이지 (포인트 컬러 그라데이션) --}}
                            <div class="absolute top-0 left-0 h-full rounded-full transition-all duration-1000 ease-out"
                                 :style="'width: ' + currentWidth + '%; background: linear-gradient(to right, {{ $pointColor }}, {{ $darkerPointColor }})'">
                            </div>
                        </div>
                        {{-- 하단 라벨 --}}
                        <div class="flex justify-between mt-3 px-1">
                            <span class="text-base text-gray-400">적음</span>
                            <span class="text-base text-gray-400">보통</span>
                            <span class="text-base text-gray-400">많음</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- 2. 효능 발현 예측 --}}
        <div class="bg-white rounded-2xl mb-10" style="border: 1px solid #D9D9D9;">
            <div class="p-6">
                {{-- 타이틀 --}}
                <div class="flex items-center gap-2 mb-6">
                    <div class="w-8 h-8 bg-black rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h2 class="text-lg font-bold text-gray-900">효능 발현 예측</h2>
                </div>

                {{-- 원형 틱 게이지 애니메이션 --}}
                <div class="relative w-60 h-60 mx-auto mb-8">
                    {{-- 원형 틱 마크 (6시부터 시계방향) --}}
                    <svg class="absolute inset-0 w-full h-full" viewBox="0 0 200 200" style="z-index: 1;">
                        @php
                            $totalTicks = 36; // 총 틱 개수
                            $outerRadius = 98; // 외부 반지름
                            $tickLength = 20; // 틱 길이
                        @endphp
                        @for($i = 0; $i < $totalTicks; $i++)
                        @php
                            // 6시(90도)부터 시계방향으로 시작
                            $angle = deg2rad(($i * 360 / $totalTicks) + 90);
                            $x1 = 100 + ($outerRadius - $tickLength) * cos($angle);
                            $y1 = 100 + ($outerRadius - $tickLength) * sin($angle);
                            $x2 = 100 + $outerRadius * cos($angle);
                            $y2 = 100 + $outerRadius * sin($angle);
                        @endphp
                        <line
                            x1="{{ round($x1, 2) }}" y1="{{ round($y1, 2) }}"
                            x2="{{ round($x2, 2) }}" y2="{{ round($y2, 2) }}"
                            stroke-width="3"
                            stroke-linecap="round"
                            class="tick-mark"
                            data-index="{{ $i }}"
                            stroke="#D9D9D9"/>
                        @endfor
                    </svg>

                    {{-- 중앙 텍스트 (틱 마크 안쪽 영역) --}}
                    <div class="absolute inset-0 flex items-center justify-center" style="z-index: 2;">
                        <div class="w-36 h-36 rounded-full bg-white flex flex-col items-center justify-center text-center">
                            <span class="text-sm text-gray-500 mb-1">한 달 사용 후</span>
                            <span class="text-xl font-bold text-gray-900">{{ $efficacyName }}</span>
                            <span class="text-2xl font-bold text-gray-900">{{ $improvementPercent }}% 개선</span>
                        </div>
                    </div>
                </div>

                {{-- 설명 텍스트 --}}
                <div class="rounded-xl p-5" style="background-color: {{ $lightTintColor }}; border: 1px solid {{ $pointColor }};">
                    <p class="text-base leading-relaxed" style="color: #999999;">
                        고객님이 <span class="font-semibold" style="color: #000000;">{{ $product->name }}</span>{{ $eulReul($product->name) }}
                        꾸준히 사용할 경우 한달 뒤 <span class="font-bold" style="color: {{ $pointColor }};">{{ $efficacyName }}{{ $iGa($efficacyName) }} {{ $improvementPercent }}% 개선</span>될 것으로 예측됩니다.
                    </p>
                </div>
            </div>
        </div>

        {{-- 마일스톤 카드 슬라이드쇼 (1.5개 노출, 무한 자동 슬라이드) --}}
        <div class="mb-8" x-data="milestoneCarousel()" x-init="init()">
            @php
                $totalTicks = 28; // 28일 기준
                $tickRadius = 42; // 틱 원 반지름
                $tickLength = 8; // 틱 길이
            @endphp
            <div class="relative overflow-hidden ml-4">
                <div class="milestone-track flex gap-3"
                     style="transition: transform 500ms ease-out; transform: translateX(0px);">
                    {{-- 카드 1: 7-10일 --}}
                    <div class="milestone-card bg-black rounded-2xl px-4 py-2 flex items-center justify-between flex-shrink-0" style="width: 280px; height: 115px;">
                        <p class="text-white text-sm font-medium leading-tight flex-shrink-0" style="width: 60px;">{!! nl2br(e($milestoneLabels[0] ?? '초기 톤 개선 체감')) !!}</p>
                        <div class="relative flex-shrink-0" style="width: 90px; height: 90px;">
                            <svg class="w-full h-full" viewBox="0 0 100 100">
                                @for($i = 0; $i < $totalTicks; $i++)
                                @php
                                    $angle = deg2rad(($i * 360 / $totalTicks) - 90);
                                    $x1 = 50 + ($tickRadius - $tickLength) * cos($angle);
                                    $y1 = 50 + ($tickRadius - $tickLength) * sin($angle);
                                    $x2 = 50 + $tickRadius * cos($angle);
                                    $y2 = 50 + $tickRadius * sin($angle);
                                    $isFilled = $i < 10;
                                @endphp
                                <line x1="{{ round($x1, 2) }}" y1="{{ round($y1, 2) }}"
                                      x2="{{ round($x2, 2) }}" y2="{{ round($y2, 2) }}"
                                      stroke="{{ $isFilled ? $pointColor : '#FFFFFF' }}"
                                      stroke-width="3" stroke-linecap="round"/>
                                @endfor
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-white text-[10px] text-center leading-tight px-2">{!! nl2br(e($milestoneCenterTexts[0] ?? '')) !!}</span>
                            </div>
                        </div>
                        <span class="text-white text-lg font-bold flex-shrink-0">7-10일</span>
                    </div>
                    {{-- 카드 2: 21-28일 --}}
                    <div class="milestone-card bg-black rounded-2xl px-4 py-2 flex items-center justify-between flex-shrink-0" style="width: 280px; height: 115px;">
                        <p class="text-white text-sm font-medium leading-tight flex-shrink-0" style="width: 60px;">{!! nl2br(e($milestoneLabels[1] ?? '효과 최대 발현')) !!}</p>
                        <div class="relative flex-shrink-0" style="width: 90px; height: 90px;">
                            <svg class="w-full h-full" viewBox="0 0 100 100">
                                @for($i = 0; $i < $totalTicks; $i++)
                                @php
                                    $angle = deg2rad(($i * 360 / $totalTicks) - 90);
                                    $x1 = 50 + ($tickRadius - $tickLength) * cos($angle);
                                    $y1 = 50 + ($tickRadius - $tickLength) * sin($angle);
                                    $x2 = 50 + $tickRadius * cos($angle);
                                    $y2 = 50 + $tickRadius * sin($angle);
                                @endphp
                                <line x1="{{ round($x1, 2) }}" y1="{{ round($y1, 2) }}"
                                      x2="{{ round($x2, 2) }}" y2="{{ round($y2, 2) }}"
                                      stroke="{{ $pointColor }}"
                                      stroke-width="3" stroke-linecap="round"/>
                                @endfor
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-white text-[10px] text-center leading-tight px-2">{!! nl2br(e($milestoneCenterTexts[1] ?? '')) !!}</span>
                            </div>
                        </div>
                        <span class="text-white text-lg font-bold flex-shrink-0">21-28일</span>
                    </div>
                    {{-- 무한 루프를 위한 복제 카드 1 --}}
                    <div class="milestone-card bg-black rounded-2xl px-4 py-2 flex items-center justify-between flex-shrink-0" style="width: 280px; height: 115px;">
                        <p class="text-white text-sm font-medium leading-tight flex-shrink-0" style="width: 60px;">{!! nl2br(e($milestoneLabels[0] ?? '초기 톤 개선 체감')) !!}</p>
                        <div class="relative flex-shrink-0" style="width: 90px; height: 90px;">
                            <svg class="w-full h-full" viewBox="0 0 100 100">
                                @for($i = 0; $i < $totalTicks; $i++)
                                @php
                                    $angle = deg2rad(($i * 360 / $totalTicks) - 90);
                                    $x1 = 50 + ($tickRadius - $tickLength) * cos($angle);
                                    $y1 = 50 + ($tickRadius - $tickLength) * sin($angle);
                                    $x2 = 50 + $tickRadius * cos($angle);
                                    $y2 = 50 + $tickRadius * sin($angle);
                                    $isFilled = $i < 10;
                                @endphp
                                <line x1="{{ round($x1, 2) }}" y1="{{ round($y1, 2) }}"
                                      x2="{{ round($x2, 2) }}" y2="{{ round($y2, 2) }}"
                                      stroke="{{ $isFilled ? $pointColor : '#FFFFFF' }}"
                                      stroke-width="3" stroke-linecap="round"/>
                                @endfor
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-white text-[10px] text-center leading-tight px-2">{!! nl2br(e($milestoneCenterTexts[0] ?? '')) !!}</span>
                            </div>
                        </div>
                        <span class="text-white text-lg font-bold flex-shrink-0">7-10일</span>
                    </div>
                    {{-- 무한 루프를 위한 복제 카드 2 --}}
                    <div class="milestone-card bg-black rounded-2xl px-4 py-2 flex items-center justify-between flex-shrink-0" style="width: 280px; height: 115px;">
                        <p class="text-white text-sm font-medium leading-tight flex-shrink-0" style="width: 60px;">{!! nl2br(e($milestoneLabels[1] ?? '효과 최대 발현')) !!}</p>
                        <div class="relative flex-shrink-0" style="width: 90px; height: 90px;">
                            <svg class="w-full h-full" viewBox="0 0 100 100">
                                @for($i = 0; $i < $totalTicks; $i++)
                                @php
                                    $angle = deg2rad(($i * 360 / $totalTicks) - 90);
                                    $x1 = 50 + ($tickRadius - $tickLength) * cos($angle);
                                    $y1 = 50 + ($tickRadius - $tickLength) * sin($angle);
                                    $x2 = 50 + $tickRadius * cos($angle);
                                    $y2 = 50 + $tickRadius * sin($angle);
                                @endphp
                                <line x1="{{ round($x1, 2) }}" y1="{{ round($y1, 2) }}"
                                      x2="{{ round($x2, 2) }}" y2="{{ round($y2, 2) }}"
                                      stroke="{{ $pointColor }}"
                                      stroke-width="3" stroke-linecap="round"/>
                                @endfor
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-white text-[10px] text-center leading-tight px-2">{!! nl2br(e($milestoneCenterTexts[1] ?? '')) !!}</span>
                            </div>
                        </div>
                        <span class="text-white text-lg font-bold flex-shrink-0">21-28일</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. 단계별 효과 --}}
        <div class="bg-white rounded-2xl mb-8" style="border: 1px solid #D9D9D9;">
            <div class="p-5">
                {{-- 타이틀 --}}
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 bg-black rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h2 class="text-lg font-bold text-gray-900">단계별 효과</h2>
                </div>

                {{-- 그래프 영역 --}}
                <div class="rounded-xl p-4 mb-6" style="background-color: #F6F6F6;">
                    <div class="h-48">
                        <canvas id="efficacyPhaseChart"></canvas>
                    </div>
                </div>

                {{-- 타임라인 + 버튼 + 설명 (실선만 애니메이션) --}}
                <div class="space-y-0" x-data="timelineAnimation()" x-init="startAnimation()">
                    {{-- Phase 1: D0-5 (흰색/회색) --}}
                    <div class="flex items-start gap-5">
                        <div class="flex flex-col items-center flex-shrink-0" style="width: 16px;">
                            <div class="w-4 h-4 rounded-full" style="background-color: #D9D9D9;"></div>
                            <div class="relative w-full flex justify-center" style="height: 120px;">
                                <div class="absolute inset-y-0 border-l-2 border-dashed" style="border-color: #D9D9D9;"></div>
                                <div class="absolute top-0 w-0.5 bg-black transition-all duration-700 ease-out"
                                     :style="'height: ' + line1Height + '%'"></div>
                            </div>
                        </div>
                        <div class="flex-1 pb-10">
                            <button class="px-4 py-2 text-base font-bold rounded-lg mb-3" style="background-color: #F4F4F4; color: #000000;">
                                D0-5
                            </button>
                            <p class="text-base text-gray-700 leading-relaxed">{{ $descriptions['phase1'] }}</p>
                        </div>
                    </div>

                    {{-- Phase 2: D7-10 (포인트컬러) --}}
                    <div class="flex items-start gap-5">
                        <div class="flex flex-col items-center flex-shrink-0" style="width: 16px;">
                            <div class="w-4 h-4 rounded-full" style="background-color: {{ $pointColor }};"></div>
                            <div class="relative w-full flex justify-center" style="height: 120px;">
                                <div class="absolute inset-y-0 border-l-2 border-dashed" style="border-color: #D9D9D9;"></div>
                                <div class="absolute top-0 w-0.5 bg-black transition-all duration-700 ease-out"
                                     :style="'height: ' + line2Height + '%'"></div>
                            </div>
                        </div>
                        <div class="flex-1 pb-10">
                            <button class="px-4 py-2 text-base font-bold rounded-lg mb-3 text-white" style="background-color: {{ $pointColor }};">
                                D7-10
                            </button>
                            <p class="text-base text-gray-700 leading-relaxed">{{ $descriptions['phase2'] }}</p>
                        </div>
                    </div>

                    {{-- Phase 3: D21-28 (검은색) --}}
                    <div class="flex items-start gap-5">
                        <div class="flex flex-col items-center flex-shrink-0" style="width: 16px;">
                            <div class="w-4 h-4 rounded-full" style="background-color: #000000;"></div>
                            <div class="relative w-full flex justify-center" style="height: 120px;">
                                <div class="absolute inset-y-0 border-l-2 border-dashed" style="border-color: #D9D9D9;"></div>
                                <div class="absolute top-0 w-0.5 bg-black transition-all duration-700 ease-out"
                                     :style="'height: ' + line3Height + '%'"></div>
                            </div>
                        </div>
                        <div class="flex-1 pb-10">
                            <button class="px-4 py-2 text-base font-bold rounded-lg mb-3 text-white" style="background-color: #000000;">
                                D21-28
                            </button>
                            <p class="text-base text-gray-700 leading-relaxed">{{ $descriptions['phase3'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. 효능을 늦추는 생활 요인 --}}
        <div class="mb-8">
            {{-- 타이틀 --}}
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 bg-black rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-gray-900">효능을 늦추는 생활 요인</h2>
            </div>

            {{-- 수동 슬라이더 카드 --}}
            @php
                $lifestyleFactors = [
                    ['emoji' => '😫', 'title' => '스트레스 수준이 높아', 'desc' => '피부톤 개선 효능 체감이 평균보다 늦어질 수 있습니다.'],
                    ['emoji' => '🚬', 'title' => '흡연 습관', 'desc' => '피부 재생 속도가 느려져 효과 발현이 지연될 수 있습니다.'],
                    ['emoji' => '🍺', 'title' => '음주 빈도가 높아', 'desc' => '피부 수분 유지력이 저하되어 효능이 감소할 수 있습니다.'],
                ];
            @endphp
            <div x-data="lifestyleSlider()" class="flex items-stretch gap-3">
                {{-- 카드 영역 --}}
                <div class="flex-1 overflow-hidden rounded-2xl" style="border: 1px solid #E5E5E5;">
                    <div class="flex transition-transform duration-300 ease-out" :style="'transform: translateX(-' + (currentIndex * 100) + '%)'">
                        @foreach($lifestyleFactors as $index => $factor)
                        <div class="w-full flex-shrink-0 bg-white p-5 flex items-center gap-3">
                            <div class="text-3xl flex-shrink-0">{{ $factor['emoji'] }}</div>
                            <div class="flex-1">
                                <p class="text-lg font-bold text-gray-900 mb-1">{{ $factor['title'] }}</p>
                                <p class="text-lg text-gray-500 leading-relaxed">{{ $factor['desc'] }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                {{-- 우측 화살표 버튼 (분리, 카드 높이와 동일) --}}
                <button @click="next()" class="flex-shrink-0 w-12 bg-black rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- 5. AI 분석 사용 가이드 --}}
        <div class="mb-8">
            {{-- 타이틀 (섹션 밖) --}}
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 bg-black rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-gray-900">AI 분석 사용 가이드</h2>
            </div>

            {{-- 최적 사용 시간 (border로 감싸기) --}}
            @php
                $optimalUsage = $result->usage_guide['optimal_usage'] ?? [];
                $morningEffect = $optimalUsage['timing']['morning_effect'] ?? 100;
                $eveningEffect = $optimalUsage['timing']['evening_effect'] ?? 100;
                $timingReason = $optimalUsage['timing']['reason'] ?? '피부 재생이 활발한 시간대';
                $isMorningGood = $morningEffect > 100;
                $isEveningGood = $eveningEffect > 100;
            @endphp
            <div class="rounded-2xl p-5" style="background-color: #F0FFF4; border: 1px solid #D9D9D9;">
                <h3 class="text-xl font-bold text-gray-900 mb-2">최적 사용 시간</h3>
                <p class="text-sm text-gray-600 mb-4">{{ $timingReason }}</p>

                {{-- 아침/저녁 효과 카드 --}}
                <div class="grid grid-cols-2 gap-3">
                    {{-- 아침 효과 --}}
                    <div>
                        @if($isMorningGood)
                            <p class="text-center font-bold text-lg text-gray-900 mb-2">Good</p>
                        @else
                            <p class="text-center font-bold text-lg text-transparent mb-2">&nbsp;</p>
                        @endif
                        <div class="bg-white rounded-xl p-4 text-center" style="border: 1px solid {{ $isMorningGood ? '#000' : '#E5E5E5' }};">
                            <div class="text-3xl mb-2">🌤️</div>
                            <p class="text-sm text-gray-500 mb-1">아침 효과</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $morningEffect }}%</p>
                        </div>
                    </div>
                    {{-- 저녁 효과 --}}
                    <div>
                        @if($isEveningGood)
                            <p class="text-center font-bold text-lg text-gray-900 mb-2">Good</p>
                        @else
                            <p class="text-center font-bold text-lg text-transparent mb-2">&nbsp;</p>
                        @endif
                        <div class="bg-white rounded-xl p-4 text-center" style="border: 1px solid {{ $isEveningGood ? '#000' : '#E5E5E5' }};">
                            <div class="text-3xl mb-2">🌙</div>
                            <p class="text-sm text-gray-500 mb-1">저녁 효과</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $eveningEffect }}%</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 6. 추가 효과 향상 방법 --}}
        <div class="mb-8">
            {{-- 타이틀 --}}
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 bg-black rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-gray-900">추가 효과 향상 방법</h2>
            </div>

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
                        <div class="bg-white rounded-2xl p-6 h-[200px] overflow-hidden relative" style="border: 1px solid #D9D9D9;">
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
                            <div class="flex items-center gap-2 mb-4 z-10">
                                <span class="text-2xl">{{ $icon }}</span>
                                <span class="font-bold text-lg text-gray-900">{{ $actionShort }}</span>
                            </div>
                            {{-- 텍스트 영역 (카드 왼쪽 하단) --}}
                            <div class="absolute bottom-4 left-6 z-10">
                                @if($isBoostType)
                                    <p class="text-3xl font-bold text-gray-900 mb-1">{{ $effectBoost }}% 향상</p>
                                    <div class="flex items-center gap-2 text-sm text-gray-500">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                            </svg>
                                        </span>
                                        <span>Boost</span>
                                    </div>
                                @else
                                    <p class="text-3xl font-bold text-gray-900 mb-1">{{ $daysSaved }}일 단축</p>
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
                            <div class="absolute bottom-4 right-4 flex justify-end {{ $imageType === 'faster_clock' ? 'w-[55%]' : 'w-[50%]' }}">
                                @if($imageType === 'boost_line')
                                    <img src="/images/effects/향상2.png" alt="효과 향상" class="w-full h-auto object-contain">
                                @elseif($imageType === 'boost_bar')
                                    <img src="/images/effects/향상1.png" alt="효과 향상" class="w-full h-auto object-contain">
                                @elseif($imageType === 'faster_timeline')
                                    <img src="/images/effects/단축1.png" alt="효능 도달" class="w-full h-auto object-contain -translate-y-12">
                                @elseif($imageType === 'faster_clock')
                                    <img src="/images/effects/단축2.png" alt="효능 도달" class="w-full h-auto object-contain translate-y-4">
                                @endif
                            </div>
                        </div>
                        <div class="bg-gray-100 rounded-xl px-6 py-3 mt-2 mb-10">
                            <p class="text-sm text-gray-700">
                                @if($isBoostType)
                                    <span class="font-semibold text-black">{{ $actionShort }}{{ preg_match('/[를을]$/', $actionShort) ? '' : (preg_match('/[가-힣]/', mb_substr($actionShort, -1)) && in_array(mb_ord(mb_substr($actionShort, -1)) % 28, [0]) ? '를' : '을') }} 할 경우</span> 효과가 최대 {{ $effectBoost }}% 향상될 것으로 예상됩니다.
                                @else
                                    <span class="font-semibold text-black">{{ $actionShort }}{{ preg_match('/[를을]$/', $actionShort) ? '' : (preg_match('/[가-힣]/', mb_substr($actionShort, -1)) && in_array(mb_ord(mb_substr($actionShort, -1)) % 28, [0]) ? '를' : '을') }} 할 경우</span> 효능 도달시점이 최대 {{ $daysSaved }}일 단축될 것으로 예상됩니다.
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

        {{-- 7. 결과 공유하기 --}}
        <div class="bg-white rounded-2xl mb-8 p-5" style="border: 1px solid #D9D9D9;">
            <h3 class="text-lg font-bold text-gray-900 mb-4">결과 공유하기</h3>
            <div class="grid grid-cols-2 gap-3">
                <button onclick="shareKakao()" class="py-3 rounded-lg font-semibold text-gray-900" style="background-color: #FEE500;">
                    카카오톡
                </button>
                <button onclick="copyLink()" class="py-3 rounded-lg font-semibold text-gray-700 bg-white" style="border: 1px solid #D9D9D9;">
                    링크 복사
                </button>
            </div>
        </div>

        {{-- 다시 분석하기 --}}
        <div class="text-center mb-6">
            <a href="{{ route('survey.index', $product->code) }}" class="text-gray-500 text-sm hover:underline">
                다시 분석하기
            </a>
        </div>

        <div class="h-4"></div>
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
        gaugeProgress: 0,
        // 반원 게이지 마커 위치 (중심: 105,95 / 반지름: 90 / 시작: 180도(왼쪽))
        markerX: 15,  // 시작점 x (180도 위치)
        markerY: 95,  // 시작점 y

        init() {
            // 로딩 완료 후 컨텐츠 표시
            setTimeout(() => {
                this.isLoading = false;

                // 로딩 해제 후 애니메이션 시작
                setTimeout(() => {
                    // 반원 게이지 + 마커 애니메이션
                    this.animateGaugeWithMarker();

                    // 원형 틱 게이지 애니메이션
                    setTimeout(() => {
                        this.animateTickGauge();
                    }, 500);
                }, 300);
            }, 800);

        },

        animateGaugeWithMarker() {
            const duration = 1500; // 1.5초
            const startTime = performance.now();

            const animate = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);

                // easeOut 이징
                const easeProgress = 1 - Math.pow(1 - progress, 3);

                // 게이지 진행률 업데이트
                this.gaugeProgress = easeProgress * 100;

                // 마커 위치 계산 (반원: 180도 → 0도, 즉 왼쪽에서 오른쪽으로)
                // 중심: (105, 95), 반지름: 90
                const angle = Math.PI - (easeProgress * Math.PI); // 180도 → 0도 (라디안)
                this.markerX = 105 + 90 * Math.cos(angle);
                this.markerY = 95 - 90 * Math.sin(angle);

                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    this.gaugeProgress = 100;
                    this.markerX = 195;
                    this.markerY = 95;
                }
            };

            requestAnimationFrame(animate);
        },

        animateTickGauge() {
            const ticks = document.querySelectorAll('.tick-mark');
            const totalTicks = ticks.length; // 36개
            const animationDuration = totalTicks * 50; // 전체 애니메이션 시간

            const runAnimation = () => {
                // 먼저 모든 틱을 초기 색상으로 리셋
                ticks.forEach(tick => {
                    tick.style.transition = 'none';
                    tick.setAttribute('stroke', '#D9D9D9');
                });

                // 약간의 딜레이 후 순차적으로 검은색으로 채우기
                setTimeout(() => {
                    ticks.forEach((tick, index) => {
                        setTimeout(() => {
                            tick.style.transition = 'stroke 0.2s ease-out';
                            tick.setAttribute('stroke', '#000000');
                        }, index * 50);
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

            const labels = ['D0', 'D5', 'D7', 'D14', 'D21', 'D28'];
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

            // 구간별 색상 (포인트컬러 기준 명암 차등)
            // labels: ['D0', 'D5', 'D7', 'D14', 'D21', 'D28'] - index 0,1,2,3,4,5
            // D5 = 1/5 = 0.2, D14 = 3/5 = 0.6
            const pointColor = '{{ $pointColor }}';
            const rgbString = '{{ $rgbString }}';

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

                            // 구간별 색상 (1차: 흰색, 2차: 포인트컬러, 3차: 검은색) + 투명도
                            const gradient = ctx.createLinearGradient(chartArea.left, 0, chartArea.right, 0);
                            gradient.addColorStop(0, 'rgba(255, 255, 255, 0.5)');           // D0 (흰색 50%)
                            gradient.addColorStop(0.2, 'rgba(255, 255, 255, 0.5)');         // D5 (흰색 끝)
                            gradient.addColorStop(0.2, `rgba(${rgbString}, 0.5)`);          // D5 (포인트컬러 시작)
                            gradient.addColorStop(0.6, `rgba(${rgbString}, 0.5)`);          // D14 (포인트컬러 끝)
                            gradient.addColorStop(0.6, 'rgba(0, 0, 0, 0.5)');               // D14 (검은색 시작)
                            gradient.addColorStop(1, 'rgba(0, 0, 0, 0.5)');                 // D28 (검은색)
                            return gradient;
                        },
                        tension: 0.4,
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

// 생활 요인 수동 슬라이더
function lifestyleSlider() {
    return {
        currentIndex: 0,
        totalSlides: 3,

        next() {
            this.currentIndex = (this.currentIndex + 1) % this.totalSlides;
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

            const labels = ['D0', 'D5', 'D7', 'D14', 'D21', 'D28'];
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
            const pointColor = '{{ $pointColor }}';
            const rgbString = '{{ $rgbString }}';

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

                            const gradient = ctx.createLinearGradient(chartArea.left, 0, chartArea.right, 0);
                            gradient.addColorStop(0, 'rgba(255, 255, 255, 0.5)');
                            gradient.addColorStop(0.2, 'rgba(255, 255, 255, 0.5)');
                            gradient.addColorStop(0.2, `rgba(${rgbString}, 0.5)`);
                            gradient.addColorStop(0.6, `rgba(${rgbString}, 0.5)`);
                            gradient.addColorStop(0.6, 'rgba(0, 0, 0, 0.5)');
                            gradient.addColorStop(1, 'rgba(0, 0, 0, 0.5)');
                            return gradient;
                        },
                        tension: 0.4,
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
