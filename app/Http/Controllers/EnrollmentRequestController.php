<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\EnrollmentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** Solicitud de matrícula del alumno y decisión del profesor (R05F01). */
class EnrollmentRequestController extends Controller
{
    /** Crea la solicitud; si estaba rechazada, vuelve a quedar pendiente. Solicitar dos veces no duplica. */
    public function store(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('create', [EnrollmentRequest::class, $course]);

        EnrollmentRequest::updateOrCreate(
            ['user_id' => $request->user()->id, 'course_id' => $course->id],
            ['status' => EnrollmentRequest::PENDING],
        );

        return redirect()->route('catalog.index')->with('status', 'Solicitud enviada. El profesor del curso deberá aprobarla.');
    }

    /** Aprobar matricula al solicitante como alumno del curso. */
    public function approve(Course $course, EnrollmentRequest $enrollmentRequest): RedirectResponse
    {
        Gate::authorize('decide', $enrollmentRequest);

        DB::transaction(function () use ($course, $enrollmentRequest) {
            $course->users()->syncWithoutDetaching([$enrollmentRequest->user_id => ['role' => 'alumno']]);
            $enrollmentRequest->update(['status' => EnrollmentRequest::APPROVED]);
        });

        return redirect()->route('courses.show', $course)->with('status', 'Solicitud aprobada: el alumno ya forma parte del curso.');
    }

    public function reject(Course $course, EnrollmentRequest $enrollmentRequest): RedirectResponse
    {
        Gate::authorize('decide', $enrollmentRequest);

        $enrollmentRequest->update(['status' => EnrollmentRequest::REJECTED]);

        return redirect()->route('courses.show', $course)->with('status', 'Solicitud rechazada.');
    }
}
