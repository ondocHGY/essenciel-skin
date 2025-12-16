@extends('layouts.admin')

@section('title', '제품 추가')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- 페이지 헤더 -->
    <div class="flex items-center gap-3 mb-8">
        <a href="{{ route('admin.products.index') }}" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">제품 추가</h1>
            <p class="text-gray-600 mt-1">새로운 제품을 등록합니다</p>
        </div>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
        <ul class="list-disc pl-5 space-y-1">
            @foreach($errors->all() as $error)
                <li class="text-sm">{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('admin.products.store') }}" method="POST" class="space-y-6">
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
        </div>

        <!-- 주요 성분 -->
        <div class="bg-white rounded-xl shadow-sm p-6" x-data="ingredientsEditor()">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">주요 성분</h2>
                    <p class="text-sm text-gray-500 mt-1">제품의 핵심 성분을 입력합니다 (제품 상세 페이지에 표시됨)</p>
                </div>
            </div>

            <!-- 성분 목록 -->
            <div class="space-y-2 mb-4">
                <template x-for="(ingredient, index) in ingredients" :key="index">
                    <div class="flex items-center gap-2">
                        <input type="text" :name="'ingredients[]'" x-model="ingredients[index]"
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="성분명 입력">
                        <button type="button" @click="removeIngredient(index)"
                                class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </template>
            </div>

            <!-- 성분 추가 버튼 -->
            <button type="button" @click="addIngredient()"
                    class="w-full py-2 border-2 border-dashed border-gray-300 text-gray-500 rounded-lg hover:border-blue-400 hover:text-blue-500 transition-colors flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                성분 추가
            </button>

            <!-- 빠른 추가 -->
            <div class="mt-4 pt-4 border-t border-gray-200">
                <p class="text-sm text-gray-600 mb-2">자주 사용하는 성분:</p>
                <div class="flex flex-wrap gap-2">
                    <template x-for="preset in presetIngredients" :key="preset">
                        <button type="button" @click="addPresetIngredient(preset)"
                                class="px-3 py-1 text-xs bg-gray-100 hover:bg-blue-100 hover:text-blue-700 rounded-full transition-colors"
                                x-text="preset"></button>
                    </template>
                </div>
            </div>
        </div>

        <script>
            function ingredientsEditor() {
                return {
                    ingredients: {!! json_encode(old('ingredients', [])) !!},
                    presetIngredients: [
                        '히알루론산', '나이아신아마이드', '레티놀', '비타민C', '펩타이드',
                        '세라마이드', '콜라겐', '아데노신', '알부틴', 'AHA', 'BHA',
                        '녹차추출물', '병풀추출물', '스쿠알란', '판테놀'
                    ],
                    addIngredient() {
                        this.ingredients.push('');
                    },
                    removeIngredient(index) {
                        this.ingredients.splice(index, 1);
                    },
                    addPresetIngredient(ingredient) {
                        if (!this.ingredients.includes(ingredient)) {
                            this.ingredients.push(ingredient);
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

        <div class="flex gap-4">
            <button type="submit"
                    class="flex-1 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                제품 등록
            </button>
            <a href="{{ route('admin.products.index') }}"
               class="px-8 py-3 border-2 border-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors text-center">
                취소
            </a>
        </div>
    </form>
</div>
@endsection
