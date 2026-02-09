<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('review_source_id')->nullable();
            $table->string('platform', 50);
            $table->string('trigger_type', 20); // 'scheduled' | 'manual'
            $table->string('status', 20);        // 'running' | 'success' | 'failed'
            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->integer('reviews_added')->default(0);
            $table->integer('reviews_updated')->default(0);
            $table->integer('total_reviews')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('review_source_id')
                  ->references('id')
                  ->on('product_review_sources')
                  ->nullOnDelete();

            $table->index(['platform', 'status']);
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
