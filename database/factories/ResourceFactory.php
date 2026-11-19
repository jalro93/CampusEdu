<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Resource> */
class ResourceFactory extends Factory
{
    protected $model = Resource::class;

    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'name' => fake()->words(3, true),
            'type' => Resource::TYPE_LINK,
            'url' => 'https://example.com/'.fake()->slug(),
        ];
    }
}
