<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Topic;
use App\Models\User;

class TopicPolicy
{
    public function view(User $user, Topic $topic): bool
    {
        return $user->isMemberOf($topic->course);
    }

    public function create(User $user, Course $course): bool
    {
        return $user->teachesCourse($course);
    }

    public function update(User $user, Topic $topic): bool
    {
        return $user->teachesCourse($topic->course);
    }

    public function delete(User $user, Topic $topic): bool
    {
        return $user->teachesCourse($topic->course);
    }
}
