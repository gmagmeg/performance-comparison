<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(rand(3, 8));
        return [
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title) . '-' . \Illuminate\Support\Str::random(8),
            'content' => fake()->paragraphs(rand(5, 10), true),
            'excerpt' => fake()->paragraph(rand(2, 4)),
            'featured_image' => rand(0, 1) ? fake()->imageUrl(800, 600, 'articles') : null,
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'published_at' => rand(0, 1) ? fake()->dateTimeBetween('-1 year', 'now') : null,
        ];
    }
}
