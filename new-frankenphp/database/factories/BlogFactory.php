<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Category;
use App\Models\Comment;
use App\Models\PostView;
use App\Models\Like;

/**
 * Unified Factory for all blog-related models
 */
class BlogFactory extends Factory
{
    /**
     * Create a User factory
     */
    public function user(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Create a Post factory
     */
    public function post(): array
    {
        $title = fake()->sentence(rand(3, 8));
        return [
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(8),
            'content' => fake()->paragraphs(rand(5, 10), true),
            'excerpt' => fake()->paragraph(rand(2, 4)),
            'featured_image' => rand(0, 1) ? fake()->imageUrl(800, 600, 'articles') : null,
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'published_at' => rand(0, 1) ? fake()->dateTimeBetween('-1 year', 'now') : null,
        ];
    }

    /**
     * Create a Tag factory
     */
    public function tag(): array
    {
        $name = fake()->unique()->words(rand(1, 2), true);
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => rand(0, 1) ? fake()->sentence() : null,
            'color' => fake()->hexColor(),
        ];
    }

    /**
     * Create a Category factory
     */
    public function category(): array
    {
        $name = fake()->unique()->words(rand(1, 3), true);
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => rand(0, 1) ? fake()->paragraph() : null,
            'parent_id' => rand(0, 1) ? null : Category::factory(),
            'sort_order' => rand(0, 100),
        ];
    }

    /**
     * Create a Comment factory
     */
    public function comment(): array
    {
        $hasUser = rand(0, 1);
        return [
            'post_id' => Post::factory(),
            'user_id' => $hasUser ? User::factory() : null,
            'parent_id' => rand(0, 1) ? null : Comment::factory(),
            'author_name' => $hasUser ? null : fake()->name(),
            'author_email' => $hasUser ? null : fake()->safeEmail(),
            'content' => fake()->paragraphs(rand(1, 3), true),
            'status' => fake()->randomElement(['pending', 'approved', 'spam', 'rejected']),
        ];
    }

    /**
     * Create a PostView factory
     */
    public function postView(): array
    {
        return [
            'post_id' => Post::factory(),
            'user_id' => rand(0, 1) ? User::factory() : null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'viewed_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }

    /**
     * Create a Like factory
     */
    public function like(): array
    {
        $likeables = [
            ['type' => Post::class, 'factory' => Post::factory()],
            ['type' => Comment::class, 'factory' => Comment::factory()],
        ];
        $likeable = fake()->randomElement($likeables);
        
        return [
            'user_id' => User::factory(),
            'likeable_type' => $likeable['type'],
            'likeable_id' => $likeable['factory'],
            'created_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }

    /**
     * Static factory methods for each model
     */
    public static function createUser($attributes = []): array
    {
        return array_merge((new static)->user(), $attributes);
    }

    public static function createPost($attributes = []): array
    {
        return array_merge((new static)->post(), $attributes);
    }

    public static function createTag($attributes = []): array
    {
        return array_merge((new static)->tag(), $attributes);
    }

    public static function createCategory($attributes = []): array
    {
        return array_merge((new static)->category(), $attributes);
    }

    public static function createComment($attributes = []): array
    {
        return array_merge((new static)->comment(), $attributes);
    }

    public static function createPostView($attributes = []): array
    {
        return array_merge((new static)->postView(), $attributes);
    }

    public static function createLike($attributes = []): array
    {
        return array_merge((new static)->like(), $attributes);
    }

    /**
     * Default definition (not used, but required by Factory class)
     */
    public function definition(): array
    {
        return [];
    }
} 