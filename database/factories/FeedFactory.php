<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Feed;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Feed>
 */
class FeedFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => fake()->sentence(3),
            'url' => fake()->unique()->url(),
            'site_url' => fake()->url(),
            'description' => fake()->sentence(),
        ];
    }
}
