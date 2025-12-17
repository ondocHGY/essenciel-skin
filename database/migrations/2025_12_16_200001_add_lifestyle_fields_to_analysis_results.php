<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analysis_results', function (Blueprint $table) {
            $table->json('lifestyle_factors')->nullable()->after('metrics');
            $table->json('usage_guide')->nullable()->after('lifestyle_factors');
        });
    }

    public function down(): void
    {
        Schema::table('analysis_results', function (Blueprint $table) {
            $table->dropColumn(['lifestyle_factors', 'usage_guide']);
        });
    }
};
