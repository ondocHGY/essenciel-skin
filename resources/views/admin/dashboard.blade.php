@extends('layouts.admin')

@section('title', '대시보드')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- 페이지 헤더 -->
    <div class="mb-8">
        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">대시보드</h1>
        <p class="text-gray-600 mt-1">스킨케어 분석 서비스 현황</p>
    </div>

    <!-- 빠른 통계 -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8">
        <div class="bg-white rounded-xl p-5 lg:p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs lg:text-sm text-gray-500 uppercase tracking-wide">등록 제품</p>
                    <p class="text-2xl lg:text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['totalProducts']) }}</p>
                </div>
                <div class="w-12 h-12 lg:w-14 lg:h-14 rounded-full bg-blue-100 flex items-center justify-center">
                    <svg class="w-6 h-6 lg:w-7 lg:h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-5 lg:p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs lg:text-sm text-gray-500 uppercase tracking-wide">전체 설문</p>
                    <p class="text-2xl lg:text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['totalSurveys']) }}</p>
                </div>
                <div class="w-12 h-12 lg:w-14 lg:h-14 rounded-full bg-green-100 flex items-center justify-center">
                    <svg class="w-6 h-6 lg:w-7 lg:h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-5 lg:p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs lg:text-sm text-gray-500 uppercase tracking-wide">분석 결과</p>
                    <p class="text-2xl lg:text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['totalAnalyses']) }}</p>
                </div>
                <div class="w-12 h-12 lg:w-14 lg:h-14 rounded-full bg-purple-100 flex items-center justify-center">
                    <svg class="w-6 h-6 lg:w-7 lg:h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-5 lg:p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs lg:text-sm text-gray-500 uppercase tracking-wide">오늘 설문</p>
                    <p class="text-2xl lg:text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['todaySurveys']) }}</p>
                </div>
                <div class="w-12 h-12 lg:w-14 lg:h-14 rounded-full bg-orange-100 flex items-center justify-center">
                    <svg class="w-6 h-6 lg:w-7 lg:h-7 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6 lg:gap-8 mb-8">
        <!-- 주간 설문 추이 차트 -->
        <div class="lg:col-span-2 bg-white rounded-xl p-5 lg:p-6 shadow-sm">
            <h3 class="text-base lg:text-lg font-semibold text-gray-900 mb-4">최근 7일 설문 현황</h3>
            <div class="h-64 lg:h-80">
                <canvas id="dailyChart"></canvas>
            </div>
        </div>

        <!-- 연령대별 분포 -->
        <div class="bg-white rounded-xl p-5 lg:p-6 shadow-sm">
            <h3 class="text-base lg:text-lg font-semibold text-gray-900 mb-4">연령대별 분포</h3>
            <div class="h-64 lg:h-80">
                <canvas id="ageChart"></canvas>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6 lg:gap-8 mb-8">
        <!-- 피부타입별 분포 -->
        <div class="bg-white rounded-xl p-5 lg:p-6 shadow-sm">
            <h3 class="text-base lg:text-lg font-semibold text-gray-900 mb-4">피부타입별 분포</h3>
            <div class="space-y-4">
                @php
                    $skinTypeColors = [
                        '중성' => 'bg-green-500',
                        '지성' => 'bg-blue-500',
                        '건성' => 'bg-orange-500',
                        '복합성' => 'bg-purple-500',
                        '민감성' => 'bg-red-500',
                    ];
                    $totalSkinType = array_sum($skinTypeDistribution);
                @endphp
                @foreach($skinTypeDistribution as $type => $count)
                <div>
                    <div class="flex justify-between text-sm mb-2">
                        <span class="font-medium text-gray-700">{{ $type }}</span>
                        <span class="text-gray-500">{{ $count }}명 ({{ $totalSkinType > 0 ? round(($count / $totalSkinType) * 100, 1) : 0 }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="{{ $skinTypeColors[$type] ?? 'bg-gray-500' }} h-2.5 rounded-full transition-all duration-500"
                             style="width: {{ $totalSkinType > 0 ? ($count / $totalSkinType) * 100 : 0 }}%"></div>
                    </div>
                </div>
                @endforeach
                @if(empty($skinTypeDistribution))
                <p class="text-center text-gray-500 py-8">데이터가 없습니다</p>
                @endif
            </div>
        </div>

        <!-- 제품별 분석 현황 -->
        <div class="bg-white rounded-xl p-5 lg:p-6 shadow-sm">
            <h3 class="text-base lg:text-lg font-semibold text-gray-900 mb-4">인기 제품 TOP 5</h3>
            <div class="space-y-4">
                @forelse($productStats as $index => $product)
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                        {{ $index + 1 }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 truncate">{{ $product->name }}</p>
                        <p class="text-sm text-gray-500">{{ $product->brand }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-gray-900">{{ number_format($product->analysis_results_count) }}</p>
                        <p class="text-xs text-gray-500">분석</p>
                    </div>
                </div>
                @empty
                <p class="text-center text-gray-500 py-8">데이터가 없습니다</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- 최근 분석 결과 -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 lg:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-base lg:text-lg font-semibold text-gray-900">최근 분석 결과</h3>
            <a href="{{ route('admin.surveys.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">전체 보기 →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 lg:px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">제품</th>
                        <th class="px-5 lg:px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">연령대</th>
                        <th class="px-5 lg:px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">피부타입</th>
                        <th class="px-5 lg:px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">성별</th>
                        <th class="px-5 lg:px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">일시</th>
                        <th class="px-5 lg:px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($recentResults as $result)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 lg:px-6 py-4">
                            <p class="font-medium text-gray-900">{{ $result->product?->name ?? '-' }}</p>
                            <p class="text-sm text-gray-500">{{ $result->product?->brand ?? '' }}</p>
                        </td>
                        <td class="px-5 lg:px-6 py-4 text-sm text-gray-600">{{ $result->profile?->age_group ?? '-' }}</td>
                        <td class="px-5 lg:px-6 py-4 text-sm text-gray-600">{{ $result->profile?->skin_type ?? '-' }}</td>
                        <td class="px-5 lg:px-6 py-4 text-sm text-gray-600">{{ $result->profile?->gender ?? '-' }}</td>
                        <td class="px-5 lg:px-6 py-4 text-sm text-gray-500">{{ $result->created_at->format('m/d H:i') }}</td>
                        <td class="px-5 lg:px-6 py-4">
                            <a href="{{ route('admin.surveys.show', $result) }}"
                               class="text-blue-600 hover:text-blue-700 font-medium text-sm">상세</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 lg:px-6 py-12 text-center text-gray-500">
                            분석 결과가 없습니다.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 일별 설문 차트
    new Chart(document.getElementById('dailyChart'), {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [{
                label: '설문 수',
                data: @json($chartData),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.3,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });

    // 연령대별 분포 차트
    const ageData = @json($ageDistribution);
    const ageLabels = Object.keys(ageData);
    const ageValues = Object.values(ageData);

    new Chart(document.getElementById('ageChart'), {
        type: 'doughnut',
        data: {
            labels: ageLabels,
            datasets: [{
                data: ageValues,
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(168, 85, 247, 0.8)',
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(251, 146, 60, 0.8)',
                    'rgba(236, 72, 153, 0.8)',
                    'rgba(107, 114, 128, 0.8)',
                ],
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 12,
                        font: { size: 12 }
                    }
                }
            }
        }
    });
});
</script>
@endsection
