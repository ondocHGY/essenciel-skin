<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analysis_results', function (Blueprint $table) {
            $table->string('share_token', 32)->nullable()->unique()->after('session_id');
            $table->index('share_token');
        });
    }

    public function down(): void
    {
        Schema::table('analysis_results', function (Blueprint $table) {
            $table->dropIndex(['share_token']);
            $table->dropColumn('share_token');
        });
    }
};
