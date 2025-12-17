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

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $results = $query->latest()->paginate(20)->withQueryString();

        $products = Product::orderBy('name')->get();

        $ageGroups = ['10대', '20대초반', '20대후반', '30대', '40대', '50대이상'];

        // 효능 타입 라벨
        $efficacyLabels = Product::$efficacyTypes;

        return view('admin.surveys.index', compact(
            'results',
            'products',
            'ageGroups',
            'efficacyLabels'
        ));
    }

    /**
     * 설문 결과 상세 보기
     */
    public function show(AnalysisResult $result)
    {
        $result->load(['product', 'profile']);

        // 효능 타입 라벨
        $efficacyLabels = Product::$efficacyTypes;

        // 성별 라벨
        $genderLabels = [
            'female' => '여성',
            'male' => '남성',
            'other' => '기타',
        ];

        // 음주 라벨
        $alcoholLabels = [
            'none' => '전혀 안함',
            'rarely' => '거의 안함 (월 1~2회)',
            'sometimes' => '가끔 (주 1~2회)',
            'often' => '자주 (주 3~4회)',
            'daily' => '매일',
        ];

        // 흡연 라벨
        $smokingLabels = [
            'none' => '비흡연',
            'quit' => '과거 흡연 (현재 금연)',
            'light' => '가끔 (하루 5개비 미만)',
            'moderate' => '보통 (하루 반 갑)',
            'heavy' => '많이 (하루 한 갑 이상)',
        ];

        // 라이프스타일 라벨 (새로운 구조)
        $lifestyleLabels = [
            'sleep_hours' => [
                'label' => '수면시간',
                'values' => [
                    'under5' => '5시간 미만',
                    '5to6' => '5~6시간',
                    '6to7' => '6~7시간',
                    '7to8' => '7~8시간',
                    'over8' => '8시간 이상',
                ],
            ],
            'uv_exposure' => [
                'label' => '자외선 노출',
                'values' => [
                    'indoor' => '실내 위주',
                    'commute' => '출퇴근 정도',
                    'normal' => '보통',
                    'outdoor' => '야외 활동 많음',
                    'heavy' => '야외 근무',
                ],
            ],
            'stress_level' => [
                'label' => '스트레스 수준',
                'values' => [
                    'very_low' => '매우 낮음',
                    'low' => '낮음',
                    'medium' => '보통',
                    'high' => '높음',
                    'very_high' => '매우 높음',
                ],
            ],
            'water_intake' => [
                'label' => '수분 섭취',
                'values' => [
                    'under500ml' => '500ml 미만',
                    '500ml_1L' => '500ml ~ 1L',
                    '1L_1.5L' => '1L ~ 1.5L',
                    '1.5L_2L' => '1.5L ~ 2L',
                    'over2L' => '2L 이상',
                ],
            ],
        ];

        // 스킨케어 단계 라벨
        $careStepsLabels = [
            'none' => '거의 안함 (세안만)',
            'basic' => '기초만 (토너 + 로션/크림)',
            'standard' => '표준 케어 (토너 + 에센스 + 크림)',
            'thorough' => '꼼꼼히 (클렌징~마스크팩)',
            'intensive' => '집중 케어 (전문 제품 다수)',
        ];

        return view('admin.surveys.show', compact(
            'result',
            'efficacyLabels',
            'genderLabels',
            'alcoholLabels',
            'smokingLabels',
            'lifestyleLabels',
            'careStepsLabels'
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
            '효능타입',
            '연령대',
            '성별',
            '음주',
            '흡연',
            '초기값',
            '최종값',
            '개선율(%)',
            '생성일시',
        ];

        $efficacyLabels = Product::$efficacyTypes;

        $callback = function () use ($results, $columns, $efficacyLabels) {
            $file = fopen('php://output', 'w');

            // BOM for UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, $columns);

            foreach ($results as $result) {
                $metrics = $result->metrics ?? [];
                $efficacyType = $metrics['efficacy_type'] ?? 'moisture';

                fputcsv($file, [
                    $result->id,
                    $result->product?->name ?? '-',
                    $efficacyLabels[$efficacyType] ?? $efficacyType,
                    $result->profile?->age_group ?? '-',
                    $result->profile?->gender ?? '-',
                    $result->profile?->alcohol ?? '-',
                    $result->profile?->smoking ?? '-',
                    $metrics['initial'] ?? '-',
                    $metrics['final'] ?? '-',
                    $metrics['change_percent'] ?? '-',
                    $result->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
