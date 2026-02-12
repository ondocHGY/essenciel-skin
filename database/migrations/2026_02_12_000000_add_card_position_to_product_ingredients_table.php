<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_ingredients', function (Blueprint $table) {
            $table->json('card_position')->nullable()->after('tags');
        });
    }

    public function down(): void
    {
        Schema::table('product_ingredients', function (Blueprint $table) {
            $table->dropColumn('card_position');
        });
    }
};
