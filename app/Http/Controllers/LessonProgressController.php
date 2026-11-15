<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** Progreso del alumno en las lecciones (R06F01): marcar y desmarcar como completada. */
class LessonProgressController extends Controller
{
    /**
     * Marca la lección como completada. Es idempotente: si ya lo estaba no se duplica
     * el registro y se conserva la fecha original (INSERT ... IGNORE sobre la clave primaria).
     */
    public function store(Request $request, Course $course, Topic $topic, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('complete', $lesson);

        DB::table('lesson_progress')->insertOrIgnore([
            'user_id' => $request->user()->id,
            'lesson_id' => $lesson->id,
            'completed_at' => now(),
        ]);

        return redirect()->route('courses.topics.lessons.show', [$course, $topic, $lesson])
            ->with('status', 'Lección marcada como completada.');
    }

    public function destroy(Request $request, Course $course, Topic $topic, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('complete', $lesson);

        $request->user()->completedLessons()->detach($lesson->id);

        return redirect()->route('courses.topics.lessons.show', [$course, $topic, $lesson])
            ->with('status', 'Lección desmarcada.');
    }
}
