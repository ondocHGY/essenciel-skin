<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 설문 옵션 카테고리 테이블
        Schema::create('survey_option_categories', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('has_icon')->default(false);
            $table->boolean('is_multiple')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 설문 옵션 항목 테이블
        Schema::create('survey_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                  ->constrained('survey_option_categories')
                  ->onDelete('cascade');
            $table->string('value');
            $table->string('label');
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['category_id', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_options');
        Schema::dropIfExists('survey_option_categories');
    }
};
