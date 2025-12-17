<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 설문 질문 테이블
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();  // age_group, gender, sleep_hours 등
            $table->string('title');               // 질문 제목
            $table->string('subtitle')->nullable(); // 부가 설명 (피부 턴오버 속도를 파악...)
            $table->string('category')->default('lifestyle'); // basic, lifestyle, habit
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 설문 옵션 테이블
        Schema::create('survey_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('survey_questions')->onDelete('cascade');
            $table->string('value', 50);           // 10대, 20대, under6 등
            $table->string('label');               // 표시될 라벨
            $table->string('description')->nullable(); // 옵션 추가 설명
            $table->decimal('modifier', 4, 2)->default(1.00); // 효능 보정계수 (0.70 ~ 1.50)
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['question_id', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_options');
        Schema::dropIfExists('survey_questions');
    }
};
