<?php

namespace App\Http\Requests;

use App\Models\Resource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación del alta de recursos (R04F04).
 * La autorización se comprueba en el controlador mediante ResourcePolicy.
 */
class ResourceRequest extends FormRequest
{
    /** Tamaño máximo de un archivo, en kilobytes (10 MB). */
    public const MAX_KB = 10240;

    /** Tipos de archivo admitidos (se comprueban por el contenido, no solo por el nombre). */
    public const MIMES = 'pdf,doc,docx,xls,xlsx,ppt,pptx,odt,ods,odp,txt,zip,png,jpg,jpeg';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in([Resource::TYPE_LINK, Resource::TYPE_FILE])],
            'url' => ['required_if:type,'.Resource::TYPE_LINK, 'nullable', 'string', 'max:255', 'url:http,https'],
            'file' => ['required_if:type,'.Resource::TYPE_FILE, 'nullable', 'file', 'max:'.self::MAX_KB, 'mimes:'.self::MIMES],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nombre', 'type' => 'tipo', 'url' => 'enlace', 'file' => 'archivo'];
    }
}
