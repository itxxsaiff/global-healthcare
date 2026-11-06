<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\PractitionerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Article> */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'practitioner_profile_id' => PractitionerProfile::factory(),
            'author_id' => fn (array $a) => PractitionerProfile::find($a['practitioner_profile_id'])->user_id,
            'type' => 'standard',
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(4)),
            'excerpt' => fake()->sentence(15),
            'body' => '<p>'.fake()->paragraphs(8, true).'</p>',
            'word_count' => 650,
            'status' => ArticleStatus::Draft,
        ];
    }
}
