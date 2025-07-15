<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Like>
 */
class LikeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
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
} 