<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\EnrollmentRequest;
use App\Models\User;

/** Permisos de las solicitudes de matrícula: las crea quien aún no participa y las resuelve el profesor. */
class EnrollmentRequestPolicy
{
    public function create(User $user, Course $course): bool
    {
        return ! $user->isMemberOf($course);
    }

    /** Aprobar o rechazar: solo el profesor del curso y solo mientras la solicitud está pendiente. */
    public function decide(User $user, EnrollmentRequest $request): bool
    {
        return $request->isPending() && $user->teachesCourse($request->course);
    }
}
