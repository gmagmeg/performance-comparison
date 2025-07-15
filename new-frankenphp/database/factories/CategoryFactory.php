<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Fakerのシード値を固定して一貫性を保つ
        fake()->seed(12345);
        
        $name = fake()->unique()->words(rand(1, 3), true);
        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->randomNumber(5),
            'description' => rand(0, 1) ? fake()->paragraph() : null,
            'parent_id' => null, // Will be set manually when needed
            'sort_order' => rand(0, 100),
        ];
    }
} 