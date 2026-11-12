<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\EnrollmentRequestController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\TopicController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('courses', CourseController::class);

    // Matriculación (R05): catálogo, solicitud del alumno y decisión del profesor.
    Route::get('catalog', [CatalogController::class, 'index'])->name('catalog.index');
    Route::post('courses/{course}/enrollment-requests', [EnrollmentRequestController::class, 'store'])
        ->name('courses.enrollment-requests.store');
    Route::patch('courses/{course}/enrollment-requests/{enrollmentRequest}/approve', [EnrollmentRequestController::class, 'approve'])
        ->scopeBindings()->name('courses.enrollment-requests.approve');
    Route::patch('courses/{course}/enrollment-requests/{enrollmentRequest}/reject', [EnrollmentRequestController::class, 'reject'])
        ->scopeBindings()->name('courses.enrollment-requests.reject');

    // Temas anidados: con scoped bindings un tema solo se resuelve desde SU curso.
    Route::resource('courses.topics', TopicController::class)->except(['index', 'show'])->scoped();
    Route::patch('courses/{course}/topics/{topic}/move/{direction}', [TopicController::class, 'move'])
        ->whereIn('direction', ['up', 'down'])
        ->scopeBindings()
        ->name('courses.topics.move');

    // Lecciones anidadas en el tema: cada lección solo se resuelve desde SU tema y SU curso.
    Route::resource('courses.topics.lessons', LessonController::class)->except(['index'])->scoped();

    // Recursos de la lección: alta, descarga (autorizada) y borrado.
    Route::resource('courses.topics.lessons.resources', ResourceController::class)->only(['store', 'show', 'destroy'])->scoped();
});

require __DIR__.'/auth.php';
