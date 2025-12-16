<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurveyOption;
use App\Models\SurveyOptionCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SurveyOptionController extends Controller
{
    /**
     * 카테고리 목록 표시
     */
    public function index()
    {
        $categories = SurveyOptionCategory::withCount('options')
            ->orderBy('sort_order')
            ->get();

        // 공식 표시를 위한 modifier 데이터
        $modifiers = SurveyOptionCategory::with(['options' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->whereIn('key', [
                'age_groups', 'skin_types', 'consistency_options',
                'sleep_hours', 'uv_exposure', 'stress_levels', 'water_intake', 'smoking_drinking'
            ])
            ->get()
            ->keyBy('key');

        return view('admin.survey-options.index', compact('categories', 'modifiers'));
    }

    /**
     * 특정 카테고리의 옵션 목록/편집 페이지
     */
    public function edit(SurveyOptionCategory $category)
    {
        $category->load(['options' => fn($q) => $q->orderBy('sort_order')]);

        return view('admin.survey-options.edit', compact('category'));
    }

    /**
     * 카테고리 정보 업데이트
     */
    public function updateCategory(Request $request, SurveyOptionCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $category->update($validated);

        return back()->with('success', '카테고리가 수정되었습니다.');
    }

    /**
     * 카테고리 생성 폼
     */
    public function create()
    {
        return view('admin.survey-options.create');
    }

    /**
     * 카테고리 저장
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255|unique:survey_option_categories,key|regex:/^[a-z_]+$/',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'has_icon' => 'boolean',
            'is_multiple' => 'boolean',
        ]);

        $validated['has_icon'] = $request->has('has_icon');
        $validated['is_multiple'] = $request->has('is_multiple');
        $validated['is_system'] = false;
        $validated['sort_order'] = SurveyOptionCategory::max('sort_order') + 1;

        SurveyOptionCategory::create($validated);

        return redirect()->route('admin.survey-options.index')
            ->with('success', '카테고리가 생성되었습니다.');
    }

    /**
     * 카테고리 삭제
     */
    public function destroyCategory(SurveyOptionCategory $category)
    {
        // 시스템 카테고리는 삭제 불가
        if ($category->is_system) {
            return back()->with('error', '시스템 카테고리는 삭제할 수 없습니다.');
        }

        $category->delete();

        return redirect()->route('admin.survey-options.index')
            ->with('success', '카테고리가 삭제되었습니다.');
    }

    /**
     * 옵션 추가
     */
    public function storeOption(Request $request, SurveyOptionCategory $category)
    {
        $validated = $request->validate([
            'value' => 'required|string|max:255',
            'label' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'modifier' => 'nullable|numeric|min:0.1|max:2.0',
        ]);

        // 중복 체크
        if ($category->options()->where('value', $validated['value'])->exists()) {
            return back()->withErrors(['value' => '이미 존재하는 값입니다.'])->withInput();
        }

        $maxOrder = $category->options()->max('sort_order') ?? -1;

        $category->options()->create([
            'value' => $validated['value'],
            'label' => $validated['label'],
            'icon' => $validated['icon'] ?? null,
            'modifier' => $validated['modifier'] ?? 1.0,
            'sort_order' => $maxOrder + 1,
        ]);

        // modifier 캐시 무효화
        Cache::forget('survey_modifiers');

        return back()->with('success', '옵션이 추가되었습니다.');
    }

    /**
     * 옵션 수정
     */
    public function updateOption(Request $request, SurveyOption $option)
    {
        $validated = $request->validate([
            'value' => 'required|string|max:255',
            'label' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'modifier' => 'nullable|numeric|min:0.1|max:2.0',
            'is_active' => 'boolean',
        ]);

        // 중복 체크 (자신 제외)
        $exists = SurveyOption::where('category_id', $option->category_id)
            ->where('value', $validated['value'])
            ->where('id', '!=', $option->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['value' => '이미 존재하는 값입니다.']);
        }

        $validated['is_active'] = $request->has('is_active');
        $validated['modifier'] = $validated['modifier'] ?? 1.0;

        $option->update($validated);

        // modifier 캐시 무효화
        Cache::forget('survey_modifiers');

        return back()->with('success', '옵션이 수정되었습니다.');
    }

    /**
     * 옵션 삭제
     */
    public function destroyOption(SurveyOption $option)
    {
        $categoryId = $option->category_id;
        $option->delete();

        // modifier 캐시 무효화
        Cache::forget('survey_modifiers');

        return redirect()->route('admin.survey-options.edit', $categoryId)
            ->with('success', '옵션이 삭제되었습니다.');
    }

    /**
     * 옵션 순서 변경 (AJAX)
     */
    public function reorderOptions(Request $request, SurveyOptionCategory $category)
    {
        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*' => 'integer|exists:survey_options,id',
        ]);

        foreach ($validated['orders'] as $index => $optionId) {
            SurveyOption::where('id', $optionId)
                ->where('category_id', $category->id)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * 카테고리 순서 변경 (AJAX)
     */
    public function reorderCategories(Request $request)
    {
        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*' => 'integer|exists:survey_option_categories,id',
        ]);

        foreach ($validated['orders'] as $index => $categoryId) {
            SurveyOptionCategory::where('id', $categoryId)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
