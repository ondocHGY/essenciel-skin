<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('intro_metrics')->nullable()->after('efficacy_metrics');
            $table->json('intro_summary')->nullable()->after('intro_metrics');
            $table->integer('intro_review_count')->nullable()->after('intro_summary');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['intro_metrics', 'intro_summary', 'intro_review_count']);
        });
    }
};
