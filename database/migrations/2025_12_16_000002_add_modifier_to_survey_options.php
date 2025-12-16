<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // survey_options 테이블에 modifier 컬럼 추가
        Schema::table('survey_options', function (Blueprint $table) {
            $table->decimal('modifier', 4, 2)->default(1.00)->after('icon');
        });

        // survey_option_categories 테이블에 is_system 컬럼 추가
        Schema::table('survey_option_categories', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('is_active');
        });

        // 기존 카테고리를 시스템 카테고리로 표시
        DB::table('survey_option_categories')->update(['is_system' => true]);

        // 기존 하드코딩된 modifier 값 적용
        $modifiers = [
            // 연령대
            'age_groups' => [
                '10대' => 1.20,
                '20대초반' => 1.15,
                '20대후반' => 1.10,
                '30대' => 1.00,
                '40대' => 0.85,
                '50대이상' => 0.70,
            ],
            // 피부 타입
            'skin_types' => [
                '중성' => 1.10,
                '지성' => 1.00,
                '건성' => 0.95,
                '복합성' => 0.90,
                '민감성' => 0.80,
            ],
            // 규칙성
            'consistency_options' => [
                'always' => 1.30,
                'regular' => 1.00,
                'sometimes' => 0.60,
            ],
            // 수면 시간
            'sleep_hours' => [
                'under6' => 0.85,
                '6to8' => 1.00,
                'over8' => 1.10,
            ],
            // 자외선 노출
            'uv_exposure' => [
                'indoor' => 1.10,
                'normal' => 1.00,
                'outdoor' => 0.85,
            ],
            // 스트레스 수준
            'stress_levels' => [
                'low' => 1.10,
                'medium' => 1.00,
                'high' => 0.85,
            ],
            // 수분 섭취
            'water_intake' => [
                'under1L' => 0.90,
                '1to2L' => 1.00,
                'over2L' => 1.10,
            ],
            // 음주/흡연
            'smoking_drinking' => [
                'none' => 1.10,
                'sometimes' => 1.00,
                'often' => 0.85,
            ],
        ];

        foreach ($modifiers as $categoryKey => $optionModifiers) {
            $category = DB::table('survey_option_categories')
                ->where('key', $categoryKey)
                ->first();

            if ($category) {
                foreach ($optionModifiers as $optionValue => $modifier) {
                    DB::table('survey_options')
                        ->where('category_id', $category->id)
                        ->where('value', $optionValue)
                        ->update(['modifier' => $modifier]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('survey_options', function (Blueprint $table) {
            $table->dropColumn('modifier');
        });

        Schema::table('survey_option_categories', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
