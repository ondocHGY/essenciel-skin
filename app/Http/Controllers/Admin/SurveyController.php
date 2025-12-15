<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalysisResult;
use App\Models\Product;
use App\Models\UserProfile;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    /**
     * 설문 결과 목록
     */
    public function index(Request $request)
    {
        $query = AnalysisResult::with(['product', 'profile']);

        // 필터링
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('age_group')) {
            $query->whereHas('profile', function ($q) use ($request) {
                $q->where('age_group', $request->age_group);
            });
        }

        if ($request->filled('skin_type')) {
            $query->whereHas('profile', function ($q) use ($request) {
                $q->where('skin_type', $request->skin_type);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $results = $query->latest()->paginate(20)->withQueryString();

        $products = Product::orderBy('name')->get();

        $ageGroups = ['10대', '20대초반', '20대후반', '30대', '40대', '50대이상'];
        $skinTypes = ['중성', '지성', '건성', '복합성', '민감성'];

        return view('admin.surveys.index', compact(
            'results',
            'products',
            'ageGroups',
            'skinTypes'
        ));
    }

    /**
     * 설문 결과 상세 보기
     */
    public function show(AnalysisResult $result)
    {
        $result->load(['product', 'profile']);

        // 지표 라벨
        $labels = [
            'moisture' => '수분',
            'elasticity' => '탄력',
            'tone' => '피부톤',
            'pore' => '모공',
            'wrinkle' => '주름',
        ];

        // 고민 라벨
        $concernLabels = [
            'wrinkle' => '주름/잔주름',
            'elasticity' => '탄력 저하',
            'pigmentation' => '기미/잡티',
            'pore' => '모공',
            'acne' => '트러블/여드름',
            'dryness' => '건조함',
            'redness' => '홍조',
            'dullness' => '칙칙함',
        ];

        // 라이프스타일 라벨
        $lifestyleLabels = [
            'sleep_hours' => [
                'label' => '수면시간',
                'under6' => '6시간 미만',
                '6to8' => '6-8시간',
                'over8' => '8시간 이상',
            ],
            'uv_exposure' => [
                'label' => '자외선 노출',
                'indoor' => '실내 위주',
                'normal' => '보통',
                'outdoor' => '야외 활동 많음',
            ],
            'stress_level' => [
                'label' => '스트레스 수준',
                'low' => '낮음',
                'medium' => '보통',
                'high' => '높음',
            ],
            'water_intake' => [
                'label' => '수분 섭취',
                'under1L' => '1L 미만',
                '1to2L' => '1-2L',
                'over2L' => '2L 이상',
            ],
            'smoking_drinking' => [
                'label' => '음주/흡연',
                'none' => '안함',
                'sometimes' => '가끔',
                'often' => '자주',
            ],
        ];

        return view('admin.surveys.show', compact(
            'result',
            'labels',
            'concernLabels',
            'lifestyleLabels'
        ));
    }

    /**
     * 설문 결과 삭제
     */
    public function destroy(AnalysisResult $result)
    {
        $profileId = $result->profile_id;

        $result->delete();

        // 해당 프로필에 다른 분석 결과가 없으면 프로필도 삭제
        if (!AnalysisResult::where('profile_id', $profileId)->exists()) {
            UserProfile::find($profileId)?->delete();
        }

        return redirect()->route('admin.surveys.index')
            ->with('success', '설문 결과가 삭제되었습니다.');
    }

    /**
     * 대시보드용 통계 데이터 API
     */
    public function statistics(Request $request)
    {
        $period = $request->get('period', '7days');

        $startDate = match ($period) {
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            default => now()->subDays(7),
        };

        $data = AnalysisResult::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json($data);
    }

    /**
     * 결과 데이터 CSV 내보내기
     */
    public function export(Request $request)
    {
        $query = AnalysisResult::with(['product', 'profile']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $results = $query->latest()->get();

        $filename = 'survey_results_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $columns = [
            'ID',
            '제품명',
            '연령대',
            '피부타입',
            '성별',
            '수분(초기)',
            '수분(최종)',
            '탄력(초기)',
            '탄력(최종)',
            '피부톤(초기)',
            '피부톤(최종)',
            '모공(초기)',
            '모공(최종)',
            '주름(초기)',
            '주름(최종)',
            '생성일시',
        ];

        $callback = function () use ($results, $columns) {
            $file = fopen('php://output', 'w');

            // BOM for UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, $columns);

            foreach ($results as $result) {
                $metrics = $result->metrics ?? [];

                fputcsv($file, [
                    $result->id,
                    $result->product?->name ?? '-',
                    $result->profile?->age_group ?? '-',
                    $result->profile?->skin_type ?? '-',
                    $result->profile?->gender ?? '-',
                    $metrics['moisture']['initial'] ?? '-',
                    $metrics['moisture']['final'] ?? '-',
                    $metrics['elasticity']['initial'] ?? '-',
                    $metrics['elasticity']['final'] ?? '-',
                    $metrics['tone']['initial'] ?? '-',
                    $metrics['tone']['final'] ?? '-',
                    $metrics['pore']['initial'] ?? '-',
                    $metrics['pore']['final'] ?? '-',
                    $metrics['wrinkle']['initial'] ?? '-',
                    $metrics['wrinkle']['final'] ?? '-',
                    $result->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
