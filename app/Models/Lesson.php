<?php

namespace App\Models;

use App\Support\VideoEmbed;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = ['topic_id', 'title', 'content', 'video_url', 'order'];

    /** Al borrar, se borran uno a uno los hijos para que también se eliminen los archivos de los recursos. */
    protected static function booted(): void
    {
        static::deleting(fn (Lesson $lesson) => $lesson->resources->each->delete());
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    public function completedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'lesson_progress')->withPivot('completed_at');
    }

    /**
     * Contenido de la lección en HTML seguro (R04F02).
     * El texto se guarda como Markdown. Al mostrarlo se descarta cualquier HTML
     * escrito a mano y se bloquean los enlaces peligrosos (javascript:, etc.),
     * de modo que el contenido no puede ejecutar código en el navegador.
     */
    public function renderedContent(): HtmlString
    {
        return new HtmlString(Str::markdown($this->content ?? '', [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]));
    }

    /** URL de incrustación del vídeo (YouTube o Bunny.net), o null si no hay vídeo válido. */
    public function videoEmbedUrl(): ?string
    {
        return VideoEmbed::embedUrl($this->video_url);
    }
}
