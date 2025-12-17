<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Products 테이블에 efficacy_type 추가 (단일 효능 집중)
        Schema::table('products', function (Blueprint $table) {
            $table->string('efficacy_type')->default('moisture')->after('category');
            // 효능별 28일 예측 커브 데이터 (JSON)
            $table->json('efficacy_curve')->nullable()->after('efficacy_type');
            // 성분 상세 정보 (나중에 업로드)
            $table->json('ingredient_details')->nullable()->after('ingredients');
            // 나노리포좀 정보 (나중에 업로드)
            $table->json('nanoliposome_info')->nullable()->after('ingredient_details');
        });

        // UserProfiles 테이블 구조 변경 (새로운 9개 질문)
        Schema::table('user_profiles', function (Blueprint $table) {
            // 음주/흡연 분리
            $table->string('alcohol')->nullable()->after('lifestyle');
            $table->string('smoking')->nullable()->after('alcohol');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['efficacy_type', 'efficacy_curve', 'ingredient_details', 'nanoliposome_info']);
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['alcohol', 'smoking']);
        });
    }
};
