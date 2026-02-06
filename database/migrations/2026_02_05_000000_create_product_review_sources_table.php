<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_review_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('platform'); // shopee, naver
            $table->string('platform_name'); // 표시용 이름 (자사몰, 네이버 스마트스토어)
            $table->string('external_url')->nullable(); // 상품 페이지 URL
            $table->string('external_id')->nullable(); // 외부 상품 ID
            $table->unsignedInteger('review_count')->default(0);
            $table->decimal('average_rating', 2, 1)->default(0); // 4.5 형식
            $table->json('recent_reviews')->nullable(); // 최근 리뷰 N개 캐싱
            $table->json('api_config')->nullable(); // API 키, 토큰 등 (암호화 권장)
            $table->boolean('is_active')->default(true);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_review_sources');
    }
};
