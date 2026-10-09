<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Course $course): bool
    {
        return $user->isMemberOf($course);
    }

    public function create(User $user): bool
    {
        return true; // quien crea un curso queda como profesor del mismo
    }

    public function update(User $user, Course $course): bool
    {
        return $user->teachesCourse($course);
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->teachesCourse($course);
    }
}
