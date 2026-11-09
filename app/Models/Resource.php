<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Recurso de una lección (R04F04): un enlace externo o un archivo subido.
 * En los archivos, `url` guarda la ruta dentro del disco privado, nunca una URL pública.
 */
class Resource extends Model
{
    use HasFactory;

    public const TYPE_FILE = 'archivo';

    public const TYPE_LINK = 'enlace';

    /** Disco donde se guardan los archivos: privado, solo accesible a través del controlador. */
    public const DISK = 'local';

    protected $fillable = ['lesson_id', 'name', 'type', 'url'];

    protected static function booted(): void
    {
        // Al borrar un recurso se elimina también su archivo del disco.
        static::deleting(function (Resource $resource) {
            if ($resource->isFile()) {
                Storage::disk(self::DISK)->delete($resource->url);
            }
        });
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function isFile(): bool
    {
        return $this->type === self::TYPE_FILE;
    }

    public function isLink(): bool
    {
        return $this->type === self::TYPE_LINK;
    }
}
