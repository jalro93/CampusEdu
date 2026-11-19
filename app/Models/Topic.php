<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'title', 'order'];

    /** Al borrar, se borran uno a uno los hijos para que también se eliminen los archivos de los recursos. */
    protected static function booted(): void
    {
        static::deleting(fn (Topic $topic) => $topic->lessons->each->delete());
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }
}
