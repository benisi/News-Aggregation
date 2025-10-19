<?php

namespace Database\Factories;

use App\Models\Author;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuthorFactory extends Factory
{
    protected $model = Author::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'source_id' => Source::factory(),
        ];
    }
}
