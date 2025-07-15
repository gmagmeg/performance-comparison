<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Post;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hasUser = rand(0, 1);
        return [
            'post_id' => Post::factory(),
            'user_id' => $hasUser ? User::factory() : null,
            'parent_id' => null, // Will be set manually for replies
            'author_name' => $hasUser ? null : fake()->name(),
            'author_email' => $hasUser ? null : fake()->safeEmail(),
            'content' => fake()->paragraphs(rand(1, 3), true),
            'status' => fake()->randomElement(['pending', 'approved', 'spam', 'rejected']),
        ];
    }
} 