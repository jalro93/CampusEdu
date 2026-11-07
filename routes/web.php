<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\ProfileController;
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

    // Temas anidados: con scoped bindings un tema solo se resuelve desde SU curso.
    Route::resource('courses.topics', TopicController::class)->except(['index', 'show'])->scoped();
    Route::patch('courses/{course}/topics/{topic}/move/{direction}', [TopicController::class, 'move'])
        ->whereIn('direction', ['up', 'down'])
        ->scopeBindings()
        ->name('courses.topics.move');
});

require __DIR__.'/auth.php';
