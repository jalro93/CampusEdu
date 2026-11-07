<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class R03CursosTest extends TestCase
{
    use RefreshDatabase;

    private function cursoDe(User $user, string $rol): Course
    {
        $course = Course::factory()->create();
        $course->users()->attach($user, ['role' => $rol]);

        return $course;
    }

    // ---- R03F01: crear cursos ----

    public function test_R03F01T01P01_profesor_crea_un_curso_y_aparece_en_el_listado(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/courses', [
            'title' => 'Introducción a Laravel',
            'description' => 'Curso de prueba.',
        ]);

        $course = Course::where('title', 'Introducción a Laravel')->firstOrFail();
        $response->assertRedirect(route('courses.show', $course));
        $this->actingAs($user)->get('/courses')->assertOk()->assertSee('Introducción a Laravel');
    }

    public function test_R03F01T01P01_el_creador_queda_como_profesor_del_curso(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/courses', ['title' => 'Bases de datos']);

        $course = Course::where('title', 'Bases de datos')->firstOrFail();
        $this->assertTrue($user->teachesCourse($course));
    }

    public function test_R03F01T01P01_el_titulo_es_obligatorio(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/courses', ['title' => ''])->assertSessionHasErrors('title');
        $this->assertDatabaseCount('courses', 0);
    }

    public function test_R03F01T01P01_dos_cursos_con_el_mismo_titulo_tienen_slug_distinto(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/courses', ['title' => 'Programación']);
        $this->actingAs($user)->post('/courses', ['title' => 'Programación']);

        $this->assertSame(['programacion', 'programacion-2'], Course::orderBy('id')->pluck('slug')->all());
    }

    // ---- R03F02: modificar y eliminar cursos ----

    public function test_R03F02T01P01_profesor_edita_su_curso_y_se_guardan_los_cambios(): void
    {
        $user = User::factory()->create();
        $course = $this->cursoDe($user, 'profesor');

        $this->actingAs($user)
            ->patch("/courses/{$course->slug}", ['title' => 'Título nuevo', 'description' => 'Otra descripción'])
            ->assertRedirect(route('courses.show', $course));

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'title' => 'Título nuevo', 'slug' => $course->slug]);
    }

    public function test_R03F02T01P01_profesor_elimina_su_curso_y_desaparece_del_listado(): void
    {
        $user = User::factory()->create();
        $course = $this->cursoDe($user, 'profesor');
        $title = $course->title;

        $this->actingAs($user)->delete("/courses/{$course->slug}")->assertRedirect(route('courses.index'));

        $this->assertModelMissing($course);
        $this->actingAs($user)->get('/courses')->assertDontSee($title);
    }

    // ---- Autorización en servidor (R02F02): no basta con ocultar botones ----

    public function test_R02F02T02P01_alumno_no_puede_editar_ni_borrar_cambiando_la_url(): void
    {
        $user = User::factory()->create();
        $course = $this->cursoDe($user, 'alumno');

        $this->actingAs($user)->get("/courses/{$course->slug}")->assertOk();
        $this->actingAs($user)->get("/courses/{$course->slug}/edit")->assertForbidden();
        $this->actingAs($user)->patch("/courses/{$course->slug}", ['title' => 'Hackeado'])->assertForbidden();
        $this->actingAs($user)->delete("/courses/{$course->slug}")->assertForbidden();

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'title' => $course->title]);
    }

    public function test_R02F02T03P01_usuario_ajeno_no_ve_un_curso_en_el_que_no_participa(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $this->actingAs($user)->get("/courses/{$course->slug}")->assertForbidden();
        $this->actingAs($user)->get('/courses')->assertDontSee($course->title);
    }

    public function test_R01F02T01P01_invitado_es_redirigido_al_login(): void
    {
        $course = Course::factory()->create();

        $this->get('/courses')->assertRedirect('/login');
        $this->get("/courses/{$course->slug}")->assertRedirect('/login');
        $this->post('/courses', ['title' => 'X'])->assertRedirect('/login');
    }
}
