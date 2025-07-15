<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tags table
        if (!Schema::hasTable('tags')) {
            Schema::create('tags', function (Blueprint $table) {
                $table->id('tag_id');
                $table->string('name', 100)->unique();
                $table->string('slug', 100)->unique();
                $table->text('description')->nullable();
                $table->string('color', 7)->default('#000000');
                $table->timestamps();
                
                $table->index('slug', 'idx_slug');
            });
        }

        // Categories table
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id('category_id');
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                
                $table->foreign('parent_id', 'categories_parent_id_foreign')
                      ->references('category_id')->on('categories')
                      ->onDelete('set null');
                $table->index('slug', 'idx_slug');
                $table->index('parent_id', 'idx_parent_id');
            });
        }

        // Post-Tags pivot table
        if (!Schema::hasTable('post_tags')) {
            Schema::create('post_tags', function (Blueprint $table) {
                $table->id('post_tag_id');
                $table->unsignedBigInteger('post_id');
                $table->unsignedBigInteger('tag_id');
                $table->timestamp('created_at')->nullable();
                
                $table->foreign('post_id', 'post_tags_post_id_foreign')
                      ->references('post_id')->on('posts')
                      ->onDelete('cascade');
                $table->foreign('tag_id', 'post_tags_tag_id_foreign')
                      ->references('tag_id')->on('tags')
                      ->onDelete('cascade');
                
                $table->unique(['post_id', 'tag_id'], 'unique_post_tag');
                $table->index('post_id', 'idx_post_id');
                $table->index('tag_id', 'idx_tag_id');
            });
        }

        // Post-Categories pivot table
        if (!Schema::hasTable('post_categories')) {
            Schema::create('post_categories', function (Blueprint $table) {
                $table->id('post_category_id');
                $table->unsignedBigInteger('post_id');
                $table->unsignedBigInteger('category_id');
                $table->timestamp('created_at')->nullable();
                
                $table->foreign('post_id', 'post_categories_post_id_foreign')
                      ->references('post_id')->on('posts')
                      ->onDelete('cascade');
                $table->foreign('category_id', 'post_categories_category_id_foreign')
                      ->references('category_id')->on('categories')
                      ->onDelete('cascade');
                
                $table->unique(['post_id', 'category_id'], 'unique_post_category');
                $table->index('post_id', 'idx_post_id');
                $table->index('category_id', 'idx_category_id');
            });
        }

        // Comments table
        if (!Schema::hasTable('comments')) {
            Schema::create('comments', function (Blueprint $table) {
                $table->id('comment_id');
                $table->unsignedBigInteger('post_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('author_name')->nullable();
                $table->string('author_email')->nullable();
                $table->text('content');
                $table->enum('status', ['pending', 'approved', 'spam', 'rejected'])->default('pending');
                $table->timestamps();
                
                $table->foreign('post_id', 'comments_post_id_foreign')
                      ->references('post_id')->on('posts')
                      ->onDelete('cascade');
                $table->foreign('user_id', 'comments_user_id_foreign')
                      ->references('id')->on('users')
                      ->onDelete('set null');
                $table->foreign('parent_id', 'comments_parent_id_foreign')
                      ->references('comment_id')->on('comments')
                      ->onDelete('cascade');
                
                $table->index('post_id', 'idx_post_id');
                $table->index('user_id', 'idx_user_id');
                $table->index('parent_id', 'idx_parent_id');
                $table->index('status', 'idx_status');
            });
        }

        // Post views table
        if (!Schema::hasTable('post_views')) {
            Schema::create('post_views', function (Blueprint $table) {
                $table->id('post_view_id');
                $table->unsignedBigInteger('post_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('viewed_at')->useCurrent();
                
                $table->foreign('post_id', 'post_views_post_id_foreign')
                      ->references('post_id')->on('posts')
                      ->onDelete('cascade');
                $table->foreign('user_id', 'post_views_user_id_foreign')
                      ->references('id')->on('users')
                      ->onDelete('set null');
                
                $table->index('post_id', 'idx_post_id');
                $table->index('user_id', 'idx_user_id');
                $table->index('viewed_at', 'idx_viewed_at');
            });
        }

        // Likes table (polymorphic)
        if (!Schema::hasTable('likes')) {
            Schema::create('likes', function (Blueprint $table) {
                $table->id('like_id');
                $table->unsignedBigInteger('user_id');
                $table->string('likeable_type');
                $table->unsignedBigInteger('likeable_id');
                $table->timestamp('created_at')->nullable();
                
                $table->foreign('user_id', 'likes_user_id_foreign')
                      ->references('id')->on('users')
                      ->onDelete('cascade');
                
                $table->unique(['user_id', 'likeable_type', 'likeable_id'], 'unique_like');
                $table->index(['likeable_type', 'likeable_id'], 'idx_likeable');
                $table->index('user_id', 'idx_user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('likes');
        Schema::dropIfExists('post_views');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('post_categories');
        Schema::dropIfExists('post_tags');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('tags');
    }
};
