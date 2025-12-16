@extends('layouts.admin')

@section('title', '설문 옵션 관리')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- 헤더 -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">설문 옵션 관리</h1>
            <p class="text-gray-600 mt-1">설문에 표시되는 옵션들을 관리합니다</p>
        </div>
        <a href="{{ route('admin.survey-options.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            카테고리 추가
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
        {{ session('error') }}
    </div>
    @endif

    <!-- 결과 계산 공식 -->
    <div class="bg-white rounded-xl shadow-sm mb-6" x-data="{ open: false }">
        <button @click="open = !open" class="w-full px-6 py-4 flex items-center justify-between text-left hover:bg-gray-50 transition-colors rounded-xl">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">결과 계산 공식</h2>
                    <p class="text-sm text-gray-500">설문 옵션이 분석 결과에 미치는 영향을 확인합니다</p>
                </div>
            </div>
            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>

        <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="border-t border-gray-100">
            <div class="p-6 space-y-6">
                <!-- 메인 공식 -->
                <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl p-5 border border-indigo-100">
                    <h3 class="text-sm font-semibold text-indigo-900 mb-3">최종 효과 계산식</h3>
                    <div class="bg-white rounded-lg p-4 font-mono text-sm overflow-x-auto">
                        <span class="text-purple-600 font-semibold">최종효과</span> =
                        <span class="text-blue-600">제품기본효과</span> ×
                        <span class="text-green-600">연령대</span> ×
                        <span class="text-orange-600">피부타입</span> ×
                        <span class="text-pink-600">규칙성</span> ×
                        <span class="text-teal-600">생활환경</span> ×
                        <span class="text-amber-600">고민매칭</span>
                    </div>
                    <p class="text-xs text-indigo-600 mt-3">
                        * 생활환경 = 수면 × 자외선 × 스트레스 × 수분섭취 × 음주흡연 (0.75~1.15 범위로 제한)
                    </p>
                </div>

                <!-- 카테고리별 영향도 -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @php
                        $categoryConfig = [
                            'age_groups' => ['name' => '연령대', 'color' => 'green', 'icon' => '👤'],
                            'skin_types' => ['name' => '피부타입', 'color' => 'orange', 'icon' => '✨'],
                            'consistency_options' => ['name' => '스킨케어 규칙성', 'color' => 'pink', 'icon' => '📅'],
                            'sleep_hours' => ['name' => '수면시간', 'color' => 'blue', 'icon' => '😴'],
                            'uv_exposure' => ['name' => '자외선 노출', 'color' => 'yellow', 'icon' => '☀️'],
                            'stress_levels' => ['name' => '스트레스', 'color' => 'red', 'icon' => '😰'],
                            'water_intake' => ['name' => '수분섭취', 'color' => 'cyan', 'icon' => '💧'],
                            'smoking_drinking' => ['name' => '음주/흡연', 'color' => 'gray', 'icon' => '🚬'],
                        ];
                    @endphp

                    @foreach($categoryConfig as $key => $config)
                        @if(isset($modifiers[$key]))
                            @php $cat = $modifiers[$key]; @endphp
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="text-lg">{{ $config['icon'] }}</span>
                                    <h4 class="font-medium text-gray-900">{{ $config['name'] }}</h4>
                                </div>
                                <div class="space-y-1.5">
                                    @foreach($cat->options as $option)
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-600">{{ $option->label }}</span>
                                            <span class="font-mono px-2 py-0.5 rounded text-xs
                                                @if($option->modifier > 1) bg-green-100 text-green-700
                                                @elseif($option->modifier < 1) bg-red-100 text-red-700
                                                @else bg-gray-100 text-gray-600
                                                @endif">
                                                ×{{ number_format($option->modifier, 2) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                <!-- 피부 측정 기준값 -->
                <div class="bg-teal-50 rounded-xl p-5 border border-teal-200">
                    <h3 class="text-sm font-semibold text-teal-900 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        피부 측정 기준값 (결과 페이지에 표시되는 수치)
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-teal-700">
                                    <th class="pb-2 font-medium">지표</th>
                                    <th class="pb-2 font-medium">단위</th>
                                    <th class="pb-2 font-medium">초기 범위</th>
                                    <th class="pb-2 font-medium">최대 개선량</th>
                                    <th class="pb-2 font-medium">방향</th>
                                </tr>
                            </thead>
                            <tbody class="text-teal-800">
                                <tr class="border-t border-teal-200">
                                    <td class="py-2">💧 피부 수분량</td>
                                    <td class="py-2 font-mono">%</td>
                                    <td class="py-2 font-mono">35 ~ 55</td>
                                    <td class="py-2 font-mono text-green-600">+25</td>
                                    <td class="py-2"><span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded">↑ 높을수록 좋음</span></td>
                                </tr>
                                <tr class="border-t border-teal-200">
                                    <td class="py-2">✨ 콜라겐 밀도</td>
                                    <td class="py-2 font-mono">mg/cm²</td>
                                    <td class="py-2 font-mono">1.8 ~ 2.8</td>
                                    <td class="py-2 font-mono text-green-600">+0.9</td>
                                    <td class="py-2"><span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded">↑ 높을수록 좋음</span></td>
                                </tr>
                                <tr class="border-t border-teal-200">
                                    <td class="py-2">🎨 멜라닌 지수</td>
                                    <td class="py-2 font-mono">M.I</td>
                                    <td class="py-2 font-mono">180 ~ 280</td>
                                    <td class="py-2 font-mono text-blue-600">-80</td>
                                    <td class="py-2"><span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded">↓ 낮을수록 좋음</span></td>
                                </tr>
                                <tr class="border-t border-teal-200">
                                    <td class="py-2">⭕ 모공 면적</td>
                                    <td class="py-2 font-mono">mm²</td>
                                    <td class="py-2 font-mono">0.8 ~ 1.6</td>
                                    <td class="py-2 font-mono text-blue-600">-0.5</td>
                                    <td class="py-2"><span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded">↓ 낮을수록 좋음</span></td>
                                </tr>
                                <tr class="border-t border-teal-200">
                                    <td class="py-2">〰️ 주름 깊이</td>
                                    <td class="py-2 font-mono">μm</td>
                                    <td class="py-2 font-mono">45 ~ 120</td>
                                    <td class="py-2 font-mono text-blue-600">-35</td>
                                    <td class="py-2"><span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded">↓ 낮을수록 좋음</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-xs text-teal-600 mt-3">
                        * 초기값은 사용자의 나이, 피부타입에 따라 범위 내에서 결정됩니다. (젊을수록/건강할수록 좋은 초기값)
                    </p>
                </div>

                <!-- 제품 기본 효과 곡선 설명 -->
                <div class="bg-blue-50 rounded-xl p-5 border border-blue-200">
                    <h3 class="text-sm font-semibold text-blue-900 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                        </svg>
                        제품별 기본 효과 곡선 (base_curve)
                    </h3>
                    <div class="text-sm text-blue-800 space-y-3">
                        <p>각 제품은 <strong>주차별 효과율(%)</strong>을 정의합니다. 이 값에 설문 modifier가 곱해져 최종 효과가 계산됩니다.</p>
                        <div class="bg-white rounded-lg p-3 overflow-x-auto">
                            <table class="w-full text-xs font-mono">
                                <thead>
                                    <tr class="text-blue-600">
                                        <th class="text-left pb-2">카테고리</th>
                                        <th class="text-center pb-2">1주</th>
                                        <th class="text-center pb-2">2주</th>
                                        <th class="text-center pb-2">4주</th>
                                        <th class="text-center pb-2">8주</th>
                                        <th class="text-center pb-2">12주</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-700">
                                    <tr><td class="py-1">수분 (moisture)</td><td class="text-center">15%</td><td class="text-center">35%</td><td class="text-center">55%</td><td class="text-center">75%</td><td class="text-center">90%</td></tr>
                                    <tr><td class="py-1">탄력 (elasticity)</td><td class="text-center">8%</td><td class="text-center">20%</td><td class="text-center">38%</td><td class="text-center">58%</td><td class="text-center">75%</td></tr>
                                    <tr><td class="py-1">피부톤 (tone)</td><td class="text-center">10%</td><td class="text-center">25%</td><td class="text-center">42%</td><td class="text-center">62%</td><td class="text-center">80%</td></tr>
                                    <tr><td class="py-1">모공 (pore)</td><td class="text-center">5%</td><td class="text-center">15%</td><td class="text-center">28%</td><td class="text-center">45%</td><td class="text-center">60%</td></tr>
                                    <tr><td class="py-1">주름 (wrinkle)</td><td class="text-center">5%</td><td class="text-center">12%</td><td class="text-center">25%</td><td class="text-center">40%</td><td class="text-center">55%</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-blue-600">
                            * 위 값은 예시입니다. 실제 값은 <a href="{{ route('admin.products.index') }}" class="underline hover:text-blue-800">제품 관리</a>에서 제품별로 설정됩니다.
                        </p>
                    </div>
                </div>

                <!-- 예시 계산 -->
                <div class="bg-amber-50 rounded-xl p-5 border border-amber-200">
                    <h3 class="text-sm font-semibold text-amber-900 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        전체 계산 예시
                    </h3>
                    <div class="text-sm text-amber-800 space-y-3">
                        <p><strong>조건:</strong> 30대, 중성피부, 매일 스킨케어, 좋은 생활습관</p>
                        <div class="bg-white rounded-lg p-3 font-mono text-xs space-y-2 overflow-x-auto">
                            <div class="text-gray-500">// 1. modifier 계산</div>
                            <div>총 modifier = <span class="text-green-600">1.00</span>(연령) × <span class="text-orange-600">1.10</span>(피부타입) × <span class="text-pink-600">1.30</span>(규칙성) × <span class="text-teal-600">1.10</span>(생활환경) = <span class="text-purple-600 font-bold">1.573</span></div>
                            <div class="text-gray-500 mt-2">// 2. 12주 후 수분 효과 계산</div>
                            <div>제품 기본효과 = <span class="text-blue-600">90%</span></div>
                            <div>최종효과 = 90% × 1.573 = <span class="text-purple-600 font-bold">141.6%</span> → <span class="text-green-600">100%</span> (최대 100% 적용)</div>
                            <div class="text-gray-500 mt-2">// 3. 피부 수분량 변화</div>
                            <div>초기값 (30대, 중성) = <span class="text-blue-600">45%</span></div>
                            <div>최대개선량 × 효과율 = 25 × 1.00 = <span class="text-green-600">+25%</span></div>
                            <div>최종 수분량 = 45% + 25% = <span class="text-purple-600 font-bold">70%</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 카테고리 목록 -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">카테고리</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">옵션 수</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">복수선택</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">아이콘</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">상태</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">관리</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200" id="sortable-categories">
                @foreach($categories as $category)
                <tr class="hover:bg-gray-50 transition-colors" data-id="{{ $category->id }}">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <span class="cursor-move text-gray-400 hover:text-gray-600 drag-handle">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M7 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 2zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 14zm6-8a2 2 0 1 0-.001-4.001A2 2 0 0 0 13 6zm0 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 14z"></path>
                                </svg>
                            </span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="font-medium text-gray-900">{{ $category->name }}</p>
                                    @if($category->is_system)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">시스템</span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-500 font-mono">{{ $category->key }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $category->options_count }}개
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        @if($category->is_multiple)
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700">가능</span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($category->has_icon)
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-700">있음</span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($category->is_active)
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700">활성</span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-700">비활성</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.survey-options.edit', $category) }}"
                               class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-700 font-medium text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                옵션 관리
                            </a>
                            @if(!$category->is_system)
                            <form action="{{ route('admin.survey-options.destroy', $category) }}" method="POST" class="inline"
                                  onsubmit="return confirm('정말 삭제하시겠습니까?\n\n이 카테고리의 모든 옵션도 함께 삭제됩니다.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 p-1" title="삭제">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="mt-4 text-sm text-gray-500">
        드래그하여 카테고리 순서를 변경할 수 있습니다. 시스템 카테고리는 삭제할 수 없습니다.
    </p>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new Sortable(document.getElementById('sortable-categories'), {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'bg-blue-50',
        onEnd: function(evt) {
            const orders = [...document.querySelectorAll('#sortable-categories tr')]
                .map(tr => parseInt(tr.dataset.id));

            fetch('{{ route('admin.survey-options.reorder') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ orders })
            });
        }
    });
});
</script>
@endpush
