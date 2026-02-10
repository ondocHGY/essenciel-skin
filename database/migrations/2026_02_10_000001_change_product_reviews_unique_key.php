<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            // 기존: unique(['platform', 'external_id'])
            // → 같은 리뷰가 여러 product에 연결 가능하도록 변경
            $table->dropUnique(['platform', 'external_id']);
        });

        Schema::table('product_reviews', function (Blueprint $table) {
            // 새: product_id별로 같은 리뷰 허용 (다대다 복제 방식)
            $table->unique(['platform', 'external_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropUnique(['platform', 'external_id', 'product_id']);
        });

        Schema::table('product_reviews', function (Blueprint $table) {
            $table->unique(['platform', 'external_id']);
        });
    }
};
