<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faceless_presets', function (Blueprint $table) {
            $table->unsignedBigInteger('music_category_id')->after('music_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('faceless_presets', function (Blueprint $table) {
            $table->dropColumn('music_category_id');
        });
    }
};
