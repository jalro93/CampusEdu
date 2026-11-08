<?php

namespace App\Http\Requests;

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
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'content' => 'contenido',
        ];
    }
}
