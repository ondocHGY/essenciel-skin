@extends('layouts.app')

@section('title', '나의 피부 분석 결과 - ' . $product->name)

@php
    // 조사 처리 함수들
    $hasFinalConsonant = function($word) {
        $lastChar = mb_substr($word, -1);
        $code = mb_ord($lastChar) - 0xAC00;
        if ($code < 0 || $code > 11171) return true; // 한글이 아니면 받침 있는 것으로 처리
        return ($code % 28) > 0;
    };

    $josa = function($word, $with, $without) use ($hasFinalConsonant) {
        return $hasFinalConsonant($word) ? $with : $without;
    };

    // 은/는
    $eunNeun = fn($word) => $josa($word, '은', '는');
    // 이/가
    $iGa = fn($word) => $josa($word, '이', '가');
    // 을/를
    $eulReul = fn($word) => $josa($word, '을', '를');
    // 과/와
    $gwaWa = fn($word) => $josa($word, '과', '와');

    $efficacyNames = \App\Models\Product::$efficacyTypes;
    $efficacyType = $result->metrics['efficacy_type'] ?? 'moisture';
    $efficacyName = $efficacyNames[$efficacyType] ?? '수분 공급';
@endphp

@section('content')
<div x-data="resultPage()" class="min-h-screen bg-gray-50">
    {{-- 헤더 --}}
    <div class="bg-gradient-to-br from-blue-600 to-indigo-700 text-white px-4 py-6">
        <div class="text-center">
            @if($product->image)
            <div class="w-20 h-20 mx-auto mb-3 rounded-xl overflow-hidden bg-white/10 shadow-lg">
                <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
            </div>
            @else
            <div class="inline-flex items-center justify-center w-14 h-14 bg-white/20 rounded-full mb-3">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            @endif
            <h1 class="text-xl font-bold">분석 완료</h1>
            <p class="text-blue-100 text-sm mt-1">{{ $product->name }}</p>
            <span class="inline-block mt-2 px-3 py-1 bg-white/20 text-sm rounded-full">
                {{ $efficacyName }} 집중 케어
            </span>
        </div>
    </div>

    {{-- 메인 탭 --}}
    <div class="sticky top-0 z-40 bg-white border-b border-gray-200 shadow-sm">
        <div class="flex">
            <button @click="activeTab = 'report'"
                    :class="activeTab === 'report' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'"
                    class="flex-1 py-3 text-center border-b-2 font-medium text-sm transition-colors">
                보고서
            </button>
            <button @click="activeTab = 'ingredients'"
                    :class="activeTab === 'ingredients' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'"
                    class="flex-1 py-3 text-center border-b-2 font-medium text-sm transition-colors">
                성분
            </button>
            <button @click="activeTab = 'nanoliposome'"
                    :class="activeTab === 'nanoliposome' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'"
                    class="flex-1 py-3 text-center border-b-2 font-medium text-sm transition-colors">
                나노리포좀
            </button>
        </div>
    </div>

    {{-- 탭 컨텐츠 --}}
    <div class="px-4 py-6">
        {{-- 보고서 탭 --}}
        <div x-show="activeTab === 'report'" x-transition:enter="transition ease-out duration-200">
            {{-- 1. 피부 반응 프로파일 요약 --}}
            @if(isset($result->skin_profile) && isset($result->skin_profile['characteristics']))
            <div class="bg-white rounded-2xl shadow-sm p-5 mb-6">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 bg-slate-800 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <h2 class="text-lg font-bold text-gray-900">피부 반응 프로파일 요약</h2>
                </div>

                <div class="bg-slate-50 rounded-xl p-4">
                    <p class="font-medium text-gray-900 mb-3">당신의 피부는</p>
                    <ul class="space-y-2">
                        @php
                            $chars = $result->skin_profile['characteristics'];
                            $charKeys = ['regeneration', 'moisture_retention', 'pigment_reactivity'];
                        @endphp
                        @foreach($charKeys as $index => $key)
                            @if(isset($chars[$key]))
                            <li class="flex items-start gap-2 text-gray-700">
                                <span class="text-slate-400 mt-0.5">•</span>
                                <span>{{ $chars[$key]['label'] }}{{ $eunNeun($chars[$key]['label']) }} {{ $chars[$key]['description'] }}</span>
                            </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            {{-- 2. 효능 발현 예측 --}}
            <div class="bg-white rounded-2xl shadow-sm p-5 mb-6">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 bg-indigo-500 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <h2 class="text-lg font-bold text-gray-900">효능 발현 예측</h2>
                </div>

                {{-- 예측 요약 문구 --}}
                @php
                    $genderPrefix = '고객';
                    // 제품에서 마일스톤 라벨 가져오기
                    $milestoneLabels = $product->getEfficacyMilestoneLabels();
                    $improvementPercent = round($result->metrics['change_percent'] ?? 0);
                @endphp

                <div class="bg-blue-50 rounded-xl p-4 mb-8">
                    <p class="text-gray-800 leading-relaxed">
                        <span class="font-semibold">{{ $genderPrefix }}님</span>이
                        <span class="font-semibold text-blue-600">{{ $product->name }}</span>{{ $eulReul($product->name) }}
                        꾸준히 사용할 경우 한달 뒤 <span class="font-bold text-blue-700">{{ $efficacyName }}{{ $iGa($efficacyName) }}
                        {{ $improvementPercent }}% 개선</span>될 것으로 예측됩니다.
                    </p>
                </div>

                {{-- 주요 마일스톤 --}}
                <div class="grid grid-cols-2 gap-3 mb-8">
                    <div class="bg-green-50 border border-green-200 rounded-xl p-3 text-center">
                        <p class="text-xs text-green-600 mb-1">{{ $milestoneLabels[0] ?? '초기 체감' }}</p>
                        <p class="text-lg font-bold text-green-700">7–10일</p>
                    </div>
                    <div class="bg-purple-50 border border-purple-200 rounded-xl p-3 text-center">
                        <p class="text-xs text-purple-600 mb-1">{{ $milestoneLabels[1] ?? '효과 안정화' }}</p>
                        <p class="text-lg font-bold text-purple-700">21–28일</p>
                    </div>
                </div>

                {{-- 단계별 효과 그래프 --}}
                <div class="mb-4">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">단계별 효과</h3>
                    <div class="h-48">
                        <canvas id="efficacyPhaseChart"></canvas>
                    </div>
                </div>

                {{-- 단계별 설명 --}}
                @php
                    // 제품에서 단계별 설명 가져오기
                    $descriptions = $product->getEfficacyPhaseDescriptions();
                @endphp

                <div class="space-y-3">
                    {{-- Phase 1: Day 0-5 --}}
                    <div class="flex items-start gap-3 p-3 bg-orange-50 border border-orange-200 rounded-xl">
                        <div class="w-14 h-8 bg-orange-500 rounded flex items-center justify-center flex-shrink-0">
                            <span class="text-xs font-bold text-white">D0–5</span>
                        </div>
                        <p class="text-sm text-orange-800">{{ $descriptions['phase1'] }}</p>
                    </div>

                    {{-- Phase 2: Day 7-10 --}}
                    <div class="flex items-start gap-3 p-3 bg-green-50 border border-green-200 rounded-xl">
                        <div class="w-14 h-8 bg-green-500 rounded flex items-center justify-center flex-shrink-0">
                            <span class="text-xs font-bold text-white">D7–10</span>
                        </div>
                        <p class="text-sm text-green-800">{{ $descriptions['phase2'] }}</p>
                    </div>

                    {{-- Phase 3: Day 21-28 (Plateau) --}}
                    <div class="flex items-start gap-3 p-3 bg-purple-50 border border-purple-200 rounded-xl">
                        <div class="w-14 h-8 bg-purple-500 rounded flex items-center justify-center flex-shrink-0">
                            <span class="text-xs font-bold text-white">D21–28</span>
                        </div>
                        <div>
                            <span class="inline-block px-2 py-0.5 bg-purple-200 text-purple-700 text-xs font-medium rounded mb-1">플래토</span>
                            <p class="text-sm text-purple-800">{{ $descriptions['phase3'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. 효능을 늦추는 생활 요인 --}}
            @if(isset($result->lifestyle_factors) && count($result->lifestyle_factors) > 0)
            @php
                // 부정적 요인만 필터링
                $negativeFactors = collect($result->lifestyle_factors)->filter(fn($f) => $f['status'] === 'negative');

                // 요인별 메시지
                $factorMessages = [
                    'sleep' => '수면 시간이 부족해',
                    'uv' => '자외선 노출이 높아',
                    'stress' => '스트레스 수준이 높아',
                    'water' => '수분 섭취량이 부족해',
                    'alcohol' => '음주 빈도가 높아',
                    'smoking' => '흡연으로 인해',
                    'skincare' => '스킨케어 단계가 부족해',
                ];
            @endphp
            @if($negativeFactors->count() > 0)
            <div class="bg-white rounded-2xl shadow-sm p-5 mb-6">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 bg-orange-500 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h2 class="text-lg font-bold text-gray-900">효능을 늦추는 생활 요인</h2>
                </div>

                <div class="space-y-4">
                    @foreach($negativeFactors as $key => $factor)
                    <div class="bg-orange-50 border border-orange-200 rounded-xl p-4">
                        <p class="font-medium text-orange-900 mb-2">{{ $factorMessages[$key] ?? $factor['name'] . $iGa($factor['name']) . ' 좋지 않아' }}</p>
                        <p class="text-sm text-orange-700 flex items-start gap-1">
                            <span>👉</span>
                            <span>{{ $efficacyName }} 효능 체감이 평균보다 늦어질 수 있습니다.</span>
                        </p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            @endif

            {{-- AI 사용 가이드 (수치 기반) --}}
            @if(isset($result->usage_guide))
            @php
                // 새 구조와 기존 구조 모두 지원
                $usage = $result->usage_guide['optimal_usage'] ?? null;
                $hasNewStructure = $usage !== null;
            @endphp
            @if($hasNewStructure)
            <div class="bg-white rounded-2xl shadow-sm p-5 mb-6">
                <div class="mb-4">
                    <h2 class="text-lg font-bold text-gray-900">AI 분석 사용 가이드</h2>
                </div>

                {{-- 최적 사용 시간대 --}}
                <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl p-4 mb-4 border border-purple-100">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">🌙</span>
                            <span class="font-semibold text-gray-900 text-sm">최적 사용 시간</span>
                        </div>
                        <span class="text-sm font-bold text-purple-700">{{ $usage['timing']['best'] ?? '저녁' }}</span>
                    </div>
                    <p class="text-xs text-gray-600 mb-3">{{ $usage['timing']['reason'] ?? '' }}</p>
                    <div class="flex gap-2">
                        <div class="flex-1 bg-white rounded-lg p-2 text-center border border-gray-100">
                            <p class="text-xs text-gray-500 mb-1">아침 효과</p>
                            <p class="text-sm font-bold {{ ($usage['timing']['morning_effect'] ?? 70) >= 90 ? 'text-green-600' : 'text-gray-700' }}">{{ $usage['timing']['morning_effect'] ?? 70 }}%</p>
                        </div>
                        <div class="flex-1 bg-white rounded-lg p-2 text-center border border-purple-200">
                            <p class="text-xs text-gray-500 mb-1">저녁 효과</p>
                            <p class="text-sm font-bold text-purple-600">{{ $usage['timing']['evening_effect'] ?? 100 }}%</p>
                        </div>
                    </div>
                </div>

                {{-- 사용 빈도 & 용량 --}}
                <div class="grid grid-cols-2 gap-3 mb-4">
                    {{-- 사용 빈도 --}}
                    <div class="bg-gray-50 rounded-xl p-3 border border-gray-100">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-base">🔄</span>
                            <span class="font-medium text-gray-900 text-xs">사용 빈도</span>
                        </div>
                        <p class="text-sm font-bold text-gray-900 mb-2">{{ $usage['frequency']['recommended'] ?? '2회/일' }}</p>
                        <div class="space-y-1.5">
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500">1회/일</span>
                                <span class="text-xs font-semibold text-orange-600">{{ $usage['frequency']['once_effect'] ?? 60 }}%</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500">2회/일</span>
                                <span class="text-xs font-semibold text-green-600">{{ $usage['frequency']['twice_effect'] ?? 100 }}%</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500">+주1회 마스크팩</span>
                                <span class="text-xs font-semibold text-blue-600">{{ $usage['frequency']['with_mask_effect'] ?? 115 }}%</span>
                            </div>
                        </div>
                    </div>

                    {{-- 적정 사용량 --}}
                    <div class="bg-gray-50 rounded-xl p-3 border border-gray-100">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-base">💧</span>
                            <span class="font-medium text-gray-900 text-xs">적정 사용량</span>
                        </div>
                        <p class="text-sm font-bold text-gray-900 mb-2">{{ $usage['amount']['optimal'] ?? '500원 동전' }}</p>
                        <div class="space-y-1.5">
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500">적은량</span>
                                <span class="text-xs font-semibold text-red-500">{{ $usage['amount']['less_effect'] ?? 60 }}%</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500">적정량</span>
                                <span class="text-xs font-semibold text-green-600">{{ $usage['amount']['optimal_effect'] ?? 100 }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 효과 향상 권장사항 --}}
                @if(isset($result->usage_guide['recommendations']) && count($result->usage_guide['recommendations']) > 0)
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        <p class="font-semibold text-gray-900 text-sm">추가 효과 향상 방법</p>
                    </div>
                    <div class="space-y-2">
                        @foreach($result->usage_guide['recommendations'] as $index => $rec)
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-3 border border-green-100">
                            <div class="flex items-start gap-2">
                                <span class="text-lg flex-shrink-0">{{ $rec['icon'] }}</span>
                                <p class="text-sm text-gray-800 leading-relaxed">
                                    @if($index % 2 == 0 && isset($rec['effect_boost']))
                                    <span class="font-medium">{{ $rec['action_short'] }}</span>{{ $eulReul($rec['action_short']) }} 할 경우 효과가 최대 <span class="font-bold text-green-700">{{ $rec['effect_boost'] }}% 향상</span>될 것으로 예상됩니다.
                                    @else
                                    <span class="font-medium">{{ $rec['action_short'] }}</span>{{ $eulReul($rec['action_short']) }} 할 경우 효능 도달시점이 최대 <span class="font-bold text-blue-700">{{ $rec['days_saved'] }}일 단축</span>될 것으로 예상됩니다.
                                    @endif
                                </p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @else
            {{-- 기존 구조 폴백 (이전 데이터 호환) --}}
            <div class="bg-white rounded-2xl shadow-sm p-5 mb-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">AI 맞춤 사용 가이드</h2>
                <div class="space-y-3">
                    @if(isset($result->usage_guide['timing']))
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 text-sm">사용 시기</p>
                            <p class="text-gray-600 text-sm">{{ $result->usage_guide['timing'] }}</p>
                        </div>
                    </div>
                    @endif
                    @if(isset($result->usage_guide['frequency']))
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 text-sm">사용 빈도</p>
                            <p class="text-gray-600 text-sm">{{ $result->usage_guide['frequency'] }}</p>
                        </div>
                    </div>
                    @endif
                    @if(isset($result->usage_guide['amount']))
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 text-sm">적정 사용량</p>
                            <p class="text-gray-600 text-sm">{{ $result->usage_guide['amount'] }}</p>
                        </div>
                    </div>
                    @endif
                </div>
                @if(isset($result->usage_guide['method']))
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <p class="font-medium text-gray-900 text-sm mb-2">사용 방법</p>
                    <p class="text-gray-600 text-sm">{{ $result->usage_guide['method'] }}</p>
                </div>
                @endif
            </div>
            @endif
            @endif
        </div>

        {{-- 성분 탭 --}}
        <div x-show="activeTab === 'ingredients'" x-transition:enter="transition ease-out duration-200">
            @if($product->ingredient_details && count($product->ingredient_details) > 0)
                {{-- 성분 상세 정보가 있을 때 --}}
                <div class="bg-white rounded-2xl shadow-sm p-5 mb-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">핵심 성분 분석</h2>
                    <div class="space-y-4">
                        @foreach($product->ingredient_details as $ingredient)
                        <div class="border border-gray-100 rounded-xl p-4">
                            <h3 class="font-semibold text-gray-900">{{ $ingredient['name'] ?? '' }}</h3>
                            <p class="text-sm text-gray-600 mt-1">{{ $ingredient['description'] ?? '' }}</p>
                            @if(isset($ingredient['effect']))
                            <div class="mt-2 flex items-center gap-2">
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded">{{ $ingredient['effect'] }}</span>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            @else
                {{-- 성분 정보가 없을 때 --}}
                <div class="bg-white rounded-2xl shadow-sm p-8 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">성분 정보 준비 중</h3>
                    <p class="text-sm text-gray-500">곧 상세한 성분 분석 정보가<br>업데이트될 예정입니다.</p>

                    @if($product->ingredients && count($product->ingredients) > 0)
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <p class="text-sm text-gray-500 mb-3">주요 성분 목록</p>
                        <div class="flex flex-wrap gap-2 justify-center">
                            @foreach($product->ingredients as $ingredient)
                            <span class="px-3 py-1 bg-gray-100 text-gray-700 text-sm rounded-full">{{ $ingredient }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- 나노리포좀 탭 --}}
        <div x-show="activeTab === 'nanoliposome'" x-transition:enter="transition ease-out duration-200">
            @if($product->nanoliposome_info && count($product->nanoliposome_info) > 0)
                {{-- 나노리포좀 정보가 있을 때 --}}
                <div class="bg-white rounded-2xl shadow-sm p-5 mb-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">나노리포좀 기술</h2>
                    <div class="space-y-4">
                        @if(isset($product->nanoliposome_info['description']))
                        <p class="text-gray-600">{{ $product->nanoliposome_info['description'] }}</p>
                        @endif
                        @if(isset($product->nanoliposome_info['benefits']))
                        <div class="space-y-2">
                            @foreach($product->nanoliposome_info['benefits'] as $benefit)
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <p class="text-gray-700">{{ $benefit }}</p>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            @else
                {{-- 나노리포좀 정보가 없을 때 --}}
                <div class="bg-white rounded-2xl shadow-sm p-8 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">나노리포좀 정보 준비 중</h3>
                    <p class="text-sm text-gray-500">혁신적인 나노리포좀 기술에 대한<br>상세 정보가 곧 업데이트됩니다.</p>

                    <div class="mt-6 bg-blue-50 rounded-xl p-4 text-left">
                        <h4 class="font-medium text-blue-900 text-sm mb-2">나노리포좀이란?</h4>
                        <p class="text-sm text-blue-700">나노리포좀은 유효 성분을 피부 깊숙이 전달하는 첨단 기술입니다. 미세한 입자가 피부 장벽을 통과해 성분의 흡수율을 극대화합니다.</p>
                    </div>
                </div>
            @endif
        </div>

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
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
function resultPage() {
    return {
        activeTab: 'report',

        init() {
            this.$nextTick(() => {
                this.initEfficacyPhaseChart();
            });
        },

        initEfficacyPhaseChart() {
            const canvas = document.getElementById('efficacyPhaseChart');
            if (!canvas || typeof Chart === 'undefined') return;

            const metrics = @json($result->metrics ?? []);
            const daily = metrics.daily || {};
            const initial = metrics.initial || 0;
            const final = metrics.final || 0;
            const unit = metrics.unit || '';

            // 3개 단계: D0-5 (orange), D7-10 (green), D21-28 (purple)
            const labels = ['D0', 'D5', 'D7', 'D14', 'D21', 'D28'];
            const dayKeys = [0, 5, 7, 14, 21, 28];

            // 실제 수치 사용 (daily 데이터 활용)
            const getValueForDay = (day) => {
                if (day === 0) return initial;
                if (daily[day]) return daily[day];
                // 보간
                const keys = Object.keys(daily).map(Number).sort((a, b) => a - b);
                for (let i = 0; i < keys.length - 1; i++) {
                    if (day > keys[i] && day < keys[i + 1]) {
                        const ratio = (day - keys[i]) / (keys[i + 1] - keys[i]);
                        return daily[keys[i]] + ratio * (daily[keys[i + 1]] - daily[keys[i]]);
                    }
                }
                // 0일 이전 또는 첫 키 이전이면 initial 반환
                if (keys.length > 0 && day < keys[0]) {
                    return initial + (daily[keys[0]] - initial) * (day / keys[0]);
                }
                return initial;
            };

            const data = dayKeys.map(day => getValueForDay(day));

            // Y축 범위 계산 및 소수점 자릿수 결정
            const range = final - initial;
            const decimals = range < 1 ? 2 : (range < 10 ? 1 : 0);
            const minVal = range < 1 ? Math.floor(initial * 10) / 10 : Math.floor(initial * 0.9);
            const maxVal = range < 1 ? Math.ceil(final * 10) / 10 : Math.ceil(final * 1.1);

            // Phase 1 data (D0-5): orange
            const phase1Data = data.map((v, i) => i <= 1 ? v : null);
            // Phase 2 data (D5-14): green - connect from D5
            const phase2Data = data.map((v, i) => (i >= 1 && i <= 3) ? v : null);
            // Phase 3 data (D14-28): purple - connect from D14
            const phase3Data = data.map((v, i) => i >= 3 ? v : null);

            new Chart(canvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: '준비 단계 (D0-5)',
                            data: phase1Data,
                            borderColor: 'rgb(249, 115, 22)',
                            backgroundColor: 'rgba(249, 115, 22, 0.2)',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointBackgroundColor: 'rgb(249, 115, 22)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            spanGaps: false
                        },
                        {
                            label: '체감 단계 (D7-10)',
                            data: phase2Data,
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.2)',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointBackgroundColor: 'rgb(34, 197, 94)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            spanGaps: false
                        },
                        {
                            label: '안정화 단계 (D21-28)',
                            data: phase3Data,
                            borderColor: 'rgb(139, 92, 246)',
                            backgroundColor: 'rgba(139, 92, 246, 0.2)',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointBackgroundColor: 'rgb(139, 92, 246)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            spanGaps: false
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
                                callback: (value) => value.toFixed(decimals) + (unit ? ' ' + unit : '')
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        x: {
                            ticks: { font: { size: 10 } },
                            grid: {
                                display: false
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
                description: '{{ $product->name }} 28일 사용 효과 예측 결과를 확인해보세요!',
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
