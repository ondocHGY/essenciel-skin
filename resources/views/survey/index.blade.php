@extends('layouts.app')

@section('title', '피부 정보 입력 - ' . $product->name)

@section('content')
{{-- 분석 로딩 오버레이 --}}
<div x-data="surveyForm()" x-cloak>
    <div x-show="isAnalyzing"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-50 bg-gradient-to-br from-blue-900 via-indigo-900 to-purple-900 flex items-center justify-center">
        <div class="text-center px-8 max-w-sm">
            {{-- AI 아이콘 애니메이션 --}}
            <div class="relative w-32 h-32 mx-auto mb-8">
                <div class="absolute inset-0 border-4 border-blue-400/30 rounded-full animate-ping"></div>
                <div class="absolute inset-2 border-4 border-purple-400/40 rounded-full animate-pulse"></div>
                <div class="absolute inset-4 border-2 border-cyan-400/50 rounded-full animate-spin" style="animation-duration: 3s;"></div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-500/50">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
                <div class="absolute inset-0 overflow-hidden rounded-full">
                    <div class="h-1 bg-gradient-to-r from-transparent via-cyan-400 to-transparent animate-scan"></div>
                </div>
            </div>

            <h2 class="text-xl font-bold text-white mb-2">AI 피부 분석 중</h2>
            <p class="text-blue-200 text-sm mb-6" x-text="analyzeStatusText"></p>

            <div class="w-full bg-white/20 rounded-full h-2 mb-4 overflow-hidden">
                <div class="h-full bg-gradient-to-r from-cyan-400 via-blue-500 to-purple-500 rounded-full transition-all duration-300 ease-out"
                     :style="{ width: analyzeProgress + '%' }"></div>
            </div>

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
            <span>Step <span x-text="currentQuestion + 1"></span> / <span x-text="questions.length"></span></span>
            <span x-text="Math.round(((currentQuestion + 1) / questions.length) * 100) + '%'"></span>
        </div>
        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
            <div class="h-full bg-blue-600 rounded-full transition-all duration-300"
                 :style="{ width: ((currentQuestion + 1) / questions.length) * 100 + '%' }"></div>
        </div>
    </div>

    {{-- 헤더 --}}
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-900" x-text="questions[currentQuestion].title"></h1>
        <p class="text-gray-500 text-sm mt-1" x-text="questions[currentQuestion].subtitle"></p>
    </div>

    <form action="{{ route('survey.store', $product->code) }}" method="POST" @submit.prevent="submitForm">
        @csrf

        {{-- 질문 카드 --}}
        <div class="min-h-[300px]">
            <template x-for="(question, qIndex) in questions" :key="qIndex">
                <div x-show="currentQuestion === qIndex"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-x-4"
                     x-transition:enter-end="opacity-100 translate-x-0">
                    <div class="space-y-3">
                        <template x-for="option in question.options" :key="option.value">
                            <label class="block cursor-pointer">
                                <input type="radio"
                                       :name="question.name"
                                       :value="option.value"
                                       x-model="formData[question.name]"
                                       class="peer sr-only">
                                <div class="p-4 border-2 rounded-xl transition-all peer-checked:border-blue-600 peer-checked:bg-blue-50 border-gray-200 hover:border-gray-300">
                                    <div class="flex items-center gap-3">
                                        <div class="w-5 h-5 rounded-full border-2 border-gray-300 peer-checked:border-blue-600 flex items-center justify-center transition-all"
                                             :class="formData[question.name] === option.value ? 'border-blue-600 bg-blue-600' : ''">
                                            <div x-show="formData[question.name] === option.value" class="w-2 h-2 rounded-full bg-white"></div>
                                        </div>
                                        <span class="font-medium" :class="formData[question.name] === option.value ? 'text-blue-700' : 'text-gray-700'" x-text="option.label"></span>
                                    </div>
                                    <p x-show="option.desc" class="text-xs text-gray-500 mt-2 ml-8" x-text="option.desc"></p>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- 버튼 영역 --}}
        <div class="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-gray-100">
            <div class="max-w-lg mx-auto flex gap-3">
                <button type="button" x-show="currentQuestion > 0" @click="prevQuestion"
                        class="flex-1 py-4 border-2 border-gray-200 text-gray-700 font-semibold rounded-xl transition-colors hover:bg-gray-50">
                    이전
                </button>
                <button type="button" x-show="currentQuestion < questions.length - 1" @click="nextQuestion" :disabled="!canProceed"
                        class="flex-1 py-4 bg-blue-600 text-white font-semibold rounded-xl transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed hover:bg-blue-700">
                    다음
                </button>
                <button type="submit" x-show="currentQuestion === questions.length - 1" :disabled="!canProceed || isSubmitting"
                        class="flex-1 py-4 bg-blue-600 text-white font-semibold rounded-xl transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed hover:bg-blue-700">
                    <span x-show="!isSubmitting">분석 시작</span>
                    <span x-show="isSubmitting">분석 중...</span>
                </button>
            </div>
        </div>
    </form>

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
function surveyForm() {
    // 서버에서 전달받은 질문 데이터 (DB 기반)
    const questionsFromServer = @json($questions);

    // formData 초기화 (질문 키 기반)
    const initialFormData = {};
    questionsFromServer.forEach(q => {
        initialFormData[q.name] = '';
    });

    return {
        currentQuestion: 0,
        isSubmitting: false,
        isAnalyzing: false,
        analyzeProgress: 0,
        currentAnalyzeStep: 0,
        analyzeStatusText: '피부 데이터를 수집하고 있습니다...',
        analyzeSteps: [
            '피부 프로필 분석 중',
            '생활 패턴 데이터 처리 중',
            '{{ number_format($product->intro_review_count ?? 12847) }}개 피부 데이터와 비교 중',
            'AI 예측 모델 적용 중',
            '맞춤 결과 생성 중'
        ],

        questions: questionsFromServer,
        formData: initialFormData,

        get canProceed() {
            const currentQ = this.questions[this.currentQuestion];
            return this.formData[currentQ.name] !== '';
        },

        get totalQuestions() {
            return this.questions.length;
        },

        get isLastQuestion() {
            return this.currentQuestion === this.questions.length - 1;
        },

        nextQuestion() {
            if (this.canProceed && this.currentQuestion < this.questions.length - 1) {
                this.currentQuestion++;
            }
        },

        prevQuestion() {
            if (this.currentQuestion > 0) {
                this.currentQuestion--;
            }
        },

        async submitForm() {
            if (!this.canProceed || this.isSubmitting) return;

            this.isSubmitting = true;
            this.isAnalyzing = true;
            this.analyzeProgress = 0;
            this.currentAnalyzeStep = 0;

            const form = document.querySelector('form');
            const formData = new FormData(form);

            Object.keys(this.formData).forEach(key => {
                formData.set(key, this.formData[key]);
            });

            const statusTexts = [
                '피부 데이터를 수집하고 있습니다...',
                '생활 패턴을 분석하고 있습니다...',
                '유사 피부 타입 데이터와 비교 중...',
                'AI 예측 모델을 적용하고 있습니다...',
                '맞춤 결과를 생성하고 있습니다...'
            ];

            const animationDuration = 3000;
            const stepDuration = animationDuration / this.analyzeSteps.length;

            const fetchPromise = fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            for (let i = 0; i < this.analyzeSteps.length; i++) {
                this.currentAnalyzeStep = i;
                this.analyzeStatusText = statusTexts[i];

                const startProgress = (i / this.analyzeSteps.length) * 100;
                const endProgress = ((i + 1) / this.analyzeSteps.length) * 100;

                await this.animateProgress(startProgress, endProgress, stepDuration);
            }

            this.currentAnalyzeStep = this.analyzeSteps.length;
            this.analyzeProgress = 100;
            this.analyzeStatusText = '분석이 완료되었습니다!';

            try {
                const response = await fetchPromise;
                const data = await response.json();
                await new Promise(resolve => setTimeout(resolve, 500));

                if (response.ok && data.success && data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }

                // 에러 처리
                console.error('Response error:', response.status, data);

                if (data.errors) {
                    const firstError = Object.values(data.errors)[0];
                    alert(Array.isArray(firstError) ? firstError[0] : firstError);
                } else if (data.message) {
                    alert(data.message);
                } else {
                    alert('오류가 발생했습니다. 다시 시도해주세요.');
                }
                this.isSubmitting = false;
                this.isAnalyzing = false;
            } catch (error) {
                console.error('Error:', error);
                alert('오류가 발생했습니다. 다시 시도해주세요.');
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
