<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Post;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PostView>
 */
class PostViewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'user_id' => rand(0, 1) ? User::factory() : null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'viewed_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }
} 