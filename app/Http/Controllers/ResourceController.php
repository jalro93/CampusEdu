<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResourceRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Resource;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Recursos de una lección (R04F04): enlaces externos y archivos descargables. */
class ResourceController extends Controller
{
    public function store(ResourceRequest $request, Course $course, Topic $topic, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('create', [Resource::class, $lesson]);

        $isFile = $request->input('type') === Resource::TYPE_FILE;

        $lesson->resources()->create([
            'name' => $request->input('name'),
            'type' => $request->input('type'),
            // El archivo va al disco privado; del enlace solo se guarda la dirección.
            'url' => $isFile
                ? $request->file('file')->store("resources/{$lesson->id}", Resource::DISK)
                : $request->input('url'),
        ]);

        return redirect()->route('courses.topics.lessons.show', [$course, $topic, $lesson])
            ->with('status', 'Recurso añadido correctamente.');
    }

    /** Descarga de un archivo: solo para miembros del curso y siempre como adjunto. */
    public function show(Course $course, Topic $topic, Lesson $lesson, Resource $resource): StreamedResponse
    {
        Gate::authorize('view', $resource);

        abort_unless($resource->isFile() && Storage::disk(Resource::DISK)->exists($resource->url), 404);

        $extension = pathinfo($resource->url, PATHINFO_EXTENSION);
        $filename = (Str::slug($resource->name) ?: 'recurso').($extension ? ".{$extension}" : '');

        return Storage::disk(Resource::DISK)->download($resource->url, $filename);
    }

    public function destroy(Course $course, Topic $topic, Lesson $lesson, Resource $resource): RedirectResponse
    {
        Gate::authorize('delete', $resource);

        $resource->delete();

        return redirect()->route('courses.topics.lessons.show', [$course, $topic, $lesson])
            ->with('status', 'Recurso eliminado correctamente.');
    }
}
