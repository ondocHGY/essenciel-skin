<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurveyQuestion;
use App\Models\SurveyOption;
use Illuminate\Http\Request;

class SurveyQuestionController extends Controller
{
    public function index()
    {
        $questions = SurveyQuestion::with('options')
            ->orderBy('sort_order')
            ->get();

        return view('admin.survey-questions.index', compact('questions'));
    }

    public function create()
    {
        return view('admin.survey-questions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:50|unique:survey_questions,key|regex:/^[a-z_]+$/',
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'category' => 'required|string|in:basic,lifestyle,habit',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'options' => 'required|array|min:2',
            'options.*.value' => 'required|string|max:50',
            'options.*.label' => 'required|string|max:255',
            'options.*.description' => 'nullable|string|max:500',
            'options.*.modifier' => 'required|numeric|min:0.1|max:2.0',
        ]);

        $question = SurveyQuestion::create([
            'key' => $validated['key'],
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'],
            'category' => $validated['category'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        foreach ($validated['options'] as $index => $optionData) {
            $question->options()->create([
                'value' => $optionData['value'],
                'label' => $optionData['label'],
                'description' => $optionData['description'] ?? null,
                'modifier' => $optionData['modifier'],
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }

        return redirect()->route('admin.survey-questions.index')
            ->with('success', '설문 질문이 등록되었습니다.');
    }

    public function edit(SurveyQuestion $surveyQuestion)
    {
        $surveyQuestion->load('options');
        return view('admin.survey-questions.edit', compact('surveyQuestion'));
    }

    public function update(Request $request, SurveyQuestion $surveyQuestion)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:50|regex:/^[a-z_]+$/|unique:survey_questions,key,' . $surveyQuestion->id,
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'category' => 'required|string|in:basic,lifestyle,habit',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'options' => 'required|array|min:2',
            'options.*.id' => 'nullable|integer',
            'options.*.value' => 'required|string|max:50',
            'options.*.label' => 'required|string|max:255',
            'options.*.description' => 'nullable|string|max:500',
            'options.*.modifier' => 'required|numeric|min:0.1|max:2.0',
            'options.*.is_active' => 'boolean',
        ]);

        $surveyQuestion->update([
            'key' => $validated['key'],
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'],
            'category' => $validated['category'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // 기존 옵션 ID 목록
        $existingIds = $surveyQuestion->options->pluck('id')->toArray();
        $updatedIds = [];

        foreach ($validated['options'] as $index => $optionData) {
            if (!empty($optionData['id'])) {
                // 기존 옵션 업데이트
                $option = SurveyOption::find($optionData['id']);
                if ($option && $option->question_id === $surveyQuestion->id) {
                    $option->update([
                        'value' => $optionData['value'],
                        'label' => $optionData['label'],
                        'description' => $optionData['description'] ?? null,
                        'modifier' => $optionData['modifier'],
                        'sort_order' => $index,
                        'is_active' => $optionData['is_active'] ?? true,
                    ]);
                    $updatedIds[] = $option->id;
                }
            } else {
                // 새 옵션 생성
                $newOption = $surveyQuestion->options()->create([
                    'value' => $optionData['value'],
                    'label' => $optionData['label'],
                    'description' => $optionData['description'] ?? null,
                    'modifier' => $optionData['modifier'],
                    'sort_order' => $index,
                    'is_active' => $optionData['is_active'] ?? true,
                ]);
                $updatedIds[] = $newOption->id;
            }
        }

        // 삭제된 옵션 제거
        $toDelete = array_diff($existingIds, $updatedIds);
        if (!empty($toDelete)) {
            SurveyOption::whereIn('id', $toDelete)->delete();
        }

        return redirect()->route('admin.survey-questions.index')
            ->with('success', '설문 질문이 수정되었습니다.');
    }

    public function destroy(SurveyQuestion $surveyQuestion)
    {
        $surveyQuestion->delete();

        return redirect()->route('admin.survey-questions.index')
            ->with('success', '설문 질문이 삭제되었습니다.');
    }

    /**
     * 질문 순서 변경 (AJAX)
     */
    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:survey_questions,id',
        ]);

        foreach ($validated['order'] as $index => $id) {
            SurveyQuestion::where('id', $id)->update(['sort_order' => $index]);
        }

        SurveyQuestion::clearCache();

        return response()->json(['success' => true]);
    }
}
