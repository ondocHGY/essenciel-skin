<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('active_ingredients')->nullable()->after('nanoliposome_info');
            $table->json('technology_section')->nullable()->after('active_ingredients');
            $table->json('sci_section')->nullable()->after('technology_section');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['active_ingredients', 'technology_section', 'sci_section']);
        });
    }
};
