<?php

namespace App\Http\Controllers;

use App\Http\Requests\CourseRequest;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CourseController extends Controller
{
    /** Cursos en los que el usuario participa, con su rol en cada uno. */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Course::class);

        $courses = $request->user()->courses()->orderBy('title')->get();

        return view('courses.index', compact('courses'));
    }

    public function create(): View
    {
        Gate::authorize('create', Course::class);

        return view('courses.create');
    }

    /** Crea el curso y registra a su creador como profesor del mismo. */
    public function store(CourseRequest $request): RedirectResponse
    {
        Gate::authorize('create', Course::class);

        $course = DB::transaction(function () use ($request) {
            $course = Course::create([
                ...$request->validated(),
                'slug' => Course::uniqueSlug($request->validated('title')),
            ]);
            $course->users()->attach($request->user(), ['role' => 'profesor']);

            return $course;
        });

        return redirect()->route('courses.show', $course)->with('status', 'Curso creado correctamente.');
    }

    public function show(Request $request, Course $course): View
    {
        Gate::authorize('view', $course);

        $role = $request->user()->roleIn($course);
        $teachers = $course->users()->wherePivot('role', 'profesor')->orderBy('name')->get();

        return view('courses.show', compact('course', 'role', 'teachers'));
    }

    public function edit(Course $course): View
    {
        Gate::authorize('update', $course);

        return view('courses.edit', compact('course'));
    }

    /** El slug no cambia al editar para no romper los enlaces existentes. */
    public function update(CourseRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        $course->update($request->validated());

        return redirect()->route('courses.show', $course)->with('status', 'Curso actualizado correctamente.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        Gate::authorize('delete', $course);

        $course->delete();

        return redirect()->route('courses.index')->with('status', 'Curso eliminado correctamente.');
    }
}
