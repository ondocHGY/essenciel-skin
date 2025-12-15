<?php

namespace App\Http\Controllers;

use App\Models\AnalysisResult;
use App\Models\Product;
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
        $product = Product::where('code', $code)->firstOrFail();

        // 세션 ID 확인
        if (!$request->session()->has('skincare_session_id')) {
            $request->session()->put('skincare_session_id', Str::uuid()->toString());
        }

        return view('survey.index', compact('product'));
    }

    public function store(Request $request, string $code)
    {
        $product = Product::where('code', $code)->firstOrFail();

        $validated = $request->validate([
            'age_group' => 'required|string',
            'skin_type' => 'required|string',
            'gender' => 'required|string',
            'concerns' => 'required|array|min:1',
            'sleep_hours' => 'required|string',
            'uv_exposure' => 'required|string',
            'stress_level' => 'required|string',
            'water_intake' => 'required|string',
            'smoking_drinking' => 'required|string',
            'care_steps' => 'required|string',
            'consistency' => 'required|string',
            'satisfaction' => 'required|integer|min:1|max:10',
        ]);

        $sessionId = $request->session()->get('skincare_session_id', Str::uuid()->toString());

        // UserProfile 생성
        $profile = UserProfile::create([
            'session_id' => $sessionId,
            'age_group' => $validated['age_group'],
            'skin_type' => $validated['skin_type'],
            'gender' => $validated['gender'],
            'concerns' => $validated['concerns'],
            'lifestyle' => [
                'sleep_hours' => $validated['sleep_hours'],
                'uv_exposure' => $validated['uv_exposure'],
                'stress_level' => $validated['stress_level'],
                'water_intake' => $validated['water_intake'],
                'smoking_drinking' => $validated['smoking_drinking'],
            ],
            'skincare_habit' => [
                'care_steps' => $validated['care_steps'],
                'consistency' => $validated['consistency'],
            ],
            'satisfaction' => $validated['satisfaction'],
        ]);

        // 분석 실행
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
        ]);

        return redirect()->route('result.show', $code);
    }
}
