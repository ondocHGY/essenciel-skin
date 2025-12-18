@extends('layouts.admin')

@section('title', '제품 추가')

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- 페이지 헤더 --}}
    <x-page-header
        title="제품 추가"
        description="새로운 제품을 등록합니다"
        :backUrl="route('admin.products.index')" />

    {{-- 플래시 메시지 --}}
    <x-flash-messages />

    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">기본 정보</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">제품 코드</label>
                    <input type="text" name="code" value="{{ old('code') }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="PROD-001">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">제품명</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">브랜드</label>
                    <input type="text" name="brand" value="{{ old('brand') }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">카테고리</label>
                    <input type="text" name="category" value="{{ old('category') }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="세럼, 크림, 에센스 등">
                </div>
            </div>

            <!-- 효능 타입 선택 -->
            <div class="mt-6 pt-6 border-t border-gray-100" x-data="{ selected: '{{ old('efficacy_type', 'moisture') }}' }">
                <label class="block text-sm font-medium text-gray-700 mb-2">집중 효능 타입</label>
                <p class="text-xs text-gray-500 mb-3">이 제품이 집중적으로 타겟하는 효능을 선택하세요. 결과 페이지에서 해당 효능을 중심으로 분석됩니다.</p>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                    <!-- 수분 공급 -->
                    <label class="relative cursor-pointer" @click="selected = 'moisture'">
                        <input type="radio" name="efficacy_type" value="moisture" class="sr-only" :checked="selected === 'moisture'">
                        <div class="p-4 text-center border-2 rounded-xl transition-all border-gray-200 hover:border-gray-300"
                             :class="selected === 'moisture' ? 'border-blue-500 bg-blue-50' : ''">
                            <span class="text-2xl block mb-1">💧</span>
                            <span class="text-sm font-medium text-gray-700">수분 공급</span>
                        </div>
                    </label>
                    <!-- 탄력 개선 -->
                    <label class="relative cursor-pointer" @click="selected = 'elasticity'">
                        <input type="radio" name="efficacy_type" value="elasticity" class="sr-only" :checked="selected === 'elasticity'">
                        <div class="p-4 text-center border-2 rounded-xl transition-all border-gray-200 hover:border-gray-300"
                             :class="selected === 'elasticity' ? 'border-purple-500 bg-purple-50' : ''">
                            <span class="text-2xl block mb-1">✨</span>
                            <span class="text-sm font-medium text-gray-700">탄력 개선</span>
                        </div>
                    </label>
                    <!-- 피부톤 개선 -->
                    <label class="relative cursor-pointer" @click="selected = 'tone'">
                        <input type="radio" name="efficacy_type" value="tone" class="sr-only" :checked="selected === 'tone'">
                        <div class="p-4 text-center border-2 rounded-xl transition-all border-gray-200 hover:border-gray-300"
                             :class="selected === 'tone' ? 'border-orange-500 bg-orange-50' : ''">
                            <span class="text-2xl block mb-1">🎨</span>
                            <span class="text-sm font-medium text-gray-700">피부톤 개선</span>
                        </div>
                    </label>
                    <!-- 모공 케어 -->
                    <label class="relative cursor-pointer" @click="selected = 'pore'">
                        <input type="radio" name="efficacy_type" value="pore" class="sr-only" :checked="selected === 'pore'">
                        <div class="p-4 text-center border-2 rounded-xl transition-all border-gray-200 hover:border-gray-300"
                             :class="selected === 'pore' ? 'border-green-500 bg-green-50' : ''">
                            <span class="text-2xl block mb-1">⭕</span>
                            <span class="text-sm font-medium text-gray-700">모공 케어</span>
                        </div>
                    </label>
                    <!-- 주름 개선 -->
                    <label class="relative cursor-pointer" @click="selected = 'wrinkle'">
                        <input type="radio" name="efficacy_type" value="wrinkle" class="sr-only" :checked="selected === 'wrinkle'">
                        <div class="p-4 text-center border-2 rounded-xl transition-all border-gray-200 hover:border-gray-300"
                             :class="selected === 'wrinkle' ? 'border-pink-500 bg-pink-50' : ''">
                            <span class="text-2xl block mb-1">〰️</span>
                            <span class="text-sm font-medium text-gray-700">주름 개선</span>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- 제품 이미지 -->
        <div class="bg-white rounded-xl shadow-sm p-6" x-data="imageUploader()">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">제품 이미지</h2>
            <p class="text-sm text-gray-500 mb-4">제품 페이지에 표시될 이미지입니다 (JPG, PNG, GIF, WEBP / 최대 2MB)</p>

            <div class="flex items-start gap-6">
                <!-- 이미지 미리보기 -->
                <div class="w-32 h-32 flex-shrink-0 bg-gray-100 rounded-xl border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden">
                    <template x-if="!preview">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </template>
                    <template x-if="preview">
                        <img :src="preview" class="w-full h-full object-cover">
                    </template>
                </div>

                <!-- 업로드 버튼 -->
                <div class="flex-1">
                    <label class="block">
                        <span class="sr-only">이미지 선택</span>
                        <input type="file" name="image" accept="image/*" @change="handleFileSelect($event)"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                    </label>
                    <p class="mt-2 text-xs text-gray-400">권장 크기: 500x500px 이상, 정사각형 비율</p>
                </div>
            </div>
        </div>

        <script>
            function imageUploader() {
                return {
                    preview: null,
                    handleFileSelect(event) {
                        const file = event.target.files[0];
                        if (file) {
                            this.preview = URL.createObjectURL(file);
                        }
                    }
                }
            }
        </script>

        <!-- 기대 효과 곡선 -->
        <div class="bg-white rounded-xl shadow-sm p-6" x-data="baseCurveEditor()">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">기대 효과 곡선 (Base Curve)</h2>
                    <p class="text-sm text-gray-500 mt-1">각 항목별 주차별 예상 개선율(0-100%)을 설정합니다</p>
                </div>
                <button type="button" @click="showHelp = !showHelp" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </button>
            </div>

            <!-- 도움말 -->
            <div x-show="showHelp" x-transition class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h4 class="font-medium text-blue-900 mb-2">효과 곡선이란?</h4>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li>• 각 수치는 해당 주차에 <strong>최대 개선량의 몇 %</strong>가 달성되는지를 의미합니다</li>
                    <li>• 예: 수분 12주차 = 90% → 최대 수분 개선량(+25%)의 90%인 +22.5% 개선</li>
                    <li>• 이 값에 사용자의 설문 응답(나이, 피부타입 등)에 따른 modifier가 곱해집니다</li>
                </ul>
            </div>

            <!-- 카테고리별 입력 -->
            @php
                $categories = [
                    'moisture' => ['label' => '💧 수분', 'desc' => '피부 수분량 증가 (최대 +25%)', 'color' => 'blue'],
                    'elasticity' => ['label' => '✨ 탄력', 'desc' => '콜라겐 밀도 증가 (최대 +0.9 mg/cm²)', 'color' => 'green'],
                    'tone' => ['label' => '🎨 피부톤', 'desc' => '멜라닌 지수 감소 (최대 -80 M.I)', 'color' => 'yellow'],
                    'pore' => ['label' => '⭕ 모공', 'desc' => '모공 면적 감소 (최대 -0.5 mm²)', 'color' => 'purple'],
                    'wrinkle' => ['label' => '〰️ 주름', 'desc' => '주름 깊이 감소 (최대 -35 μm)', 'color' => 'pink'],
                ];
                $defaultValues = [10, 25, 40, 60, 80];
            @endphp

            @foreach($categories as $key => $config)
            <div class="mb-6 last:mb-0 p-4 bg-{{ $config['color'] }}-50 rounded-lg border border-{{ $config['color'] }}-100">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <label class="text-sm font-medium text-gray-900">{{ $config['label'] }}</label>
                        <p class="text-xs text-gray-500">{{ $config['desc'] }}</p>
                    </div>
                    <span class="text-sm font-mono bg-white px-2 py-1 rounded border" x-text="curves.{{ $key }}[4] + '%'">
                        {{ $defaultValues[4] }}%
                    </span>
                </div>
                <div class="grid grid-cols-5 gap-3">
                    @foreach([0, 1, 2, 3, 4] as $i)
                    <div>
                        <label class="text-xs text-gray-500 block mb-1 text-center">{{ [1, 2, 4, 8, 12][$i] }}주</label>
                        <input type="number" name="base_curve[{{ $key }}][]" min="0" max="100" required
                               value="{{ old("base_curve.$key.$i", $defaultValues[$i]) }}"
                               x-model="curves.{{ $key }}[{{ $i }}]"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-center focus:ring-2 focus:ring-{{ $config['color'] }}-500 focus:border-{{ $config['color'] }}-500">
                    </div>
                    @endforeach
                </div>
                <!-- 미니 진행 바 -->
                <div class="flex gap-1 mt-3">
                    @foreach([0, 1, 2, 3, 4] as $i)
                    <div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-{{ $config['color'] }}-400 rounded-full transition-all duration-300"
                             x-bind:style="'width: ' + curves.{{ $key }}[{{ $i }}] + '%'"></div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach

            <!-- 프리셋 버튼 -->
            <div class="mt-6 pt-4 border-t border-gray-200">
                <p class="text-sm text-gray-600 mb-3">빠른 설정:</p>
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="applyPreset('gradual')"
                            class="px-3 py-1.5 text-xs bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        점진적 개선
                    </button>
                    <button type="button" @click="applyPreset('fast')"
                            class="px-3 py-1.5 text-xs bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        빠른 효과
                    </button>
                    <button type="button" @click="applyPreset('moisture')"
                            class="px-3 py-1.5 text-xs bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition-colors">
                        수분 집중
                    </button>
                    <button type="button" @click="applyPreset('antiaging')"
                            class="px-3 py-1.5 text-xs bg-purple-100 hover:bg-purple-200 text-purple-700 rounded-lg transition-colors">
                        안티에이징
                    </button>
                </div>
            </div>
        </div>

        <script>
            function baseCurveEditor() {
                return {
                    showHelp: false,
                    curves: {
                        moisture: [10, 25, 40, 60, 80],
                        elasticity: [10, 25, 40, 60, 80],
                        tone: [10, 25, 40, 60, 80],
                        pore: [10, 25, 40, 60, 80],
                        wrinkle: [10, 25, 40, 60, 80],
                    },
                    applyPreset(type) {
                        const presets = {
                            gradual: {
                                moisture: [10, 25, 45, 65, 85],
                                elasticity: [8, 20, 38, 58, 75],
                                tone: [10, 25, 42, 62, 80],
                                pore: [5, 15, 30, 50, 65],
                                wrinkle: [5, 15, 30, 48, 65],
                            },
                            fast: {
                                moisture: [25, 50, 70, 85, 95],
                                elasticity: [20, 40, 60, 78, 90],
                                tone: [20, 42, 62, 80, 92],
                                pore: [15, 35, 55, 72, 85],
                                wrinkle: [12, 30, 50, 68, 82],
                            },
                            moisture: {
                                moisture: [20, 45, 65, 82, 95],
                                elasticity: [8, 18, 32, 48, 62],
                                tone: [8, 20, 35, 52, 68],
                                pore: [5, 12, 25, 40, 55],
                                wrinkle: [5, 12, 22, 38, 52],
                            },
                            antiaging: {
                                moisture: [12, 28, 45, 62, 78],
                                elasticity: [15, 35, 55, 75, 90],
                                tone: [12, 30, 50, 70, 85],
                                pore: [8, 20, 38, 55, 72],
                                wrinkle: [15, 32, 52, 72, 88],
                            },
                        };
                        if (presets[type]) {
                            this.curves = { ...presets[type] };
                        }
                    }
                }
            }
        </script>

        <!-- 효능 설정 (결과 페이지용) -->
        <div class="bg-white rounded-xl shadow-sm p-6" x-data="efficacySettings()">
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">효능 발현 예측 설정</h2>
                    <p class="text-sm text-gray-500 mt-1">결과 페이지에 표시될 효능 단계별 설명을 설정합니다</p>
                </div>
            </div>

            <!-- 마일스톤 라벨 -->
            <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-100">
                <h3 class="text-sm font-medium text-gray-900 mb-3">마일스톤 라벨</h3>
                <p class="text-xs text-gray-500 mb-3">결과 페이지 상단에 표시되는 기간별 효과 라벨입니다</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-gray-600 block mb-1">7-10일 (초기 체감)</label>
                        <input type="text" name="efficacy_milestones[0]"
                               value="{{ old('efficacy_milestones.0', '') }}"
                               x-model="milestones[0]"
                               placeholder="예: 초기 톤 개선 체감"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600 block mb-1">21-28일 (안정화)</label>
                        <input type="text" name="efficacy_milestones[1]"
                               value="{{ old('efficacy_milestones.1', '') }}"
                               x-model="milestones[1]"
                               placeholder="예: 색소 완화 안정화"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            <!-- 단계별 설명 -->
            <div class="space-y-4">
                <h3 class="text-sm font-medium text-gray-900">단계별 효과 설명</h3>
                <p class="text-xs text-gray-500 mb-3">그래프 아래에 표시되는 단계별 상세 설명입니다</p>

                <!-- Phase 1: D0-5 -->
                <div class="p-4 bg-amber-50 rounded-lg border border-amber-200">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2 py-1 bg-amber-400 text-white text-xs font-bold rounded">D0–5</span>
                        <span class="text-sm font-medium text-amber-800">준비 단계</span>
                    </div>
                    <textarea name="efficacy_phases[phase1]" rows="2"
                              x-model="phases.phase1"
                              placeholder="예: 유효 성분이 피부에 전달되며, 멜라닌 생성 신호를 완화할 준비 단계에 들어갑니다."
                              class="w-full px-3 py-2 border border-amber-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">{{ old('efficacy_phases.phase1', '') }}</textarea>
                </div>

                <!-- Phase 2: D7-10 -->
                <div class="p-4 bg-emerald-50 rounded-lg border border-emerald-200">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2 py-1 bg-emerald-500 text-white text-xs font-bold rounded">D7–10</span>
                        <span class="text-sm font-medium text-emerald-800">체감 단계</span>
                    </div>
                    <textarea name="efficacy_phases[phase2]" rows="2"
                              x-model="phases.phase2"
                              placeholder="예: 피부 톤 변화가 눈으로 느껴지기 시작하며, 칙칙함이 점차 완화됩니다."
                              class="w-full px-3 py-2 border border-emerald-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">{{ old('efficacy_phases.phase2', '') }}</textarea>
                </div>

                <!-- Phase 3: D21-28 -->
                <div class="p-4 bg-purple-50 rounded-lg border border-purple-200">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2 py-1 bg-purple-500 text-white text-xs font-bold rounded">D21–28</span>
                        <span class="text-sm font-medium text-purple-800">안정화 단계 (플래토)</span>
                    </div>
                    <textarea name="efficacy_phases[phase3]" rows="2"
                              x-model="phases.phase3"
                              placeholder="예: 색소 완화 효과가 안정화되며, 균일한 톤이 유지되는 단계입니다."
                              class="w-full px-3 py-2 border border-purple-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500">{{ old('efficacy_phases.phase3', '') }}</textarea>
                </div>
            </div>

            <!-- 프리셋 버튼 -->
            <div class="mt-6 pt-4 border-t border-gray-200">
                <p class="text-sm text-gray-600 mb-3">효능 타입별 기본값 적용:</p>
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="applyPreset('moisture')"
                            class="px-3 py-1.5 text-xs bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        수분 공급
                    </button>
                    <button type="button" @click="applyPreset('elasticity')"
                            class="px-3 py-1.5 text-xs bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        탄력 개선
                    </button>
                    <button type="button" @click="applyPreset('tone')"
                            class="px-3 py-1.5 text-xs bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        피부톤 개선
                    </button>
                    <button type="button" @click="applyPreset('pore')"
                            class="px-3 py-1.5 text-xs bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        모공 케어
                    </button>
                    <button type="button" @click="applyPreset('wrinkle')"
                            class="px-3 py-1.5 text-xs bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        주름 개선
                    </button>
                </div>
            </div>
        </div>

        <script>
            function efficacySettings() {
                return {
                    milestones: ['', ''],
                    phases: {
                        phase1: '',
                        phase2: '',
                        phase3: ''
                    },
                    applyPreset(type) {
                        const presets = {
                            moisture: {
                                milestones: ['초기 보습 체감', '수분 밸런스 안정화'],
                                phases: {
                                    phase1: '유효 성분이 피부에 전달되며, 수분 흡수 준비 단계에 들어갑니다.',
                                    phase2: '피부 수분도 변화가 느껴지기 시작하며, 건조함이 점차 완화됩니다.',
                                    phase3: '수분 밸런스 효과가 안정화되며, 촉촉한 피부가 유지되는 단계입니다.'
                                }
                            },
                            elasticity: {
                                milestones: ['초기 탄력 체감', '탄력 효과 안정화'],
                                phases: {
                                    phase1: '유효 성분이 피부에 전달되며, 콜라겐 합성 촉진 준비 단계에 들어갑니다.',
                                    phase2: '피부 탄력 변화가 느껴지기 시작하며, 처짐이 점차 개선됩니다.',
                                    phase3: '탄력 개선 효과가 안정화되며, 탱탱한 피부가 유지되는 단계입니다.'
                                }
                            },
                            tone: {
                                milestones: ['초기 톤 개선 체감', '색소 완화 안정화'],
                                phases: {
                                    phase1: '유효 성분이 피부에 전달되며, 멜라닌 생성 신호를 완화할 준비 단계에 들어갑니다.',
                                    phase2: '피부 톤 변화가 눈으로 느껴지기 시작하며, 칙칙함이 점차 완화됩니다.',
                                    phase3: '색소 완화 효과가 안정화되며, 균일한 톤이 유지되는 단계입니다.'
                                }
                            },
                            pore: {
                                milestones: ['초기 모공 케어 체감', '모공 개선 안정화'],
                                phases: {
                                    phase1: '유효 성분이 피부에 전달되며, 모공 정화 준비 단계에 들어갑니다.',
                                    phase2: '모공 축소 변화가 눈으로 느껴지기 시작하며, 피지 분비가 조절됩니다.',
                                    phase3: '모공 케어 효과가 안정화되며, 매끈한 피부결이 유지되는 단계입니다.'
                                }
                            },
                            wrinkle: {
                                milestones: ['초기 주름 완화 체감', '주름 개선 안정화'],
                                phases: {
                                    phase1: '유효 성분이 피부에 전달되며, 표피 재생 촉진 준비 단계에 들어갑니다.',
                                    phase2: '주름 완화 변화가 느껴지기 시작하며, 미세주름이 점차 개선됩니다.',
                                    phase3: '주름 개선 효과가 안정화되며, 매끄러운 피부결이 유지되는 단계입니다.'
                                }
                            }
                        };
                        if (presets[type]) {
                            this.milestones = [...presets[type].milestones];
                            this.phases = {...presets[type].phases};
                        }
                    }
                }
            }
        </script>

        <!-- 효능 측정 기준값 설정 -->
        <div class="bg-white rounded-xl shadow-sm p-6" x-data="efficacyMetricsSettings()">
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">효능 측정 기준값</h2>
                    <p class="text-sm text-gray-500 mt-1">결과 페이지에 표시되는 수치 기준을 설정합니다. 비워두면 기본값이 적용됩니다.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- 지표명 & 단위 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">지표명</label>
                    <input type="text" name="efficacy_metrics[name]" x-model="metrics.name"
                           placeholder="예: 피부 수분도"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-400 mt-1">기본값: 효능 타입에 따라 자동 설정</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">단위</label>
                    <input type="text" name="efficacy_metrics[unit]" x-model="metrics.unit"
                           placeholder="예: %"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-400 mt-1">기본값: 효능 타입에 따라 자동 설정</p>
                </div>
            </div>

            <div class="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                <h3 class="text-sm font-medium text-gray-900 mb-4">초기값 범위 (Baseline)</h3>
                <p class="text-xs text-gray-500 mb-4">사용자 연령대에 따라 이 범위 내에서 초기값이 결정됩니다 (젊을수록 좋은 값).</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-gray-600 block mb-1">최소값 (최상 상태)</label>
                        <input type="number" step="0.01" name="efficacy_metrics[baseline_min]" x-model="metrics.baseline_min"
                               placeholder="예: 32"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600 block mb-1">최대값 (보통 상태)</label>
                        <input type="number" step="0.01" name="efficacy_metrics[baseline_max]" x-model="metrics.baseline_max"
                               placeholder="예: 48"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            <div class="mt-4 p-4 bg-green-50 rounded-lg border border-green-200">
                <h3 class="text-sm font-medium text-gray-900 mb-3">목표 개선량</h3>
                <p class="text-xs text-gray-500 mb-3">28일 후 최대 개선 가능한 수치입니다. 사용자 조건에 따라 이 값의 일부가 적용됩니다.</p>
                <div class="max-w-xs">
                    <input type="number" step="0.01" name="efficacy_metrics[target_improvement]" x-model="metrics.target_improvement"
                           placeholder="예: 18"
                           class="w-full px-3 py-2 border border-green-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">설명</label>
                <input type="text" name="efficacy_metrics[description]" x-model="metrics.description"
                       placeholder="예: 각질층 수분 함유량 측정"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- 효능 타입별 기본값 프리셋 -->
            <div class="mt-6 pt-4 border-t border-gray-200">
                <p class="text-sm text-gray-600 mb-3">효능 타입별 기본값 적용:</p>
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="applyMetricsPreset('moisture')"
                            class="px-3 py-1.5 text-xs bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        수분 공급
                    </button>
                    <button type="button" @click="applyMetricsPreset('elasticity')"
                            class="px-3 py-1.5 text-xs bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        탄력 개선
                    </button>
                    <button type="button" @click="applyMetricsPreset('tone')"
                            class="px-3 py-1.5 text-xs bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        피부톤 개선
                    </button>
                    <button type="button" @click="applyMetricsPreset('pore')"
                            class="px-3 py-1.5 text-xs bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        모공 케어
                    </button>
                    <button type="button" @click="applyMetricsPreset('wrinkle')"
                            class="px-3 py-1.5 text-xs bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        주름 개선
                    </button>
                    <button type="button" @click="clearMetrics()"
                            class="px-3 py-1.5 text-xs bg-red-50 hover:bg-red-100 text-red-600 rounded-lg transition-colors">
                        초기화
                    </button>
                </div>
            </div>
        </div>

        <script>
            function efficacyMetricsSettings() {
                return {
                    metrics: {
                        name: '',
                        unit: '',
                        baseline_min: '',
                        baseline_max: '',
                        target_improvement: '',
                        description: ''
                    },
                    applyMetricsPreset(type) {
                        const presets = {
                            moisture: { name: '피부 수분도', unit: '%', baseline_min: 32, baseline_max: 48, target_improvement: 18, description: '각질층 수분 함유량 측정' },
                            elasticity: { name: '피부 탄력도', unit: 'R값', baseline_min: 0.65, baseline_max: 0.85, target_improvement: 0.15, description: '피부 탄성 회복력 지수' },
                            tone: { name: '피부 밝기', unit: 'L*값', baseline_min: 58, baseline_max: 68, target_improvement: 5, description: '멜라닌 지수 기반 밝기' },
                            pore: { name: '모공 축소율', unit: '%', baseline_min: 0, baseline_max: 0, target_improvement: 25, description: '모공 면적 감소 비율' },
                            wrinkle: { name: '주름 개선도', unit: '%', baseline_min: 0, baseline_max: 0, target_improvement: 30, description: '주름 깊이 감소 비율' }
                        };
                        if (presets[type]) {
                            this.metrics = {...presets[type]};
                        }
                    },
                    clearMetrics() {
                        this.metrics = { name: '', unit: '', baseline_min: '', baseline_max: '', target_improvement: '', description: '' };
                    }
                }
            }
        </script>

        <!-- 제품 소개 페이지 설정 -->
        <div class="bg-white rounded-xl shadow-sm p-6" x-data="introPageSettings()">
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">제품 소개 페이지 설정</h2>
                    <p class="text-sm text-gray-500 mt-1">QR 코드 스캔 시 보이는 제품 소개 페이지의 AI 리뷰 분석 내용을 설정합니다</p>
                </div>
            </div>

            <!-- 분석 리뷰 수 -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">분석한 리뷰 수</label>
                <input type="number" name="intro_review_count" x-model="reviewCount"
                       placeholder="예: 12847"
                       class="w-full max-w-xs px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-gray-400 mt-1">비워두면 기본값(12,847)이 표시됩니다</p>
            </div>

            <!-- AI 리뷰 분석 지표 -->
            <div class="mb-6 p-4 bg-slate-50 rounded-lg border border-slate-200">
                <h3 class="text-sm font-medium text-gray-900 mb-3">AI 리뷰 분석 지표</h3>
                <p class="text-xs text-gray-500 mb-4">막대 그래프로 표시되는 5개 지표를 설정합니다. 비워두면 효능 타입에 따른 기본값이 적용됩니다.</p>

                <div class="space-y-3">
                    <template x-for="(metric, index) in metrics" :key="index">
                        <div class="flex items-center gap-3">
                            <div class="flex-1">
                                <input type="text" :name="'intro_metrics[' + index + '][name]'" x-model="metric.name"
                                       placeholder="지표명 (예: 보습력)"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div class="w-20">
                                <select :name="'intro_metrics[' + index + '][value]'" x-model="metric.value"
                                        class="w-full px-2 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="0">0</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                </select>
                            </div>
                            <div class="w-32">
                                <select :name="'intro_metrics[' + index + '][color]'" x-model="metric.color"
                                        class="w-full px-2 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="bg-blue-500">파랑</option>
                                    <option value="bg-indigo-500">인디고</option>
                                    <option value="bg-purple-500">보라</option>
                                    <option value="bg-pink-500">핑크</option>
                                    <option value="bg-rose-500">로즈</option>
                                    <option value="bg-red-500">빨강</option>
                                    <option value="bg-orange-500">주황</option>
                                    <option value="bg-amber-500">앰버</option>
                                    <option value="bg-yellow-500">노랑</option>
                                    <option value="bg-green-500">초록</option>
                                    <option value="bg-emerald-500">에메랄드</option>
                                    <option value="bg-teal-500">틸</option>
                                    <option value="bg-cyan-500">시안</option>
                                </select>
                            </div>
                            <!-- 미리보기 -->
                            <div class="flex gap-0.5">
                                <template x-for="i in 5" :key="i">
                                    <div class="w-4 h-4 rounded"
                                         :class="i <= parseInt(metric.value) ? metric.color : 'bg-gray-200'"></div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-4 flex gap-2">
                    <button type="button" @click="applyMetricsPreset()"
                            class="px-3 py-1.5 text-xs bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition-colors">
                        효능타입 기본값 적용
                    </button>
                    <button type="button" @click="clearMetrics()"
                            class="px-3 py-1.5 text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg transition-colors">
                        초기화
                    </button>
                </div>
            </div>

            <!-- AI 분석 요약 문구 -->
            <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-medium text-gray-900">AI 분석 요약 문구</h3>
                        <p class="text-xs text-gray-500 mt-1">제품 소개 페이지에 표시되는 AI 분석 요약 문구입니다. 등록된 문구 중 랜덤으로 2~3개가 노출됩니다.</p>
                    </div>
                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full" x-text="summary.filter(s => s && s.trim()).length + '개 등록'"></span>
                </div>

                <div class="space-y-2">
                    <template x-for="(item, index) in summary" :key="index">
                        <div class="flex items-start gap-2">
                            <span class="text-xs text-gray-400 mt-2.5 w-6 flex-shrink-0" x-text="(index + 1) + '.'"></span>
                            <textarea :name="'intro_summary[' + index + ']'" rows="2" x-model="summary[index]"
                                      placeholder="AI 분석 요약 문구를 입력하세요..."
                                      class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                            <button type="button" @click="removeSummary(index)"
                                    class="mt-1.5 p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded transition-colors"
                                    title="삭제">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" @click="addSummary()"
                            class="px-3 py-1.5 text-xs bg-green-100 hover:bg-green-200 text-green-700 rounded-lg transition-colors flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        문구 추가
                    </button>
                    <button type="button" @click="applySummaryPreset()"
                            class="px-3 py-1.5 text-xs bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition-colors">
                        효능타입 기본값 적용 (10개)
                    </button>
                    <button type="button" @click="clearSummary()"
                            class="px-3 py-1.5 text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg transition-colors">
                        전체 초기화
                    </button>
                </div>
            </div>
        </div>

        <script>
            function introPageSettings() {
                const metricsPresets = {
                    moisture: [
                        { name: '보습력', value: 5, color: 'bg-blue-500' },
                        { name: '보습지속력', value: 4, color: 'bg-indigo-500' },
                        { name: '끈적임', value: 4, color: 'bg-cyan-500' },
                        { name: '효과 체감', value: 4, color: 'bg-emerald-500' },
                        { name: '자극여부', value: 1, color: 'bg-rose-500' },
                    ],
                    elasticity: [
                        { name: '탄력 개선', value: 5, color: 'bg-purple-500' },
                        { name: '리프팅감', value: 4, color: 'bg-indigo-500' },
                        { name: '탱탱함', value: 4, color: 'bg-pink-500' },
                        { name: '효과 체감', value: 4, color: 'bg-emerald-500' },
                        { name: '자극여부', value: 1, color: 'bg-rose-500' },
                    ],
                    tone: [
                        { name: '톤 개선', value: 5, color: 'bg-orange-500' },
                        { name: '화사함', value: 4, color: 'bg-amber-500' },
                        { name: '균일함', value: 4, color: 'bg-yellow-500' },
                        { name: '효과 체감', value: 4, color: 'bg-emerald-500' },
                        { name: '자극여부', value: 1, color: 'bg-rose-500' },
                    ],
                    pore: [
                        { name: '모공 축소', value: 5, color: 'bg-green-500' },
                        { name: '피지 조절', value: 4, color: 'bg-teal-500' },
                        { name: '매끄러움', value: 4, color: 'bg-cyan-500' },
                        { name: '효과 체감', value: 4, color: 'bg-emerald-500' },
                        { name: '자극여부', value: 1, color: 'bg-rose-500' },
                    ],
                    wrinkle: [
                        { name: '주름 개선', value: 5, color: 'bg-pink-500' },
                        { name: '탄력감', value: 4, color: 'bg-purple-500' },
                        { name: '매끄러움', value: 4, color: 'bg-indigo-500' },
                        { name: '효과 체감', value: 4, color: 'bg-emerald-500' },
                        { name: '자극여부', value: 1, color: 'bg-rose-500' },
                    ],
                };
                const summaryPresets = {
                    moisture: [
                        '다수의 리뷰에서 시간이 지나도 수분감이 유지된다는 반응이 반복적으로 관측되었습니다.',
                        '흡수가 빠르고 끈적임 없이 촉촉하다는 평가가 87%를 차지했습니다.',
                        '건조한 환경에서도 보습력이 오래 유지된다는 후기가 72% 이상이었습니다.',
                        '세안 후 바로 사용했을 때 즉각적인 수분 충전을 느꼈다는 평가가 많았습니다.',
                        '에어컨/히터 환경에서 피부 당김이 줄었다는 반응이 다수 확인되었습니다.',
                        '다음 단계 제품의 흡수력을 높여준다는 의견이 65% 이상이었습니다.',
                        '메이크업 전 사용 시 화장이 들뜨지 않는다는 후기가 반복적으로 관측되었습니다.',
                        '저녁에 바르고 아침까지 촉촉함이 유지된다는 평가가 78%였습니다.',
                        '피부가 푸석해 보이지 않고 건강해 보인다는 반응이 다수였습니다.',
                        '계절 변화에도 피부 컨디션이 안정적이라는 후기가 많았습니다.',
                    ],
                    elasticity: [
                        '사용 2~3주 후 피부가 탱탱해지고 탄력이 개선되었다는 리뷰가 다수 관측되었습니다.',
                        '리프팅 효과와 피부결 개선을 체감했다는 평가가 82%를 차지했습니다.',
                        '볼 라인이 올라간 느낌이 든다는 후기가 68% 이상이었습니다.',
                        '꾸준히 사용할수록 피부가 탱탱해진다는 반응이 반복적으로 관측되었습니다.',
                        '아침에 일어났을 때 피부가 처지지 않는다는 평가가 많았습니다.',
                        '눈가 탄력이 개선되어 눈이 또렷해 보인다는 의견이 다수였습니다.',
                        '사진 찍을 때 피부가 더 탱탱해 보인다는 후기가 관측되었습니다.',
                        '메이크업이 주름에 끼지 않는다는 반응이 75%였습니다.',
                        '피부가 얇아지는 느낌 없이 탄력이 생긴다는 평가가 많았습니다.',
                        '팔자주름 부위가 덜 꺼져 보인다는 후기가 다수 확인되었습니다.',
                    ],
                    tone: [
                        '꾸준한 사용 후 피부톤이 맑아지고 화사해졌다는 리뷰가 반복적으로 관측되었습니다.',
                        '칙칙함 개선과 피부 균일함에 대한 긍정 평가가 85%를 차지했습니다.',
                        '잡티와 기미 부위가 옅어졌다는 후기가 73% 이상이었습니다.',
                        '노메이크업 상태에서도 피부가 맑아 보인다는 반응이 다수였습니다.',
                        '피부 전체적으로 톤이 균일해졌다는 평가가 많았습니다.',
                        '칙칙했던 눈 밑이 밝아졌다는 후기가 반복적으로 관측되었습니다.',
                        '여드름 자국이 옅어지는 속도가 빨라졌다는 의견이 67%였습니다.',
                        '화장을 하지 않아도 피부가 투명해 보인다는 평가가 많았습니다.',
                        '사용 3주 차부터 피부톤 변화를 체감했다는 후기가 다수였습니다.',
                        '피부가 뽀얗고 건강해 보인다는 반응이 반복적으로 관측되었습니다.',
                    ],
                    pore: [
                        '모공이 눈에 띄게 축소되고 피부결이 매끄러워졌다는 리뷰가 다수 관측되었습니다.',
                        '피지 조절 효과와 모공 케어에 대한 긍정 평가가 79%를 차지했습니다.',
                        '코와 볼 주변 모공이 덜 눈에 띈다는 후기가 71% 이상이었습니다.',
                        '오후에도 피지가 덜 올라온다는 반응이 반복적으로 관측되었습니다.',
                        '블랙헤드가 줄어들었다는 평가가 65%를 차지했습니다.',
                        '화장이 모공에 끼지 않고 매끄럽게 발린다는 의견이 다수였습니다.',
                        '피부결이 부드러워지고 매끈해졌다는 후기가 많았습니다.',
                        '번들거림이 줄어 화장 지속력이 좋아졌다는 반응이 68%였습니다.',
                        'T존 부위 피지 컨트롤이 확실하다는 평가가 다수 관측되었습니다.',
                        '모공 주변 피부톤도 함께 개선되었다는 후기가 많았습니다.',
                    ],
                    wrinkle: [
                        '눈가와 이마 주름이 옅어졌다는 리뷰가 반복적으로 관측되었습니다.',
                        '잔주름 개선과 피부 매끄러움에 대한 긍정 평가가 81%를 차지했습니다.',
                        '웃을 때 생기는 주름이 덜 깊어 보인다는 후기가 74% 이상이었습니다.',
                        '이마 주름이 눈에 띄게 완화되었다는 반응이 다수 관측되었습니다.',
                        '눈가 잔주름이 매끄러워졌다는 평가가 많았습니다.',
                        '미간 주름 부위가 부드러워졌다는 후기가 67%였습니다.',
                        '팔자주름이 덜 깊어 보인다는 의견이 다수 확인되었습니다.',
                        '피부가 전체적으로 매끄럽고 탄탄해졌다는 반응이 많았습니다.',
                        '메이크업이 주름에 끼지 않는다는 후기가 반복적으로 관측되었습니다.',
                        '피부 나이가 어려 보인다는 주변 반응이 있었다는 평가가 많았습니다.',
                    ],
                };

                return {
                    reviewCount: '',
                    metrics: [
                        { name: '', value: 5, color: 'bg-blue-500' },
                        { name: '', value: 4, color: 'bg-indigo-500' },
                        { name: '', value: 4, color: 'bg-cyan-500' },
                        { name: '', value: 4, color: 'bg-emerald-500' },
                        { name: '', value: 1, color: 'bg-rose-500' },
                    ],
                    summary: [''],
                    selectedEfficacyType: 'moisture',
                    applyMetricsPreset() {
                        // 현재 선택된 효능 타입 가져오기
                        const typeInput = document.querySelector('input[name="efficacy_type"]:checked');
                        const type = typeInput ? typeInput.value : 'moisture';
                        this.metrics = JSON.parse(JSON.stringify(metricsPresets[type] || metricsPresets.moisture));
                    },
                    clearMetrics() {
                        this.metrics = [
                            { name: '', value: 5, color: 'bg-blue-500' },
                            { name: '', value: 4, color: 'bg-indigo-500' },
                            { name: '', value: 4, color: 'bg-cyan-500' },
                            { name: '', value: 4, color: 'bg-emerald-500' },
                            { name: '', value: 1, color: 'bg-rose-500' },
                        ];
                    },
                    addSummary() {
                        this.summary.push('');
                    },
                    removeSummary(index) {
                        if (this.summary.length > 1) {
                            this.summary.splice(index, 1);
                        } else {
                            this.summary[0] = '';
                        }
                    },
                    applySummaryPreset() {
                        // 현재 선택된 효능 타입 가져오기
                        const typeInput = document.querySelector('input[name="efficacy_type"]:checked');
                        const type = typeInput ? typeInput.value : 'moisture';
                        this.summary = [...(summaryPresets[type] || summaryPresets.moisture)];
                    },
                    clearSummary() {
                        this.summary = [''];
                    }
                };
            }
        </script>

        <div class="flex gap-4">
            <x-button type="submit" variant="primary" size="xl" class="flex-1">
                제품 등록
            </x-button>
            <x-button :href="route('admin.products.index')" variant="outline" size="xl" class="px-8">
                취소
            </x-button>
        </div>
    </form>
</div>
@endsection
