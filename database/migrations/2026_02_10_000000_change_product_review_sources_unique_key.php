<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 기존 제약조건 안전하게 제거 (어떤 상태든 대응)
        $this->safeDropForeign('product_review_sources', 'product_review_sources_product_id_foreign');
        $this->safeDropIndex('product_review_sources', 'product_review_sources_product_id_platform_unique');
        $this->safeDropIndex('product_review_sources', 'product_review_sources_platform_external_id_unique');
        $this->safeDropIndex('product_review_sources', 'product_review_sources_platform_external_id_index');
        $this->safeDropIndex('product_review_sources', 'product_review_sources_product_id_platform_external_id_unique');

        // product_id nullable로 변경
        Schema::table('product_review_sources', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
        });

        // 새 제약조건 추가
        Schema::table('product_review_sources', function (Blueprint $table) {
            // 다대다: 같은 external_id가 여러 product에 매칭 가능
            $table->unique(['product_id', 'platform', 'external_id']);
            // external_id로 검색 성능 확보
            $table->index(['platform', 'external_id']);
        });
    }

    public function down(): void
    {
        $this->safeDropIndex('product_review_sources', 'product_review_sources_product_id_platform_external_id_unique');
        $this->safeDropIndex('product_review_sources', 'product_review_sources_platform_external_id_index');

        Schema::table('product_review_sources', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
            $table->unique(['product_id', 'platform']);
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    private function safeDropIndex(string $table, string $indexName): void
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            if (!empty($indexes)) {
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
            }
        } catch (\Exception $e) {
            // 인덱스가 없으면 무시
        }
    }

    private function safeDropForeign(string $table, string $foreignName): void
    {
        try {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$foreignName}`");
        } catch (\Exception $e) {
            // FK가 없으면 무시
        }
    }
};
