<?php

namespace Database\Factories;

use App\Models\Entry;
use App\Models\Feed;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entry>
 */
class EntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'feed_id' => Feed::factory(),
            'guid' => fake()->unique()->uuid(),
            'url' => fake()->url(),
            'title' => fake()->sentence(),
            'author' => fake()->name(),
            'content' => fake()->paragraphs(3, true),
            'published_at' => fake()->dateTimeBetween('-1 month'),
        ];
    }
}
