<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facelesses', function (Blueprint $table) {
            $table->foreignId('character_id')->nullable()->after('character')->constrained('characters')->nullOnDelete();
            $table->foreignId('genre_id')->nullable()->after('genre')->constrained('genres')->nullOnDelete();

            $table->renameColumn('character', 'character_name');
            $table->renameColumn('genre', 'genre_name');
        });
    }

    public function down(): void
    {
        Schema::table('facelesses', function (Blueprint $table) {
            $table->dropForeign(['character_id']);
            $table->dropForeign(['genre_id']);
            $table->dropColumn(['character_id', 'genre_id']);
        });
    }
};
