<?php

namespace Database\Seeders;

use App\Models\SurveyOptionCategory;
use Illuminate\Database\Seeder;

class SurveyOptionSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'key' => 'age_groups',
                'name' => '연령대',
                'sort_order' => 1,
                'options' => [
                    ['value' => '10대', 'label' => '10대'],
                    ['value' => '20대초반', 'label' => '20대 초반'],
                    ['value' => '20대후반', 'label' => '20대 후반'],
                    ['value' => '30대', 'label' => '30대'],
                    ['value' => '40대', 'label' => '40대'],
                    ['value' => '50대이상', 'label' => '50대 이상'],
                ]
            ],
            [
                'key' => 'skin_types',
                'name' => '피부 타입',
                'sort_order' => 2,
                'options' => [
                    ['value' => '건성', 'label' => '건성'],
                    ['value' => '지성', 'label' => '지성'],
                    ['value' => '복합성', 'label' => '복합성'],
                    ['value' => '민감성', 'label' => '민감성'],
                    ['value' => '중성', 'label' => '중성'],
                ]
            ],
            [
                'key' => 'genders',
                'name' => '성별',
                'sort_order' => 3,
                'options' => [
                    ['value' => 'female', 'label' => '여성'],
                    ['value' => 'male', 'label' => '남성'],
                    ['value' => 'other', 'label' => '기타'],
                ]
            ],
            [
                'key' => 'concerns',
                'name' => '피부 고민',
                'has_icon' => true,
                'is_multiple' => true,
                'sort_order' => 4,
                'options' => [
                    ['value' => 'wrinkle', 'label' => '주름', 'icon' => '🔲'],
                    ['value' => 'elasticity', 'label' => '탄력저하', 'icon' => '📉'],
                    ['value' => 'pigmentation', 'label' => '색소침착', 'icon' => '🔵'],
                    ['value' => 'pore', 'label' => '모공', 'icon' => '⚫'],
                    ['value' => 'acne', 'label' => '여드름', 'icon' => '🔴'],
                    ['value' => 'dryness', 'label' => '건조함', 'icon' => '🏜️'],
                    ['value' => 'redness', 'label' => '홍조', 'icon' => '🌹'],
                    ['value' => 'dullness', 'label' => '칙칙함', 'icon' => '😶'],
                ]
            ],
            [
                'key' => 'sleep_hours',
                'name' => '수면 시간',
                'sort_order' => 5,
                'options' => [
                    ['value' => 'under6', 'label' => '6시간 미만'],
                    ['value' => '6to8', 'label' => '6-8시간'],
                    ['value' => 'over8', 'label' => '8시간 이상'],
                ]
            ],
            [
                'key' => 'uv_exposure',
                'name' => '자외선 노출',
                'sort_order' => 6,
                'options' => [
                    ['value' => 'indoor', 'label' => '실내 위주'],
                    ['value' => 'normal', 'label' => '보통'],
                    ['value' => 'outdoor', 'label' => '실외 많음'],
                ]
            ],
            [
                'key' => 'stress_levels',
                'name' => '스트레스 수준',
                'sort_order' => 7,
                'options' => [
                    ['value' => 'low', 'label' => '낮음'],
                    ['value' => 'medium', 'label' => '보통'],
                    ['value' => 'high', 'label' => '높음'],
                ]
            ],
            [
                'key' => 'water_intake',
                'name' => '수분 섭취',
                'sort_order' => 8,
                'options' => [
                    ['value' => 'under1L', 'label' => '1L 미만'],
                    ['value' => '1to2L', 'label' => '1-2L'],
                    ['value' => 'over2L', 'label' => '2L 이상'],
                ]
            ],
            [
                'key' => 'smoking_drinking',
                'name' => '음주/흡연',
                'sort_order' => 9,
                'options' => [
                    ['value' => 'none', 'label' => '안함'],
                    ['value' => 'sometimes', 'label' => '가끔'],
                    ['value' => 'often', 'label' => '자주'],
                ]
            ],
            [
                'key' => 'care_steps',
                'name' => '스킨케어 단계 수',
                'sort_order' => 10,
                'options' => [
                    ['value' => '3이하', 'label' => '3단계 이하'],
                    ['value' => '5단계', 'label' => '5단계'],
                    ['value' => '7이상', 'label' => '7단계 이상'],
                ]
            ],
            [
                'key' => 'consistency_options',
                'name' => '스킨케어 규칙성',
                'sort_order' => 11,
                'options' => [
                    ['value' => 'sometimes', 'label' => '가끔'],
                    ['value' => 'regular', 'label' => '규칙적'],
                    ['value' => 'always', 'label' => '매일'],
                ]
            ],
        ];

        foreach ($categories as $categoryData) {
            $options = $categoryData['options'];
            unset($categoryData['options']);

            $category = SurveyOptionCategory::create($categoryData);

            foreach ($options as $index => $option) {
                $category->options()->create([
                    ...$option,
                    'sort_order' => $index,
                ]);
            }
        }
    }
}
