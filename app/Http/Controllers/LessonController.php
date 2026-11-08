<?php

namespace App\Http\Controllers;

use App\Http\Requests\LessonRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Lecciones de un tema (R04F02). Se listan en courses.show, por eso no hay index. */
class LessonController extends Controller
{
    public function create(Course $course, Topic $topic): View
    {
        Gate::authorize('create', [Lesson::class, $topic]);

        return view('lessons.create', compact('course', 'topic'));
    }

    /** La lección nueva se coloca al final del tema. */
    public function store(LessonRequest $request, Course $course, Topic $topic): RedirectResponse
    {
        Gate::authorize('create', [Lesson::class, $topic]);

        $lesson = $topic->lessons()->create([
            ...$request->validated(),
            'order' => ($topic->lessons()->max('order') ?? -1) + 1,
        ]);

        return redirect()->route('courses.topics.lessons.show', [$course, $topic, $lesson])
            ->with('status', 'Lección creada correctamente.');
    }

    public function show(Course $course, Topic $topic, Lesson $lesson): View
    {
        Gate::authorize('view', $lesson);

        return view('lessons.show', compact('course', 'topic', 'lesson'));
    }

    public function edit(Course $course, Topic $topic, Lesson $lesson): View
    {
        Gate::authorize('update', $lesson);

        return view('lessons.edit', compact('course', 'topic', 'lesson'));
    }

    public function update(LessonRequest $request, Course $course, Topic $topic, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('update', $lesson);

        $lesson->update($request->validated());

        return redirect()->route('courses.topics.lessons.show', [$course, $topic, $lesson])
            ->with('status', 'Lección actualizada correctamente.');
    }

    public function destroy(Course $course, Topic $topic, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('delete', $lesson);

        $lesson->delete();

        return redirect()->route('courses.show', $course)->with('status', 'Lección eliminada correctamente.');
    }
}
