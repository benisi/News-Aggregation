<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->paragraph(2),
            'content' => $this->faker->paragraphs(5, true),
            'url' => $this->faker->unique()->url(),
            'image_url' => $this->faker->imageUrl(),
            'published_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'source_id' => Source::factory(),
            'category_id' => Category::factory(),
        ];
    }
}
