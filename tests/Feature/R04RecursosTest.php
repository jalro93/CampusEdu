<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Resource;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** R04F04: recursos de una lección (enlaces externos y archivos descargables). */
class R04RecursosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(Resource::DISK);
    }

    private function leccionDe(User $user, string $rol): Lesson
    {
        $course = Course::factory()->create();
        $course->users()->attach($user, ['role' => $rol]);
        $topic = Topic::factory()->create(['course_id' => $course->id]);

        return Lesson::factory()->create(['topic_id' => $topic->id]);
    }

    private function base(Lesson $lesson): string
    {
        return "/courses/{$lesson->topic->course->slug}/topics/{$lesson->topic_id}/lessons/{$lesson->id}";
    }

    private function subirPdf(User $user, Lesson $lesson, string $nombre = 'Apuntes'): Resource
    {
        $this->actingAs($user)->post($this->base($lesson).'/resources', [
            'name' => $nombre,
            'type' => 'archivo',
            'file' => UploadedFile::fake()->create('apuntes.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        return Resource::where('lesson_id', $lesson->id)->firstOrFail();
    }

    public function test_R04F04T01P01_el_profesor_anade_un_enlace_y_el_alumno_lo_ve_con_rel_seguro(): void
    {
        $profesor = User::factory()->create();
        $lesson = $this->leccionDe($profesor, 'profesor');
        $alumno = User::factory()->create();
        $lesson->topic->course->users()->attach($alumno, ['role' => 'alumno']);

        $this->actingAs($profesor)->post($this->base($lesson).'/resources', [
            'name' => 'Documentación oficial', 'type' => 'enlace', 'url' => 'https://laravel.com/docs',
        ])->assertRedirect($this->base($lesson));

        $this->assertDatabaseHas('resources', ['lesson_id' => $lesson->id, 'type' => 'enlace', 'url' => 'https://laravel.com/docs']);
        $this->actingAs($alumno)->get($this->base($lesson))
            ->assertOk()
            ->assertSee('Documentación oficial')
            ->assertSee('rel="noopener noreferrer"', false);
    }

    public function test_R04F04T01P01_un_enlace_solo_admite_http_o_https(): void
    {
        $profesor = User::factory()->create();
        $lesson = $this->leccionDe($profesor, 'profesor');

        foreach (['javascript:alert(1)', 'ftp://example.com/a', 'data:text/html;base64,PHNjcmlwdD4=', 'no-es-una-url', ''] as $url) {
            $this->actingAs($profesor)->post($this->base($lesson).'/resources', [
                'name' => 'Malo', 'type' => 'enlace', 'url' => $url,
            ])->assertSessionHasErrors('url');
        }

        $this->assertSame(0, Resource::count());
    }

    public function test_R04F04T01P01_un_archivo_se_guarda_en_el_disco_privado(): void
    {
        $profesor = User::factory()->create();
        $lesson = $this->leccionDe($profesor, 'profesor');

        $resource = $this->subirPdf($profesor, $lesson);

        $this->assertSame('archivo', $resource->type);
        $this->assertStringStartsWith("resources/{$lesson->id}/", $resource->url);
        Storage::disk(Resource::DISK)->assertExists($resource->url);
        Storage::disk('public')->assertMissing($resource->url);
    }

    public function test_R04F04T01P01_se_rechazan_archivos_de_tipo_no_permitido_o_demasiado_grandes(): void
    {
        $profesor = User::factory()->create();
        $lesson = $this->leccionDe($profesor, 'profesor');

        foreach ([
            UploadedFile::fake()->create('script.php', 1, 'text/x-php'),
            UploadedFile::fake()->create('programa.exe', 1, 'application/x-msdownload'),
            UploadedFile::fake()->create('pagina.html', 1, 'text/html'),
            UploadedFile::fake()->create('enorme.pdf', 10241, 'application/pdf'),
        ] as $file) {
            $this->actingAs($profesor)->post($this->base($lesson).'/resources', [
                'name' => 'Malo', 'type' => 'archivo', 'file' => $file,
            ])->assertSessionHasErrors('file');
        }

        $this->assertSame(0, Resource::count());
        $this->assertSame([], Storage::disk(Resource::DISK)->allFiles());
    }

    public function test_R04F04T01P01_el_nombre_y_el_contenido_del_tipo_elegido_son_obligatorios(): void
    {
        $profesor = User::factory()->create();
        $lesson = $this->leccionDe($profesor, 'profesor');
        $url = $this->base($lesson).'/resources';

        $this->actingAs($profesor)->post($url, ['type' => 'enlace', 'url' => 'https://example.com'])->assertSessionHasErrors('name');
        $this->actingAs($profesor)->post($url, ['name' => 'Sin archivo', 'type' => 'archivo'])->assertSessionHasErrors('file');
        $this->actingAs($profesor)->post($url, ['name' => 'Tipo raro', 'type' => 'otro'])->assertSessionHasErrors('type');
        $this->assertSame(0, Resource::count());
    }

    public function test_R04F04T01P01_un_miembro_descarga_el_archivo_como_adjunto(): void
    {
        $profesor = User::factory()->create();
        $lesson = $this->leccionDe($profesor, 'profesor');
        $alumno = User::factory()->create();
        $lesson->topic->course->users()->attach($alumno, ['role' => 'alumno']);
        $resource = $this->subirPdf($profesor, $lesson, 'Apuntes tema 1');

        $respuesta = $this->actingAs($alumno)->get("{$this->base($lesson)}/resources/{$resource->id}");

        $respuesta->assertOk();
        $this->assertStringContainsString('attachment', $respuesta->headers->get('content-disposition'));
        $this->assertStringContainsString('apuntes-tema-1.pdf', $respuesta->headers->get('content-disposition'));
    }

    public function test_R04F04T01P01_quien_no_es_miembro_no_puede_descargar_ni_gestionar_recursos(): void
    {
        $profesor = User::factory()->create();
        $lesson = $this->leccionDe($profesor, 'profesor');
        $resource = $this->subirPdf($profesor, $lesson);
        $ajeno = User::factory()->create();
        $url = "{$this->base($lesson)}/resources";

        $this->actingAs($ajeno)->get("{$url}/{$resource->id}")->assertForbidden();
        $this->actingAs($ajeno)->post($url, ['name' => 'X', 'type' => 'enlace', 'url' => 'https://example.com'])->assertForbidden();
        $this->actingAs($ajeno)->delete("{$url}/{$resource->id}")->assertForbidden();
        $this->assertSame(1, Resource::count());
    }

    public function test_R04F04T01P01_el_alumno_ve_los_recursos_pero_no_puede_crearlos_ni_borrarlos(): void
    {
        $profesor = User::factory()->create();
        $lesson = $this->leccionDe($profesor, 'profesor');
        $resource = $this->subirPdf($profesor, $lesson, 'Apuntes');
        $alumno = User::factory()->create();
        $lesson->topic->course->users()->attach($alumno, ['role' => 'alumno']);
        $url = "{$this->base($lesson)}/resources";

        $this->actingAs($alumno)->get($this->base($lesson))->assertOk()->assertSee('Apuntes')->assertDontSee('Añadir recurso');
        $this->actingAs($alumno)->post($url, ['name' => 'X', 'type' => 'enlace', 'url' => 'https://example.com'])->assertForbidden();
        $this->actingAs($alumno)->delete("{$url}/{$resource->id}")->assertForbidden();
        $this->assertSame(1, Resource::count());
    }

    public function test_R04F04T01P01_un_recurso_no_se_resuelve_desde_otra_leccion(): void
    {
        $profesor = User::factory()->create();
        $lesson = $this->leccionDe($profesor, 'profesor');
        $otra = Lesson::factory()->create(['topic_id' => $lesson->topic_id]);
        $resource = $this->subirPdf($profesor, $lesson);

        $this->actingAs($profesor)->get("{$this->base($otra)}/resources/{$resource->id}")->assertNotFound();
        $this->actingAs($profesor)->delete("{$this->base($otra)}/resources/{$resource->id}")->assertNotFound();
        $this->assertSame(1, Resource::count());
    }

    public function test_R04F04T01P01_borrar_un_recurso_elimina_tambien_su_archivo(): void
    {
        $profesor = User::factory()->create();
        $lesson = $this->leccionDe($profesor, 'profesor');
        $resource = $this->subirPdf($profesor, $lesson);
        $ruta = $resource->url;

        $this->actingAs($profesor)->delete("{$this->base($lesson)}/resources/{$resource->id}")->assertRedirect($this->base($lesson));

        $this->assertModelMissing($resource);
        Storage::disk(Resource::DISK)->assertMissing($ruta);
    }

    public function test_R04F04T01P01_borrar_la_leccion_el_tema_o_el_curso_elimina_los_archivos(): void
    {
        $profesor = User::factory()->create();

        $a = $this->leccionDe($profesor, 'profesor');
        $rutaA = $this->subirPdf($profesor, $a)->url;
        $a->delete();
        Storage::disk(Resource::DISK)->assertMissing($rutaA);

        $b = $this->leccionDe($profesor, 'profesor');
        $rutaB = $this->subirPdf($profesor, $b)->url;
        $b->topic->delete();
        Storage::disk(Resource::DISK)->assertMissing($rutaB);

        $c = $this->leccionDe($profesor, 'profesor');
        $rutaC = $this->subirPdf($profesor, $c)->url;
        $c->topic->course->delete();
        Storage::disk(Resource::DISK)->assertMissing($rutaC);

        $this->assertSame(0, Resource::count());
    }
}
