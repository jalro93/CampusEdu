<?php

namespace App\Http\Controllers;

use App\Http\Requests\TopicRequest;
use App\Models\Course;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Temas de un curso (R04). Los temas se listan en courses.show, por eso no hay index ni show. */
class TopicController extends Controller
{
    public function create(Course $course): View
    {
        Gate::authorize('create', [Topic::class, $course]);

        return view('topics.create', compact('course'));
    }

    /** El tema nuevo se coloca al final de la lista. */
    public function store(TopicRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('create', [Topic::class, $course]);

        $course->topics()->create([
            'title' => $request->validated('title'),
            'order' => ($course->topics()->max('order') ?? -1) + 1,
        ]);

        return redirect()->route('courses.show', $course)->with('status', 'Tema creado correctamente.');
    }

    public function edit(Course $course, Topic $topic): View
    {
        Gate::authorize('update', $topic);

        return view('topics.edit', compact('course', 'topic'));
    }

    public function update(TopicRequest $request, Course $course, Topic $topic): RedirectResponse
    {
        Gate::authorize('update', $topic);

        $topic->update($request->validated());

        return redirect()->route('courses.show', $course)->with('status', 'Tema actualizado correctamente.');
    }

    public function destroy(Course $course, Topic $topic): RedirectResponse
    {
        Gate::authorize('delete', $topic);

        $topic->delete();

        return redirect()->route('courses.show', $course)->with('status', 'Tema eliminado correctamente.');
    }

    /** Sube o baja un tema intercambiándolo con su vecino y renumera el campo order (0..n-1). */
    public function move(Course $course, Topic $topic, string $direction): RedirectResponse
    {
        Gate::authorize('update', $topic);

        DB::transaction(function () use ($course, $topic, $direction) {
            $ids = $course->topics()->pluck('id')->all();
            $from = array_search($topic->id, $ids, true);
            $to = $direction === 'up' ? $from - 1 : $from + 1;

            if (isset($ids[$to])) {
                [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];
            }

            foreach ($ids as $position => $id) {
                Topic::whereKey($id)->update(['order' => $position]);
            }
        });

        return redirect()->route('courses.show', $course);
    }
}
