<?php

namespace App\Http\Controllers;

use App\Models\AnalysisResult;
use App\Models\Product;
use App\Models\SurveyQuestion;
use App\Models\UserProfile;
use App\Services\AnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SurveyController extends Controller
{
    public function __construct(
        private AnalysisService $analysisService
    ) {}

    public function index(Request $request, string $code)
    {
        $product = Product::where('code', $code)
            ->with(['activeReviewSources'])
            ->firstOrFail();

        // 세션 ID 확인
        if (!$request->session()->has('skincare_session_id')) {
            $request->session()->put('skincare_session_id', Str::uuid()->toString());
        }

        // 설문 질문 로드 (DB에서 또는 기본값)
        $questions = $this->loadSurveyQuestions();

        // 리뷰 데이터 집계 (실제 수집된 리뷰 건수 사용)
        $actualReviewCount = $product->reviews()->count();
        $reviewData = [
            'total_count' => $actualReviewCount,
            'sources' => $product->activeReviewSources->map(fn($s) => [
                'platform' => $s->platform,
                'platform_name' => $s->platform_name,
                'review_count' => $s->review_count,
                'average_rating' => $s->average_rating,
            ])->toArray(),
        ];

        return view('survey.index', compact('product', 'questions', 'reviewData'));
    }

    /**
     * 설문 질문 로드 (DB 우선, 폴백으로 기본값)
     */
    private function loadSurveyQuestions(): array
    {
        try {
            $dbQuestions = SurveyQuestion::getActiveQuestions();

            if ($dbQuestions->isNotEmpty()) {
                return $dbQuestions->map(fn($q) => $q->toFrontendFormat())->toArray();
            }
        } catch (\Exception $e) {
            // DB 오류 시 기본값 사용
        }

        // 폴백: 하드코딩된 기본 질문
        return $this->getDefaultQuestions();
    }

    /**
     * 기본 설문 질문 (DB 없을 때 폴백)
     */
    private function getDefaultQuestions(): array
    {
        return [
            [
                'name' => 'age_group',
                'title' => '연령대를 선택해주세요',
                'subtitle' => '피부 턴오버 속도를 파악하는 데 사용돼요',
                'options' => [
                    ['value' => '10대', 'label' => '10대', 'desc' => null],
                    ['value' => '20대', 'label' => '20대', 'desc' => null],
                    ['value' => '30대', 'label' => '30대', 'desc' => null],
                    ['value' => '40대', 'label' => '40대', 'desc' => null],
                    ['value' => '50대이상', 'label' => '50대 이상', 'desc' => null],
                ],
            ],
            [
                'name' => 'gender',
                'title' => '성별을 선택해주세요',
                'subtitle' => '피지 분비 및 각질 두께를 분석해요',
                'options' => [
                    ['value' => 'male', 'label' => '남성', 'desc' => null],
                    ['value' => 'female', 'label' => '여성', 'desc' => null],
                    ['value' => 'other', 'label' => '기타', 'desc' => null],
                ],
            ],
            [
                'name' => 'sleep_hours',
                'title' => '평균 수면 시간은 어떻게 되시나요?',
                'subtitle' => '피부 재생 능력을 파악해요',
                'options' => [
                    ['value' => 'under6', 'label' => '6시간 미만', 'desc' => null],
                    ['value' => '6to8', 'label' => '6~8시간', 'desc' => null],
                    ['value' => 'over8', 'label' => '8시간 이상', 'desc' => null],
                ],
            ],
            [
                'name' => 'uv_exposure',
                'title' => '자외선 노출 정도는 어떠신가요?',
                'subtitle' => '멜라닌 활성도를 분석해요',
                'options' => [
                    ['value' => 'indoor', 'label' => '실내 위주', 'desc' => null],
                    ['value' => 'normal', 'label' => '보통', 'desc' => null],
                    ['value' => 'outdoor', 'label' => '실외 많음', 'desc' => null],
                ],
            ],
            [
                'name' => 'water_intake',
                'title' => '하루 수분 섭취량은 어떻게 되시나요?',
                'subtitle' => '수분 유지 메커니즘을 분석해요',
                'options' => [
                    ['value' => 'under1L', 'label' => '1L 미만', 'desc' => null],
                    ['value' => '1to2L', 'label' => '1~2L', 'desc' => null],
                    ['value' => 'over2L', 'label' => '2L 이상', 'desc' => null],
                ],
            ],
        ];
    }

    public function store(Request $request, string $code)
    {
        $product = Product::where('code', $code)->firstOrFail();

        // 새로운 9개 질문 구조로 변경
        $validated = $request->validate([
            'age_group' => 'required|string',
            'gender' => 'required|string',
            'sleep_hours' => 'required|string',
            'uv_exposure' => 'required|string',
            'stress_level' => 'nullable|string', // 현재 설문에서 제외
            'water_intake' => 'required|string',
            'alcohol' => 'nullable|string', // 현재 설문에서 제외
            'smoking' => 'nullable|string', // 현재 설문에서 제외
            'care_steps' => 'nullable|string', // 현재 설문에서 제외
        ]);

        $sessionId = $request->session()->get('skincare_session_id', Str::uuid()->toString());

        // UserProfile 생성 (새로운 구조)
        $profile = UserProfile::create([
            'session_id' => $sessionId,
            'ip_address' => $request->ip(),
            'age_group' => $validated['age_group'],
            'skin_type' => 'normal', // 기본값 설정 (더 이상 설문에서 받지 않음)
            'gender' => $validated['gender'],
            'concerns' => [], // 더 이상 설문에서 받지 않음
            'lifestyle' => [
                'sleep_hours' => $validated['sleep_hours'],
                'uv_exposure' => $validated['uv_exposure'],
                'stress_level' => $validated['stress_level'] ?? 'medium', // 기본값
                'water_intake' => $validated['water_intake'],
            ],
            'skincare_habit' => [
                'care_steps' => $validated['care_steps'] ?? 'basic', // 기본값
            ],
            'satisfaction' => 5, // 기본값
            'alcohol' => $validated['alcohol'] ?? 'none', // 기본값
            'smoking' => $validated['smoking'] ?? 'none', // 기본값
        ]);

        // 분석 실행 (제품의 단일 효능에 집중)
        $analysisData = $this->analysisService->calculate($product, $profile);

        // 분석 결과 저장
        AnalysisResult::create([
            'session_id' => $sessionId,
            'product_id' => $product->id,
            'profile_id' => $profile->id,
            'timeline' => $analysisData['timeline'],
            'milestones' => $analysisData['milestones'],
            'comparison' => $analysisData['comparison'],
            'metrics' => $analysisData['metrics'],
            'lifestyle_factors' => $analysisData['lifestyle_factors'] ?? [],
            'usage_guide' => $analysisData['usage_guide'] ?? [],
            'skin_profile' => $analysisData['skin_profile'] ?? [],
        ]);

        // AJAX 요청인 경우 JSON으로 redirect URL 반환
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => route('result.show', $code),
            ]);
        }

        return redirect()->route('result.show', $code);
    }
}
