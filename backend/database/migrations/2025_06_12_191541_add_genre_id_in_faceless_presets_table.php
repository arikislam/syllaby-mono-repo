<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faceless_presets', function (Blueprint $table) {
            $table->foreignId('genre_id')
                ->after('genre')
                ->nullable()
                ->constrained('genres')
                ->cascadeOnDelete();

            $table->renameColumn('genre', 'genre_name');
        });
    }

    public function down(): void
    {
        Schema::table('faceless_presets', function (Blueprint $table) {
            $table->dropForeign(['genre_id']);
            $table->dropColumn('genre_id');
        });
    }
};
