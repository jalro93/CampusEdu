<?php

namespace App\Models;

use App\Support\Progress;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Course extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'description'];

    /** Al borrar, se borran uno a uno los hijos para que también se eliminen los archivos de los recursos. */
    protected static function booted(): void
    {
        static::deleting(fn (Course $course) => $course->topics->each->delete());
    }

    /** Las rutas usan el slug (único) en lugar del identificador numérico. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Genera un slug a partir del título y, si ya existe, añade un sufijo numérico. */
    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'curso';
        $slug = $base;
        $n = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    /** Todas las lecciones del curso, a través de sus temas. */
    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, Topic::class);
    }

    /**
     * Progreso de un usuario en este curso (R06F02).
     *
     * @return array{completed: int, total: int, percent: int}
     */
    public function progressFor(User $user): array
    {
        $total = $this->lessons()->count();
        $completed = $user->completedLessons()
            ->whereHas('topic', fn ($q) => $q->where('course_id', $this->id))
            ->count();

        return ['completed' => $completed, 'total' => $total, 'percent' => Progress::percent($completed, $total)];
    }

    public function enrollmentRequests(): HasMany
    {
        return $this->hasMany(EnrollmentRequest::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class)->orderBy('order')->orderBy('id');
    }
}
