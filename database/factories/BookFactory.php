<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'author' => fake()->name(),
            'isbn' => '9784' . fake()->unique()->numerify('#########'),
            'published_date' => fake()->dateTimeBetween('-30 years', 'now')->format('Y-m-d'),
            'description' => fake()->paragraph(),
            'image_url' => null,
        ];
    }
}