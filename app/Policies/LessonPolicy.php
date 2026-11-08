<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\Topic;
use App\Models\User;

/** Permisos de las lecciones: las ven los miembros del curso y solo las gestiona su profesor. */
class LessonPolicy
{
    public function view(User $user, Lesson $lesson): bool
    {
        return $user->isMemberOf($lesson->topic->course);
    }

    public function create(User $user, Topic $topic): bool
    {
        return $user->teachesCourse($topic->course);
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $user->teachesCourse($lesson->topic->course);
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return $user->teachesCourse($lesson->topic->course);
    }
}
