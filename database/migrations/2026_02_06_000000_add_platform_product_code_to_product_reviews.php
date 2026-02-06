<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            // 플랫폼별 상품코드 (네이버 상품번호, Qoo10 상품코드 등)
            $table->string('platform_product_code', 100)->nullable()->after('platform');

            // product_id를 nullable로 변경 (매칭 전 NULL 허용)
            $table->unsignedBigInteger('product_id')->nullable()->change();

            // 인덱스 추가
            $table->index(['platform', 'platform_product_code']);
        });
    }

    public function down(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropIndex(['platform', 'platform_product_code']);
            $table->dropColumn('platform_product_code');
        });
    }
};
