<?php

namespace App\Models\Concerns;

use App\Models\Course;
use App\Models\EnrollmentRequest;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /** Lecciones que el usuario ha marcado como completadas (R06), con la fecha en el pivote. */
    public function completedLessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'lesson_progress')->withPivot('completed_at');
    }

    /** Solicitudes de matrícula que ha hecho el usuario (R05). */
    public function enrollmentRequests(): HasMany
    {
        return $this->hasMany(EnrollmentRequest::class);
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
