<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalysisResult;
use App\Models\Product;
use App\Models\UserProfile;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 기본 통계
        $stats = [
            'totalProducts' => Product::count(),
            'totalSurveys' => UserProfile::count(),
            'totalAnalyses' => AnalysisResult::count(),
            'todaySurveys' => UserProfile::whereDate('created_at', today())->count(),
        ];

        // 최근 7일 일별 설문 수
        $dailySurveys = UserProfile::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        // 7일 데이터 정리 (빈 날짜 채우기)
        $chartLabels = [];
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = now()->subDays($i)->format('m/d');
            $chartData[] = $dailySurveys[$date] ?? 0;
        }

        // 연령대별 분포
        $ageDistribution = UserProfile::select('age_group', DB::raw('COUNT(*) as count'))
            ->groupBy('age_group')
            ->pluck('count', 'age_group')
            ->toArray();

        // 피부타입별 분포
        $skinTypeDistribution = UserProfile::select('skin_type', DB::raw('COUNT(*) as count'))
            ->groupBy('skin_type')
            ->pluck('count', 'skin_type')
            ->toArray();

        // 제품별 분석 현황
        $productStats = Product::withCount('analysisResults')
            ->orderByDesc('analysis_results_count')
            ->limit(5)
            ->get();

        // 최근 분석 결과
        $recentResults = AnalysisResult::with(['product', 'profile'])
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'chartLabels',
            'chartData',
            'ageDistribution',
            'skinTypeDistribution',
            'productStats',
            'recentResults'
        ));
    }
}
