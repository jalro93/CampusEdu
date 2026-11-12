<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\EnrollmentRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EnrollmentRequest> */
class EnrollmentRequestFactory extends Factory
{
    protected $model = EnrollmentRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'status' => EnrollmentRequest::PENDING,
        ];
    }
}
