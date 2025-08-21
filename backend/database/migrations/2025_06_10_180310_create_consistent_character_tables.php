<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->json('details')->nullable();
            $table->text('prompt')->nullable();
            $table->json('meta')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('consistent_character')->default(true);
            $table->timestamps();
        });

        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete(); // TODO: ADD IN DELETION
            $table->foreignId('genre_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('provider_id')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->integer('training_images')->nullable();
            $table->string('gender');
            $table->string('model')->nullable();
            $table->string('trigger')->nullable();
            $table->text('prompt')->nullable();
            $table->string('status')->nullable();
            $table->json('meta')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('characters');
        Schema::dropIfExists('genres');
    }
};
