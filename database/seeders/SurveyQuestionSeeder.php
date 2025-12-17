<?php

namespace Database\Seeders;

use App\Models\SurveyQuestion;
use Illuminate\Database\Seeder;

class SurveyQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            [
                'key' => 'age_group',
                'title' => '연령대를 선택해주세요',
                'subtitle' => '피부 턴오버 속도를 파악하는 데 사용돼요',
                'category' => 'basic',
                'sort_order' => 0,
                'options' => [
                    ['value' => '10대', 'label' => '10대', 'modifier' => 1.25],
                    ['value' => '20대', 'label' => '20대', 'modifier' => 1.15],
                    ['value' => '30대', 'label' => '30대', 'modifier' => 1.00],
                    ['value' => '40대', 'label' => '40대', 'modifier' => 0.85],
                    ['value' => '50대이상', 'label' => '50대 이상', 'modifier' => 0.70],
                ],
            ],
            [
                'key' => 'gender',
                'title' => '성별을 선택해주세요',
                'subtitle' => '피지 분비 및 각질 두께를 분석해요',
                'category' => 'basic',
                'sort_order' => 1,
                'options' => [
                    ['value' => 'male', 'label' => '남성', 'modifier' => 1.00],
                    ['value' => 'female', 'label' => '여성', 'modifier' => 1.00],
                    ['value' => 'other', 'label' => '기타', 'modifier' => 1.00],
                ],
            ],
            [
                'key' => 'sleep_hours',
                'title' => '평균 수면 시간은 어떻게 되시나요?',
                'subtitle' => '피부 재생 능력을 파악해요',
                'category' => 'lifestyle',
                'sort_order' => 2,
                'options' => [
                    ['value' => 'under6', 'label' => '6시간 미만', 'modifier' => 0.80],
                    ['value' => '6to8', 'label' => '6~8시간', 'modifier' => 1.00],
                    ['value' => 'over8', 'label' => '8시간 이상', 'modifier' => 1.15],
                ],
            ],
            [
                'key' => 'uv_exposure',
                'title' => '자외선 노출 정도는 어떠신가요?',
                'subtitle' => '멜라닌 활성도를 분석해요',
                'category' => 'lifestyle',
                'sort_order' => 3,
                'options' => [
                    ['value' => 'indoor', 'label' => '실내 위주', 'modifier' => 1.15],
                    ['value' => 'normal', 'label' => '보통', 'modifier' => 1.00],
                    ['value' => 'outdoor', 'label' => '실외 많음', 'modifier' => 0.80],
                ],
            ],
            [
                'key' => 'stress_level',
                'title' => '평소 스트레스 수준은 어떠신가요?',
                'subtitle' => '피부 염증 반응을 예측해요',
                'category' => 'lifestyle',
                'sort_order' => 4,
                'options' => [
                    ['value' => 'low', 'label' => '낮음', 'modifier' => 1.10],
                    ['value' => 'medium', 'label' => '보통', 'modifier' => 1.00],
                    ['value' => 'high', 'label' => '높음', 'modifier' => 0.80],
                ],
            ],
            [
                'key' => 'water_intake',
                'title' => '하루 수분 섭취량은 어떻게 되시나요?',
                'subtitle' => '수분 유지 메커니즘을 분석해요',
                'category' => 'lifestyle',
                'sort_order' => 5,
                'options' => [
                    ['value' => 'under1L', 'label' => '1L 미만', 'modifier' => 0.80],
                    ['value' => '1to2L', 'label' => '1~2L', 'modifier' => 1.00],
                    ['value' => 'over2L', 'label' => '2L 이상', 'modifier' => 1.15],
                ],
            ],
            [
                'key' => 'alcohol',
                'title' => '음주 빈도는 어떻게 되시나요?',
                'subtitle' => '피부 장벽 손상 빈도를 파악해요',
                'category' => 'lifestyle',
                'sort_order' => 6,
                'options' => [
                    ['value' => 'none', 'label' => '안함', 'modifier' => 1.15],
                    ['value' => 'sometimes', 'label' => '가끔', 'modifier' => 1.00],
                    ['value' => 'often', 'label' => '자주', 'modifier' => 0.80],
                ],
            ],
            [
                'key' => 'smoking',
                'title' => '흡연 여부를 알려주세요',
                'subtitle' => '산화 스트레스를 분석해요',
                'category' => 'lifestyle',
                'sort_order' => 7,
                'options' => [
                    ['value' => 'none', 'label' => '안함', 'modifier' => 1.15],
                    ['value' => 'sometimes', 'label' => '가끔', 'modifier' => 0.95],
                    ['value' => 'often', 'label' => '자주', 'modifier' => 0.75],
                ],
            ],
            [
                'key' => 'care_steps',
                'title' => '현재 스킨케어 단계 수는 어떻게 되시나요?',
                'subtitle' => '기존 관리 습관을 파악해 효과를 예측해요',
                'category' => 'habit',
                'sort_order' => 8,
                'options' => [
                    ['value' => 'basic', 'label' => '3단계 이하', 'modifier' => 0.90],
                    ['value' => 'advanced', 'label' => '4단계 이상', 'modifier' => 1.10],
                ],
            ],
        ];

        foreach ($questions as $questionData) {
            $options = $questionData['options'];
            unset($questionData['options']);

            $question = SurveyQuestion::updateOrCreate(
                ['key' => $questionData['key']],
                $questionData
            );

            foreach ($options as $index => $optionData) {
                $question->options()->updateOrCreate(
                    ['value' => $optionData['value']],
                    array_merge($optionData, ['sort_order' => $index, 'is_active' => true])
                );
            }
        }

        $this->command->info('설문 질문 데이터가 성공적으로 등록되었습니다.');
    }
}
