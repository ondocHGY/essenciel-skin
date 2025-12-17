<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // 제품별 효능 측정 기준값 설정
            // baseline: [min, max] - 초기값 범위
            // targetImprovement: 최대 개선량
            $table->json('efficacy_metrics')->nullable()->after('efficacy_milestones');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('efficacy_metrics');
        });
    }
};
