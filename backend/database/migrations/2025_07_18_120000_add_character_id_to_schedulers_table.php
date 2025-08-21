<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedulers', function (Blueprint $table) {
            $table->foreignId('character_id')
                ->nullable()
                ->after('idea_id')
                ->constrained('characters')
                ->nullOnDelete();

            $table->index('character_id');
        });
    }

    public function down(): void
    {
        Schema::table('schedulers', function (Blueprint $table) {
            $table->dropForeign(['character_id']);
            $table->dropColumn('character_id');
        });
    }
};
