<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // 효능 발현 단계별 설명 (phase1, phase2, phase3)
            $table->json('efficacy_phases')->nullable()->after('efficacy_curve');

            // 효능 마일스톤 설정 (초기 체감 기간, 안정화 기간 등)
            $table->json('efficacy_milestones')->nullable()->after('efficacy_phases');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['efficacy_phases', 'efficacy_milestones']);
        });
    }
};
