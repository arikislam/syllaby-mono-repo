<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('facelesses', function (Blueprint $table) {
            $table->string('character')->nullable()->after('genre')->comment('the story main character');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facelesses', function (Blueprint $table) {
            $table->dropColumn('character');
        });
    }
};
