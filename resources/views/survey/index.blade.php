@extends('layouts.app')

@section('title', '피부 정보 입력 - ' . $product->name)

@section('content')
{{-- 분석 로딩 오버레이 --}}
<div x-data="surveyForm(@js($surveyOptions ?? []))" x-cloak>
    <div x-show="isAnalyzing"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-50 bg-gradient-to-br from-blue-900 via-indigo-900 to-purple-900 flex items-center justify-center">
        <div class="text-center px-8 max-w-sm">
            {{-- AI 아이콘 애니메이션 --}}
            <div class="relative w-32 h-32 mx-auto mb-8">
                {{-- 외부 링 --}}
                <div class="absolute inset-0 border-4 border-blue-400/30 rounded-full animate-ping"></div>
                <div class="absolute inset-2 border-4 border-purple-400/40 rounded-full animate-pulse"></div>
                <div class="absolute inset-4 border-2 border-cyan-400/50 rounded-full animate-spin" style="animation-duration: 3s;"></div>

                {{-- 중앙 AI 아이콘 --}}
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-500/50">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>

                {{-- 스캔 라인 --}}
                <div class="absolute inset-0 overflow-hidden rounded-full">
                    <div class="h-1 bg-gradient-to-r from-transparent via-cyan-400 to-transparent animate-scan"></div>
                </div>
            </div>

            {{-- 분석 텍스트 --}}
            <h2 class="text-xl font-bold text-white mb-2">AI 피부 분석 중</h2>
            <p class="text-blue-200 text-sm mb-6" x-text="analyzeStatusText"></p>

            {{-- 진행바 --}}
            <div class="w-full bg-white/20 rounded-full h-2 mb-4 overflow-hidden">
                <div class="h-full bg-gradient-to-r from-cyan-400 via-blue-500 to-purple-500 rounded-full transition-all duration-300 ease-out"
                     :style="{ width: analyzeProgress + '%' }"></div>
            </div>

            {{-- 분석 항목 --}}
            <div class="space-y-2 text-left">
                <template x-for="(item, index) in analyzeSteps" :key="index">
                    <div class="flex items-center gap-2 text-sm"
                         :class="currentAnalyzeStep > index ? 'text-green-400' : currentAnalyzeStep === index ? 'text-white' : 'text-white/40'">
                        <template x-if="currentAnalyzeStep > index">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </template>
                        <template x-if="currentAnalyzeStep === index">
                            <div class="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></div>
                        </template>
                        <template x-if="currentAnalyzeStep < index">
                            <div class="w-4 h-4 border border-current/40 rounded-full"></div>
                        </template>
                        <span x-text="item"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>

