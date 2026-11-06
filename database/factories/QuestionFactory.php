<?php

namespace Database\Factories;

use App\Enums\ModerationStatus;
use App\Models\Question;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Question> */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        $title = rtrim(fake()->sentence(8), '.').'?';

        return [
            'patient_id' => User::factory(),
            'specialty_id' => fn () => Specialty::query()->whereNull('parent_id')->value('id'),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(4)),
            'body' => fake()->paragraph(4),
            'status' => ModerationStatus::Pending,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => ModerationStatus::Approved, 'published_at' => now()->subDay()]);
    }
}
