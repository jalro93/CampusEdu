<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Catálogo de cursos a los que el usuario todavía no pertenece (R05F01). */
class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $courses = Course::whereDoesntHave('users', fn ($q) => $q->whereKey($user->id))
            ->with(['users' => fn ($q) => $q->wherePivot('role', 'profesor')])
            ->orderBy('title')
            ->paginate(12);

        // Estado de las solicitudes del usuario, por curso.
        $statuses = $user->enrollmentRequests()->pluck('status', 'course_id');

        return view('catalog.index', compact('courses', 'statuses'));
    }
}