<div class="px-4 py-6">
    {{-- 진행 바 --}}
    <div class="mb-6">
        <div class="flex justify-between text-sm text-gray-500 mb-2">
            <span>Step <span x-text="step"></span> / 3</span>
            <span x-text="Math.round((step / 3) * 100) + '%'"></span>
        </div>
        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
            <div class="h-full bg-blue-600 rounded-full transition-all duration-300"
                 :style="{ width: (step / 3) * 100 + '%' }"></div>
        </div>
    </div>

    {{-- 헤더 --}}
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-900" x-text="stepTitles[step - 1]"></h1>
        <p class="text-gray-500 text-sm mt-1" x-text="stepDescriptions[step - 1]"></p>
    </div>

    <form action="{{ route('survey.store', $product->code) }}" method="POST" @submit.prevent="submitForm">
        @csrf

        {{-- Step 1: 기본 정보 --}}
        <div x-show="step === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
            <div class="space-y-6">
                {{-- 연령대 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">연령대</label>
                    <div class="grid grid-cols-3 gap-2">
                        <template x-for="age in ageGroups" :key="age.value">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="age_group" :value="age.value" x-model="formData.age_group" class="peer sr-only">
                                <div class="p-3 text-center border-2 rounded-xl text-sm transition-all peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:text-blue-700 border-gray-200 hover:border-gray-300">
                                    <span x-text="age.label"></span>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- 피부 타입 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">피부 타입</label>
                    <div class="grid grid-cols-3 gap-2">
                        <template x-for="skin in skinTypes" :key="skin.value">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="skin_type" :value="skin.value" x-model="formData.skin_type" class="peer sr-only">
                                <div class="p-3 text-center border-2 rounded-xl text-sm transition-all peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:text-blue-700 border-gray-200 hover:border-gray-300">
                                    <span x-text="skin.label"></span>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- 성별 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">성별</label>
                    <div class="grid grid-cols-3 gap-2">
                        <template x-for="g in genders" :key="g.value">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="gender" :value="g.value" x-model="formData.gender" class="peer sr-only">
                                <div class="p-3 text-center border-2 rounded-xl text-sm transition-all peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:text-blue-700 border-gray-200 hover:border-gray-300">
                                    <span x-text="g.label"></span>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 2: 생활환경 --}}
        <div x-show="step === 2" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
            <div class="space-y-6">
                {{-- 수면 시간 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">평균 수면 시간</label>
                    <div class="grid grid-cols-3 gap-2">
                        <template x-for="item in sleepHours" :key="item.value">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="sleep_hours" :value="item.value" x-model="formData.sleep_hours" class="peer sr-only">
                                <div class="p-3 text-center border-2 rounded-xl text-sm transition-all peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:text-blue-700 border-gray-200 hover:border-gray-300">
                                    <span x-text="item.label"></span>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- 자외선 노출 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">자외선 노출 정도</label>
                    <div class="grid grid-cols-3 gap-2">
                        <template x-for="item in uvExposure" :key="item.value">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="uv_exposure" :value="item.value" x-model="formData.uv_exposure" class="peer sr-only">
                                <div class="p-3 text-center border-2 rounded-xl text-sm transition-all peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:text-blue-700 border-gray-200 hover:border-gray-300">
                                    <span x-text="item.label"></span>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- 스트레스 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">스트레스 수준</label>
                    <div class="grid grid-cols-3 gap-2">
                        <template x-for="item in stressLevels" :key="item.value">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="stress_level" :value="item.value" x-model="formData.stress_level" class="peer sr-only">
                                <div class="p-3 text-center border-2 rounded-xl text-sm transition-all peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:text-blue-700 border-gray-200 hover:border-gray-300">
                                    <span x-text="item.label"></span>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- 수분 섭취 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">하루 수분 섭취량</label>
                    <div class="grid grid-cols-3 gap-2">
                        <template x-for="item in waterIntake" :key="item.value">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="water_intake" :value="item.value" x-model="formData.water_intake" class="peer sr-only">
                                <div class="p-3 text-center border-2 rounded-xl text-sm transition-all peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:text-blue-700 border-gray-200 hover:border-gray-300">
                                    <span x-text="item.label"></span>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- 음주/흡연 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">음주/흡연</label>
                    <div class="grid grid-cols-3 gap-2">
                        <template x-for="item in smokingDrinking" :key="item.value">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="smoking_drinking" :value="item.value" x-model="formData.smoking_drinking" class="peer sr-only">
                                <div class="p-3 text-center border-2 rounded-xl text-sm transition-all peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:text-blue-700 border-gray-200 hover:border-gray-300">
                                    <span x-text="item.label"></span>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 3: 스킨케어 습관 + 피부 고민 --}}
        <div x-show="step === 3" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
            <div class="space-y-6">
                {{-- 케어 단계 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">스킨케어 단계 수</label>
                    <div class="grid grid-cols-3 gap-2">
                        <template x-for="item in careSteps" :key="item.value">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="care_steps" :value="item.value" x-model="formData.care_steps" class="peer sr-only">
                                <div class="p-3 text-center border-2 rounded-xl text-sm transition-all peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:text-blue-700 border-gray-200 hover:border-gray-300">
                                    <span x-text="item.label"></span>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- 규칙성 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">스킨케어 규칙성</label>
                    <div class="grid grid-cols-3 gap-2">
                        <template x-for="item in consistencyOptions" :key="item.value">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="consistency" :value="item.value" x-model="formData.consistency" class="peer sr-only">
                                <div class="p-3 text-center border-2 rounded-xl text-sm transition-all peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:text-blue-700 border-gray-200 hover:border-gray-300">
                                    <span x-text="item.label"></span>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- 피부 고민 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">피부 고민 (복수 선택 가능)</label>
                    <div class="grid grid-cols-2 gap-3">
                        <template x-for="concern in concerns" :key="concern.value">
                            <label class="relative cursor-pointer">
                                <input type="checkbox" :name="'concerns[]'" :value="concern.value" x-model="formData.concerns" class="peer sr-only">
                                <div class="p-4 text-center border-2 rounded-xl transition-all peer-checked:border-blue-600 peer-checked:bg-blue-50 border-gray-200 hover:border-gray-300">
                                    <span class="text-2xl block mb-1" x-text="concern.icon"></span>
                                    <span class="text-sm" :class="formData.concerns.includes(concern.value) ? 'text-blue-700 font-medium' : 'text-gray-700'" x-text="concern.label"></span>
                                </div>
                            </label>
                        </template>
                    </div>
                    <p class="text-xs text-gray-400 mt-3 text-center">최소 1개 이상 선택해주세요</p>
                </div>

                {{-- 만족도 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        현재 피부 상태 만족도: <span class="text-blue-600 font-bold" x-text="formData.satisfaction"></span>점
                    </label>
                    <input type="range" name="satisfaction" min="1" max="10" x-model="formData.satisfaction"
                           class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600">
                    <div class="flex justify-between text-xs text-gray-400 mt-1">
                        <span>불만족</span>
                        <span>매우 만족</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 버튼 영역 --}}
        <div class="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-gray-100">
            <div class="max-w-lg mx-auto flex gap-3">
                <button type="button" x-show="step > 1" @click="prevStep"
                        class="flex-1 py-4 border-2 border-gray-200 text-gray-700 font-semibold rounded-xl transition-colors hover:bg-gray-50">
                    이전
                </button>
                <button type="button" x-show="step < 3" @click="nextStep" :disabled="!canProceed"
                        class="flex-1 py-4 bg-blue-600 text-white font-semibold rounded-xl transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed hover:bg-blue-700">
                    다음
                </button>
                <button type="submit" x-show="step === 3" :disabled="!canSubmit || isSubmitting"
                        class="flex-1 py-4 bg-blue-600 text-white font-semibold rounded-xl transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed hover:bg-blue-700">
                    <span x-show="!isSubmitting">분석 시작</span>
                    <span x-show="isSubmitting">분석 중...</span>
                </button>
            </div>
        </div>
    </form>

    {{-- 하단 여백 --}}
    <div class="h-24"></div>
</div>
</div>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    @keyframes scan {
        0% { transform: translateY(-100%); }
        100% { transform: translateY(3200%); }
    }
    .animate-scan {
        animation: scan 2s ease-in-out infinite;
    }
</style>
@endpush

@push('scripts')
<script>
function surveyForm(options = {}) {
    // 기본값 설정 (DB에서 옵션을 가져오지 못한 경우 fallback)
    const defaultOptions = {
        age_groups: [
            { value: '10대', label: '10대' },
            { value: '20대초반', label: '20대 초반' },
            { value: '20대후반', label: '20대 후반' },
            { value: '30대', label: '30대' },
            { value: '40대', label: '40대' },
            { value: '50대이상', label: '50대 이상' }
        ],
        skin_types: [
            { value: '건성', label: '건성' },
            { value: '지성', label: '지성' },
            { value: '복합성', label: '복합성' },
            { value: '민감성', label: '민감성' },
            { value: '중성', label: '중성' }
        ],
        genders: [
            { value: 'female', label: '여성' },
            { value: 'male', label: '남성' },
            { value: 'other', label: '기타' }
        ],
        concerns: [
            { value: 'wrinkle', label: '주름', icon: '🔲' },
            { value: 'elasticity', label: '탄력저하', icon: '📉' },
            { value: 'pigmentation', label: '색소침착', icon: '🔵' },
            { value: 'pore', label: '모공', icon: '⚫' },
            { value: 'acne', label: '여드름', icon: '🔴' },
            { value: 'dryness', label: '건조함', icon: '🏜️' },
            { value: 'redness', label: '홍조', icon: '🌹' },
            { value: 'dullness', label: '칙칙함', icon: '😶' }
        ],
        sleep_hours: [
            { value: 'under6', label: '6시간 미만' },
            { value: '6to8', label: '6-8시간' },
            { value: 'over8', label: '8시간 이상' }
        ],
        uv_exposure: [
            { value: 'indoor', label: '실내 위주' },
            { value: 'normal', label: '보통' },
            { value: 'outdoor', label: '실외 많음' }
        ],
        stress_levels: [
            { value: 'low', label: '낮음' },
            { value: 'medium', label: '보통' },
            { value: 'high', label: '높음' }
        ],
        water_intake: [
            { value: 'under1L', label: '1L 미만' },
            { value: '1to2L', label: '1-2L' },
            { value: 'over2L', label: '2L 이상' }
        ],
        smoking_drinking: [
            { value: 'none', label: '안함' },
            { value: 'sometimes', label: '가끔' },
            { value: 'often', label: '자주' }
        ],
        care_steps: [
            { value: '3이하', label: '3단계 이하' },
            { value: '5단계', label: '5단계' },
            { value: '7이상', label: '7단계 이상' }
        ],
        consistency_options: [
            { value: 'sometimes', label: '가끔' },
            { value: 'regular', label: '규칙적' },
            { value: 'always', label: '매일' }
        ]
    };

    // DB 옵션이 있으면 사용, 없으면 기본값 사용
    const merged = { ...defaultOptions, ...options };

    return {
        step: 1,
        isSubmitting: false,
        isAnalyzing: false,
        analyzeProgress: 0,
        currentAnalyzeStep: 0,
        analyzeStatusText: '피부 데이터를 수집하고 있습니다...',
        analyzeSteps: [
            '피부 프로필 분석 중',
            '생활 패턴 데이터 처리 중',
            '12,847개 피부 데이터와 비교 중',
            'AI 예측 모델 적용 중',
            '맞춤 결과 생성 중'
        ],
        stepTitles: [
            '기본 정보를 알려주세요',
            '생활 환경을 알려주세요',
            '스킨케어 습관을 알려주세요'
        ],
        stepDescriptions: [
            '정확한 분석을 위해 기본 정보가 필요해요',
            '생활 습관도 피부에 영향을 미쳐요',
            '마지막 단계예요!'
        ],
        formData: {
            age_group: '',
            skin_type: '',
            gender: '',
            concerns: [],
            sleep_hours: '',
            uv_exposure: '',
            stress_level: '',
            water_intake: '',
            smoking_drinking: '',
            care_steps: '',
            consistency: '',
            satisfaction: 5
        },
        // DB에서 가져온 옵션 사용
        ageGroups: merged.age_groups,
        skinTypes: merged.skin_types,
        genders: merged.genders,
        concerns: merged.concerns,
        sleepHours: merged.sleep_hours,
        uvExposure: merged.uv_exposure,
        stressLevels: merged.stress_levels,
        waterIntake: merged.water_intake,
        smokingDrinking: merged.smoking_drinking,
        careSteps: merged.care_steps,
        consistencyOptions: merged.consistency_options,

        get canProceed() {
            if (this.step === 1) {
                return this.formData.age_group && this.formData.skin_type && this.formData.gender;
            }
            if (this.step === 2) {
                return this.formData.sleep_hours && this.formData.uv_exposure &&
                       this.formData.stress_level && this.formData.water_intake &&
                       this.formData.smoking_drinking;
            }
            return true;
        },

        get canSubmit() {
            return this.formData.care_steps && this.formData.consistency && this.formData.concerns.length > 0;
        },

        nextStep() {
            if (this.canProceed && this.step < 3) {
                this.step++;
            }
        },

        prevStep() {
            if (this.step > 1) {
                this.step--;
            }
        },

        async submitForm() {
            if (!this.canSubmit || this.isSubmitting) return;

            this.isSubmitting = true;
            this.isAnalyzing = true;
            this.analyzeProgress = 0;
            this.currentAnalyzeStep = 0;

            const form = document.querySelector('form');
            const formData = new FormData(form);

            // FormData에 값 추가
            Object.keys(this.formData).forEach(key => {
                if (key === 'concerns') {
                    this.formData.concerns.forEach(c => formData.append('concerns[]', c));
                } else {
                    formData.set(key, this.formData[key]);
                }
            });

            // 분석 애니메이션 시작
            const statusTexts = [
                '피부 데이터를 수집하고 있습니다...',
                '생활 패턴을 분석하고 있습니다...',
                '유사 피부 타입 데이터와 비교 중...',
                'AI 예측 모델을 적용하고 있습니다...',
                '맞춤 결과를 생성하고 있습니다...'
            ];

            // 애니메이션 진행 (총 3초)
            const animationDuration = 3000;
            const stepDuration = animationDuration / this.analyzeSteps.length;

            // 백그라운드에서 실제 API 호출
            const fetchPromise = fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            // 애니메이션 실행
            for (let i = 0; i < this.analyzeSteps.length; i++) {
                this.currentAnalyzeStep = i;
                this.analyzeStatusText = statusTexts[i];

                // 각 단계 내에서 프로그레스 애니메이션
                const startProgress = (i / this.analyzeSteps.length) * 100;
                const endProgress = ((i + 1) / this.analyzeSteps.length) * 100;

                await this.animateProgress(startProgress, endProgress, stepDuration);
            }

            // 완료 표시
            this.currentAnalyzeStep = this.analyzeSteps.length;
            this.analyzeProgress = 100;
            this.analyzeStatusText = '분석이 완료되었습니다!';

            // API 응답 대기
            try {
                const response = await fetchPromise;
                await new Promise(resolve => setTimeout(resolve, 500)); // 완료 표시 잠시 보여주기

                if (response.redirected) {
                    window.location.href = response.url;
                }
            } catch (error) {
                console.error('Error:', error);
                this.isSubmitting = false;
                this.isAnalyzing = false;
            }
        },

        animateProgress(start, end, duration) {
            return new Promise(resolve => {
                const startTime = performance.now();
                const animate = (currentTime) => {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);

                    this.analyzeProgress = start + (end - start) * this.easeOutQuad(progress);

                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    } else {
                        resolve();
                    }
                };
                requestAnimationFrame(animate);
            });
        },

        easeOutQuad(t) {
            return t * (2 - t);
        }
    };
}
</script>
@endpush
