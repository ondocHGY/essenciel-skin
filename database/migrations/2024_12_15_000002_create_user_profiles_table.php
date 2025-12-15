<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->string('age_group');
            $table->string('skin_type');
            $table->string('gender');
            $table->json('concerns');
            $table->json('lifestyle');
            $table->json('skincare_habit');
            $table->tinyInteger('satisfaction');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
