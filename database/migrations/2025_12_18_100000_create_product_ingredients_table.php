<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name');                          // 성분명
            $table->string('image')->nullable();             // 이미지 경로
            $table->string('percentage')->nullable();        // 함유량 (예: "2%")
            $table->text('description')->nullable();         // 설명
            $table->json('tags')->nullable();                // 2차 태그 배열
            $table->integer('sort_order')->default(0);       // 정렬 순서
            $table->boolean('is_active')->default(true);     // 활성화 여부
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_ingredients');
    }
};
