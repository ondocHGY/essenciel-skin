<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable(); // 매칭 전 NULL 가능
            $table->foreignId('review_source_id')->nullable()->constrained('product_review_sources')->onDelete('set null');
            $table->string('platform', 50);
            $table->string('platform_product_code', 100)->nullable(); // 플랫폼별 상품코드 (Qoo10, 네이버 등)
            $table->string('external_id')->nullable(); // 플랫폼의 리뷰 ID
            $table->decimal('rating', 2, 1)->default(5.0);
            $table->string('title')->nullable();
            $table->text('content');
            $table->string('author')->nullable();
            $table->string('purchased_option')->nullable(); // 구매 옵션 (색상, 용량 등)
            $table->json('images')->nullable(); // 리뷰 이미지 URL들
            $table->boolean('is_featured')->default(false); // 대표 리뷰 여부
            $table->boolean('is_visible')->default(true); // 표시 여부
            $table->timestamp('reviewed_at')->nullable(); // 원본 리뷰 작성일
            $table->timestamps();

            $table->index(['product_id', 'platform']);
            $table->index(['platform', 'platform_product_code']); // 상품코드로 매칭용
            $table->index(['product_id', 'is_featured']);
            $table->unique(['platform', 'external_id']); // 중복 방지

            // product_id는 nullable이므로 foreign key 대신 index만
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
