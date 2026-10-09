<?php

namespace App\Models\Concerns;

use App\Models\Course;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * R02: el rol (profesor | alumno) vive en la tabla pivote course_user,
 * de modo que un mismo usuario puede ser profesor en un curso y alumno en otro.
 */
trait HasCourseRoles
{
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class)->withPivot('role')->withTimestamps();
    }

    public function roleIn(Course $course): ?string
    {
        return $this->courses()->whereKey($course->getKey())->first()?->pivot->role;
    }

    public function teachesCourse(Course $course): bool
    {
        return $this->roleIn($course) === 'profesor';
    }

    public function isEnrolledIn(Course $course): bool
    {
        return $this->roleIn($course) === 'alumno';
    }

    public function isMemberOf(Course $course): bool
    {
        return $this->roleIn($course) !== null;
    }
}
