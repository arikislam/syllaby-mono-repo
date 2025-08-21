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
        Schema::table('users', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('social_accounts', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('social_channels', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('user_feedback', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('trackers', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('industries', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('industry_user', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('video_assets', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('footages', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('captions', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('real_clones', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('avatars', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('surveys', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('bookmarks', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('taggables', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('ideas', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('keywords', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('related_topics', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('keyword_user', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('metadata', function (Blueprint $table) {
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('social_channels', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('user_feedback', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('trackers', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('industries', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('industry_user', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('video_assets', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('footages', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('captions', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('real_clones', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('avatars', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('surveys', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('bookmarks', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('taggables', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('ideas', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('keywords', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('related_topics', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('keyword_user', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('metadata', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });
    }
};
