<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Article> */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'title' => $title,
            'slug' => Article::slugify($title).'-'.fake()->unique()->numerify('####'),
            'excerpt' => fake()->sentence(15),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'image' => null,
            'published_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
