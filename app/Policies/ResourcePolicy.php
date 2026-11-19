<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\Resource;
use App\Models\User;

/** Permisos de los recursos: los ven los miembros del curso y solo los gestiona su profesor. */
class ResourcePolicy
{
    public function view(User $user, Resource $resource): bool
    {
        return $user->isMemberOf($resource->lesson->topic->course);
    }

    public function create(User $user, Lesson $lesson): bool
    {
        return $user->teachesCourse($lesson->topic->course);
    }

    public function delete(User $user, Resource $resource): bool
    {
        return $user->teachesCourse($resource->lesson->topic->course);
    }
}
