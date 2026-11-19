<?php

namespace App\Http\Requests;

use App\Support\VideoEmbed;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación común de alta y edición de lecciones (R04F02).
 * La autorización se comprueba en el controlador mediante LessonPolicy.
 */
class LessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:20000'],
            'video_url' => ['nullable', 'string', 'max:255', function (string $attribute, mixed $value, \Closure $fail) {
                if (VideoEmbed::embedUrl($value) === null) {
                    $fail('El enlace del vídeo debe ser una dirección https de YouTube o de Bunny.net.');
                }
            }],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'content' => 'contenido',
            'video_url' => 'enlace del vídeo',
        ];
    }
}
