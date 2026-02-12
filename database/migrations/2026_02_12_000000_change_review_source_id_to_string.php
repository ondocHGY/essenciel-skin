<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            // FK 제약조건 제거
            $table->dropForeign(['review_source_id']);
        });

        Schema::table('product_reviews', function (Blueprint $table) {
            // integer → string 변경 (플랫폼 상품코드 저장용)
            $table->string('review_source_id', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('review_source_id')->nullable()->change();
        });

        Schema::table('product_reviews', function (Blueprint $table) {
            $table->foreign('review_source_id')
                ->references('id')
                ->on('product_review_sources')
                ->onDelete('set null');
        });
    }
};
