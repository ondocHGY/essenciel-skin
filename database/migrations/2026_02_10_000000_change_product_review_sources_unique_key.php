<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_review_sources', function (Blueprint $table) {
            // 1. 외래 키 제거
            $table->dropForeign(['product_id']);
            // 2. 기존 유니크 키 제거
            $table->dropUnique(['product_id', 'platform']);
            // 3. product_id nullable로 변경
            $table->unsignedBigInteger('product_id')->nullable()->change();
        });

        Schema::table('product_review_sources', function (Blueprint $table) {
            // 4. 다대다: 같은 external_id가 여러 product에 매칭 가능
            //    같은 조합의 중복만 방지
            $table->unique(['product_id', 'platform', 'external_id']);
            // 5. external_id로 검색 성능 확보
            $table->index(['platform', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::table('product_review_sources', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'platform', 'external_id']);
            $table->dropIndex(['platform', 'external_id']);
        });

        Schema::table('product_review_sources', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
            $table->unique(['product_id', 'platform']);
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }
};
