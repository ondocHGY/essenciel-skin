<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_results', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('profile_id')->constrained('user_profiles')->onDelete('cascade');
            $table->json('timeline');
            $table->json('milestones');
            $table->json('comparison');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_results');
    }
};
