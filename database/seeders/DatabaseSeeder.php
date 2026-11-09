<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Datos de demostración para desarrollo (no se usa en producción).
 * Se puede ejecutar varias veces: no duplica usuarios ni el curso de ejemplo.
 *
 * Usuarios (contraseña de ambos: "password"):
 *   profesor@campusedu.test  → profesor del curso de ejemplo
 *   alumno@campusedu.test    → alumno del curso de ejemplo
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $profesor = $this->usuario('Laura Profesora', 'profesor@campusedu.test');
        $alumno = $this->usuario('Pablo Alumno', 'alumno@campusedu.test');

        if (Course::where('title', 'Introducción a Laravel')->exists()) {
            return;
        }

        $curso = Course::create([
            'title' => 'Introducción a Laravel',
            'slug' => Course::uniqueSlug('Introducción a Laravel'),
            'description' => 'Curso de ejemplo para probar CampusEdu: rutas, vistas Blade y base de datos con Eloquent.',
        ]);
        $curso->users()->attach($profesor, ['role' => 'profesor']);
        $curso->users()->attach($alumno, ['role' => 'alumno']);

        $primeros = Topic::create(['course_id' => $curso->id, 'title' => 'Primeros pasos', 'order' => 0]);
        $datos = Topic::create(['course_id' => $curso->id, 'title' => 'Base de datos', 'order' => 1]);

        $instalacion = Lesson::create([
            'topic_id' => $primeros->id,
            'title' => 'Instalación y estructura del proyecto',
            'content' => "# Instalación\n\nLaravel se instala con **Composer**. Las carpetas principales son:\n\n- `app/`: modelos, controladores y políticas.\n- `routes/`: definición de las rutas.\n- `resources/views/`: plantillas Blade.\n",
            'video_url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
            'order' => 0,
        ]);
        $instalacion->resources()->create(['name' => 'Documentación oficial de Laravel', 'type' => 'enlace', 'url' => 'https://laravel.com/docs']);

        Lesson::create([
            'topic_id' => $primeros->id,
            'title' => 'Rutas y controladores',
            'content' => "## Rutas\n\nUna ruta asocia una URL con un controlador.\n\n```php\nRoute::get('/cursos', [CourseController::class, 'index']);\n```\n",
            'order' => 1,
        ]);

        Lesson::create([
            'topic_id' => $datos->id,
            'title' => 'Migraciones y Eloquent',
            'content' => "Las **migraciones** versionan la base de datos y *Eloquent* permite consultarla con objetos PHP.\n",
            'order' => 0,
        ]);
    }

    private function usuario(string $nombre, string $email): User
    {
        $usuario = User::firstOrNew(['email' => $email]);

        if (! $usuario->exists) {
            $usuario->forceFill(['name' => $nombre, 'password' => 'password', 'email_verified_at' => now()])->save();
        }

        return $usuario;
    }
}
