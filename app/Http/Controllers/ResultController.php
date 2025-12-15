<?php

namespace App\Http\Controllers;

use App\Models\AnalysisResult;
use App\Models\Product;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    // 레이더 차트용 정규화 범위
    private array $radarRanges = [
        'moisture' => ['worst' => 25, 'best' => 70, 'inverse' => false],
        'elasticity' => ['worst' => 1.5, 'best' => 3.5, 'inverse' => false],
        'tone' => ['worst' => 320, 'best' => 120, 'inverse' => true],
        'pore' => ['worst' => 2.0, 'best' => 0.5, 'inverse' => true],
        'wrinkle' => ['worst' => 150, 'best' => 30, 'inverse' => true],
    ];

    public function show(Request $request, string $code)
    {
        $product = Product::where('code', $code)->firstOrFail();
        $sessionId = $request->session()->get('skincare_session_id');

        if (!$sessionId) {
            return redirect()->route('survey.index', $code);
        }

        $result = AnalysisResult::where('session_id', $sessionId)
            ->where('product_id', $product->id)
            ->with('profile')
            ->latest('created_at')
            ->first();

        if (!$result) {
            return redirect()->route('survey.index', $code);
        }

        // metrics에 radarBefore/radarAfter가 없으면 추가
        $metrics = $result->metrics;
        if ($metrics) {
            $metrics = $this->ensureRadarScores($metrics);
            $result->metrics = $metrics;
        }

        // Chart.js용 데이터 준비
        $chartData = $this->prepareChartData($result, $product);

        return view('result.show', compact('product', 'result', 'chartData'));
    }

    private function ensureRadarScores(array $metrics): array
    {
        foreach ($metrics as $key => &$metric) {
            if (!isset($metric['radarBefore']) || !isset($metric['radarAfter'])) {
                $range = $this->radarRanges[$key] ?? null;
                if ($range) {
                    $scores = $this->calculateRadarScore(
                        $metric['initial'],
                        $metric['final'],
                        $range
                    );
                    $metric['radarBefore'] = $scores['before'];
                    $metric['radarAfter'] = $scores['after'];
                }
            }
        }
        return $metrics;
    }

    private function calculateRadarScore(float $initial, float $final, array $range): array
    {
        $isInverse = $range['inverse'] ?? false;

        if ($isInverse) {
            $beforeScore = 100 - (($initial - $range['best']) / ($range['worst'] - $range['best']) * 100);
            $afterScore = 100 - (($final - $range['best']) / ($range['worst'] - $range['best']) * 100);
        } else {
            $beforeScore = (($initial - $range['worst']) / ($range['best'] - $range['worst'])) * 100;
            $afterScore = (($final - $range['worst']) / ($range['best'] - $range['worst'])) * 100;
        }

        return [
            'before' => max(0, min(100, round($beforeScore, 1))),
            'after' => max(0, min(100, round($afterScore, 1))),
        ];
    }

    private function prepareChartData(AnalysisResult $result, Product $product): array
    {
        $timeline = $result->timeline;
        $comparison = $result->comparison;
        $baseCurve = $product->base_curve;

        $labels = [
            'moisture' => '수분',
            'elasticity' => '탄력',
            'tone' => '피부톤',
            'pore' => '모공',
            'wrinkle' => '주름',
        ];

        // 라인 차트 데이터
        $weeks = [1, 2, 4, 8, 12];
        $lineChartData = [
            'labels' => array_map(fn($w) => $w . '주', $weeks),
            'datasets' => [],
        ];

        $colors = [
            'moisture' => ['rgb(59, 130, 246)', 'rgba(59, 130, 246, 0.1)'],
            'elasticity' => ['rgb(168, 85, 247)', 'rgba(168, 85, 247, 0.1)'],
            'tone' => ['rgb(251, 146, 60)', 'rgba(251, 146, 60, 0.1)'],
            'pore' => ['rgb(34, 197, 94)', 'rgba(34, 197, 94, 0.1)'],
            'wrinkle' => ['rgb(236, 72, 153)', 'rgba(236, 72, 153, 0.1)'],
        ];

        foreach ($timeline as $category => $values) {
            $data = [];
            foreach ($weeks as $week) {
                $data[] = $values[$week] ?? 0;
            }

            $lineChartData['datasets'][] = [
                'label' => $labels[$category] ?? $category,
                'data' => $data,
                'borderColor' => $colors[$category][0] ?? 'rgb(107, 114, 128)',
                'backgroundColor' => $colors[$category][1] ?? 'rgba(107, 114, 128, 0.1)',
                'tension' => 0.3,
                'fill' => false,
            ];
        }

        // 비교 바 차트 데이터
        $barChartData = [
            'labels' => array_values($labels),
            'datasets' => [
                [
                    'label' => '평균',
                    'data' => array_map(fn($c) => $comparison[$c]['average'] ?? 0, array_keys($labels)),
                    'backgroundColor' => 'rgba(156, 163, 175, 0.8)',
                ],
                [
                    'label' => '나의 예상',
                    'data' => array_map(fn($c) => $comparison[$c]['personal'] ?? 0, array_keys($labels)),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                ],
            ],
        ];

        return [
            'lineChart' => $lineChartData,
            'barChart' => $barChartData,
        ];
    }
}
