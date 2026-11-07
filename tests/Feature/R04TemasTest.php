<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class R04TemasTest extends TestCase
{
    use RefreshDatabase;

    private function cursoDe(User $user, string $rol): Course
    {
        $course = Course::factory()->create();
        $course->users()->attach($user, ['role' => $rol]);

        return $course;
    }

    public function test_R04F01T01P01_profesor_crea_un_tema_y_queda_asociado_al_curso(): void
    {
        $user = User::factory()->create();
        $course = $this->cursoDe($user, 'profesor');

        $this->actingAs($user)
            ->post("/courses/{$course->slug}/topics", ['title' => 'Introducción'])
            ->assertRedirect(route('courses.show', $course));

        $this->assertDatabaseHas('topics', ['course_id' => $course->id, 'title' => 'Introducción']);
        $this->assertSame(1, $course->topics()->count());
    }

    public function test_R04F01T01P02_los_temas_nuevos_se_colocan_al_final(): void
    {
        $user = User::factory()->create();
        $course = $this->cursoDe($user, 'profesor');

        $this->actingAs($user)->post("/courses/{$course->slug}/topics", ['title' => 'Primero']);
        $this->actingAs($user)->post("/courses/{$course->slug}/topics", ['title' => 'Segundo']);

        $this->assertSame(['Primero', 'Segundo'], $course->topics()->pluck('title')->all());
        $this->assertSame([0, 1], $course->topics()->pluck('order')->all());
    }

    public function test_R04F01T02P01_el_titulo_es_obligatorio_y_maximo_255_caracteres(): void
    {
        $user = User::factory()->create();
        $course = $this->cursoDe($user, 'profesor');

        $this->actingAs($user)->post("/courses/{$course->slug}/topics", ['title' => ''])->assertSessionHasErrors('title');
        $this->actingAs($user)->post("/courses/{$course->slug}/topics", ['title' => str_repeat('a', 256)])->assertSessionHasErrors('title');
        $this->actingAs($user)->post("/courses/{$course->slug}/topics", ['title' => str_repeat('a', 255)])->assertSessionDoesntHaveErrors();

        $this->assertSame(1, Topic::count());
    }

    public function test_R04F01T03P01_profesor_edita_un_tema(): void
    {
        $user = User::factory()->create();
        $course = $this->cursoDe($user, 'profesor');
        $topic = Topic::factory()->create(['course_id' => $course->id]);

        $this->actingAs($user)
            ->patch("/courses/{$course->slug}/topics/{$topic->id}", ['title' => 'Título nuevo'])
            ->assertRedirect(route('courses.show', $course));

        $this->assertDatabaseHas('topics', ['id' => $topic->id, 'title' => 'Título nuevo']);
    }

    public function test_R04F01T04P01_profesor_elimina_un_tema(): void
    {
        $user = User::factory()->create();
        $course = $this->cursoDe($user, 'profesor');
        $topic = Topic::factory()->create(['course_id' => $course->id]);

        $this->actingAs($user)->delete("/courses/{$course->slug}/topics/{$topic->id}")->assertRedirect(route('courses.show', $course));

        $this->assertModelMissing($topic);
    }

    public function test_R04F01T05P01_profesor_reordena_los_temas_con_subir_y_bajar(): void
    {
        $user = User::factory()->create();
        $course = $this->cursoDe($user, 'profesor');
        $a = Topic::factory()->create(['course_id' => $course->id, 'title' => 'A', 'order' => 0]);
        $b = Topic::factory()->create(['course_id' => $course->id, 'title' => 'B', 'order' => 1]);
        $c = Topic::factory()->create(['course_id' => $course->id, 'title' => 'C', 'order' => 2]);

        $this->actingAs($user)->patch("/courses/{$course->slug}/topics/{$c->id}/move/up");
        $this->assertSame(['A', 'C', 'B'], $course->topics()->pluck('title')->all());

        $this->actingAs($user)->patch("/courses/{$course->slug}/topics/{$a->id}/move/down");
        $this->assertSame(['C', 'A', 'B'], $course->topics()->pluck('title')->all());

        // En los extremos no cambia nada.
        $this->actingAs($user)->patch("/courses/{$course->slug}/topics/{$c->id}/move/up");
        $this->assertSame(['C', 'A', 'B'], $course->topics()->pluck('title')->all());
        $this->assertSame([0, 1, 2], $course->topics()->pluck('order')->all());
    }

    public function test_R04F01T06P01_courses_show_lista_los_temas_en_orden(): void
    {
        $user = User::factory()->create();
        $course = $this->cursoDe($user, 'alumno');
        Topic::factory()->create(['course_id' => $course->id, 'title' => 'Tema Tercero', 'order' => 2]);
        Topic::factory()->create(['course_id' => $course->id, 'title' => 'Tema Primero', 'order' => 0]);
        Topic::factory()->create(['course_id' => $course->id, 'title' => 'Tema Segundo', 'order' => 1]);

        $this->actingAs($user)->get("/courses/{$course->slug}")
            ->assertOk()
            ->assertSeeInOrder(['Tema Primero', 'Tema Segundo', 'Tema Tercero']);
    }

    public function test_R04F01T07P01_alumno_no_puede_crear_editar_borrar_ni_reordenar(): void
    {
        $user = User::factory()->create();
        $course = $this->cursoDe($user, 'alumno');
        $topic = Topic::factory()->create(['course_id' => $course->id, 'title' => 'Original']);
        $other = Topic::factory()->create(['course_id' => $course->id, 'order' => 1]);

        $this->actingAs($user)->get("/courses/{$course->slug}/topics/create")->assertForbidden();
        $this->actingAs($user)->post("/courses/{$course->slug}/topics", ['title' => 'Nuevo'])->assertForbidden();
        $this->actingAs($user)->get("/courses/{$course->slug}/topics/{$topic->id}/edit")->assertForbidden();
        $this->actingAs($user)->patch("/courses/{$course->slug}/topics/{$topic->id}", ['title' => 'Hackeado'])->assertForbidden();
        $this->actingAs($user)->delete("/courses/{$course->slug}/topics/{$topic->id}")->assertForbidden();
        $this->actingAs($user)->patch("/courses/{$course->slug}/topics/{$topic->id}/move/down")->assertForbidden();

        $this->assertSame(2, Topic::count());
        $this->assertDatabaseHas('topics', ['id' => $topic->id, 'title' => 'Original', 'order' => 0]);
        $this->assertDatabaseHas('topics', ['id' => $other->id, 'order' => 1]);
    }

    public function test_R04F01T07P02_profesor_de_otro_curso_no_puede_gestionar_temas_ajenos(): void
    {
        $user = User::factory()->create();
        $this->cursoDe($user, 'profesor');
        $foreign = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $foreign->id, 'title' => 'Ajeno']);

        $this->actingAs($user)->post("/courses/{$foreign->slug}/topics", ['title' => 'Intruso'])->assertForbidden();
        $this->actingAs($user)->patch("/courses/{$foreign->slug}/topics/{$topic->id}", ['title' => 'Hackeado'])->assertForbidden();

        $this->assertDatabaseHas('topics', ['id' => $topic->id, 'title' => 'Ajeno']);
        $this->assertDatabaseMissing('topics', ['title' => 'Intruso']);
    }

    public function test_R04F01T08P01_un_tema_no_se_abre_desde_la_url_de_otro_curso(): void
    {
        $user = User::factory()->create();
        $mine = $this->cursoDe($user, 'profesor');
        $other = $this->cursoDe($user, 'profesor');
        $topic = Topic::factory()->create(['course_id' => $other->id, 'title' => 'Del otro curso']);

        $this->actingAs($user)->get("/courses/{$mine->slug}/topics/{$topic->id}/edit")->assertNotFound();
        $this->actingAs($user)->patch("/courses/{$mine->slug}/topics/{$topic->id}", ['title' => 'Cambiado'])->assertNotFound();
        $this->actingAs($user)->delete("/courses/{$mine->slug}/topics/{$topic->id}")->assertNotFound();
        $this->actingAs($user)->patch("/courses/{$mine->slug}/topics/{$topic->id}/move/up")->assertNotFound();

        $this->assertDatabaseHas('topics', ['id' => $topic->id, 'title' => 'Del otro curso', 'course_id' => $other->id]);
    }

    public function test_R04F01T09P01_invitado_es_redirigido_al_login(): void
    {
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);

        $this->get("/courses/{$course->slug}/topics/create")->assertRedirect('/login');
        $this->post("/courses/{$course->slug}/topics", ['title' => 'X'])->assertRedirect('/login');
        $this->get("/courses/{$course->slug}/topics/{$topic->id}/edit")->assertRedirect('/login');
        $this->patch("/courses/{$course->slug}/topics/{$topic->id}", ['title' => 'X'])->assertRedirect('/login');
        $this->delete("/courses/{$course->slug}/topics/{$topic->id}")->assertRedirect('/login');
        $this->patch("/courses/{$course->slug}/topics/{$topic->id}/move/up")->assertRedirect('/login');
    }

    public function test_R04F01T10P01_alumno_ve_los_temas_pero_no_los_botones_de_gestion(): void
    {
        $student = User::factory()->create();
        $teacher = User::factory()->create();
        $course = $this->cursoDe($student, 'alumno');
        $course->users()->attach($teacher, ['role' => 'profesor']);
        Topic::factory()->create(['course_id' => $course->id, 'title' => 'Visible']);

        $this->actingAs($student)->get("/courses/{$course->slug}")->assertSee('Visible')->assertDontSee('Nuevo tema');
        $this->actingAs($teacher)->get("/courses/{$course->slug}")->assertSee('Visible')->assertSee('Nuevo tema');
    }
}
