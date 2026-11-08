<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** R04F02: lecciones de un tema, con texto formateado (Markdown) y saneado al mostrarlo. */
class R04LeccionesTest extends TestCase
{
    use RefreshDatabase;

    private function temaDe(User $user, string $rol): Topic
    {
        $course = Course::factory()->create();
        $course->users()->attach($user, ['role' => $rol]);

        return Topic::factory()->create(['course_id' => $course->id]);
    }

    private function base(Topic $topic): string
    {
        return "/courses/{$topic->course->slug}/topics/{$topic->id}/lessons";
    }

    public function test_R04F02T01P01_profesor_crea_una_leccion_con_texto_formateado_y_se_conserva(): void
    {
        $user = User::factory()->create();
        $topic = $this->temaDe($user, 'profesor');
        $markdown = "## Variables\n\nUna variable es **un nombre** que guarda un valor.\n\n- Primero\n- Segundo";

        $this->actingAs($user)
            ->post($this->base($topic), ['title' => 'Introducción', 'content' => $markdown])
            ->assertRedirect();

        $lesson = Lesson::where('topic_id', $topic->id)->firstOrFail();
        $this->assertSame($markdown, $lesson->content);

        $this->actingAs($user)->get("{$this->base($topic)}/{$lesson->id}")
            ->assertOk()
            ->assertSee('<h2>Variables</h2>', false)
            ->assertSee('<strong>un nombre</strong>', false)
            ->assertSee('<li>Segundo</li>', false);
    }

    public function test_R04F02T01P01_las_lecciones_nuevas_se_colocan_al_final_del_tema(): void
    {
        $user = User::factory()->create();
        $topic = $this->temaDe($user, 'profesor');

        $this->actingAs($user)->post($this->base($topic), ['title' => 'Primera']);
        $this->actingAs($user)->post($this->base($topic), ['title' => 'Segunda']);

        $this->assertSame(['Primera', 'Segunda'], $topic->lessons()->pluck('title')->all());
        $this->assertSame([0, 1], $topic->lessons()->pluck('order')->all());
    }

    public function test_R04F02T01P01_el_titulo_es_obligatorio_y_el_contenido_tiene_limite(): void
    {
        $user = User::factory()->create();
        $topic = $this->temaDe($user, 'profesor');

        $this->actingAs($user)->post($this->base($topic), ['title' => ''])->assertSessionHasErrors('title');
        $this->actingAs($user)->post($this->base($topic), ['title' => str_repeat('a', 256)])->assertSessionHasErrors('title');
        $this->actingAs($user)->post($this->base($topic), ['title' => 'Larga', 'content' => str_repeat('a', 20001)])->assertSessionHasErrors('content');
        $this->assertDatabaseCount('lessons', 0);
    }

    public function test_R04F02T01P01_profesor_edita_una_leccion(): void
    {
        $user = User::factory()->create();
        $topic = $this->temaDe($user, 'profesor');
        $lesson = Lesson::factory()->create(['topic_id' => $topic->id]);

        $this->actingAs($user)
            ->patch("{$this->base($topic)}/{$lesson->id}", ['title' => 'Título nuevo', 'content' => 'Texto **nuevo**'])
            ->assertRedirect();

        $this->assertDatabaseHas('lessons', ['id' => $lesson->id, 'title' => 'Título nuevo', 'content' => 'Texto **nuevo**']);
    }

    public function test_R04F02T01P01_profesor_elimina_una_leccion(): void
    {
        $user = User::factory()->create();
        $topic = $this->temaDe($user, 'profesor');
        $lesson = Lesson::factory()->create(['topic_id' => $topic->id]);

        $this->actingAs($user)->delete("{$this->base($topic)}/{$lesson->id}")
            ->assertRedirect(route('courses.show', $topic->course));

        $this->assertModelMissing($lesson);
    }

    public function test_R04F02T01P01_el_html_peligroso_del_contenido_no_llega_a_la_pagina(): void
    {
        // Sin Vite, el layout no añade su propio <script type="module"> y la aserción solo ve el contenido de la lección.
        $this->withoutVite();

        $user = User::factory()->create();
        $topic = $this->temaDe($user, 'profesor');
        $lesson = Lesson::factory()->create([
            'topic_id' => $topic->id,
            'content' => "Texto normal\n\n<script>alert('xss')</script>\n\n<img src=x onerror=alert(1)>\n\n[enlace malicioso](javascript:alert(1))\n\n[enlace seguro](https://laravel.com)",
        ]);

        $this->actingAs($user)->get("{$this->base($topic)}/{$lesson->id}")
            ->assertOk()
            ->assertSee('Texto normal')
            ->assertDontSee('<script', false)
            ->assertDontSee('onerror', false)
            ->assertDontSee('javascript:', false)
            ->assertSee('href="https://laravel.com"', false);
    }

    public function test_R04F02T01P01_alumno_ve_la_leccion_pero_no_puede_crear_editar_ni_borrar(): void
    {
        $user = User::factory()->create();
        $topic = $this->temaDe($user, 'alumno');
        $lesson = Lesson::factory()->create(['topic_id' => $topic->id]);
        $url = "{$this->base($topic)}/{$lesson->id}";

        $this->actingAs($user)->get($url)->assertOk()->assertDontSee('Editar');
        $this->actingAs($user)->get("{$this->base($topic)}/create")->assertForbidden();
        $this->actingAs($user)->post($this->base($topic), ['title' => 'Intruso'])->assertForbidden();
        $this->actingAs($user)->get("{$url}/edit")->assertForbidden();
        $this->actingAs($user)->patch($url, ['title' => 'Hackeada'])->assertForbidden();
        $this->actingAs($user)->delete($url)->assertForbidden();

        $this->assertDatabaseHas('lessons', ['id' => $lesson->id, 'title' => $lesson->title]);
        $this->assertDatabaseMissing('lessons', ['title' => 'Intruso']);
    }

    public function test_R04F02T01P01_usuario_ajeno_al_curso_no_puede_ver_ni_gestionar_lecciones(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create();
        $lesson = Lesson::factory()->create(['topic_id' => $topic->id]);
        $url = "{$this->base($topic)}/{$lesson->id}";

        $this->actingAs($user)->get($url)->assertForbidden();
        $this->actingAs($user)->post($this->base($topic), ['title' => 'X'])->assertForbidden();
        $this->actingAs($user)->patch($url, ['title' => 'X'])->assertForbidden();
        $this->actingAs($user)->delete($url)->assertForbidden();
    }

    public function test_R04F02T01P01_profesor_de_otro_curso_no_puede_gestionar_lecciones_ajenas(): void
    {
        $profesor = User::factory()->create();
        $this->temaDe($profesor, 'profesor');
        $topicAjeno = Topic::factory()->create();
        $lesson = Lesson::factory()->create(['topic_id' => $topicAjeno->id]);

        $this->actingAs($profesor)->patch("{$this->base($topicAjeno)}/{$lesson->id}", ['title' => 'Hackeada'])->assertForbidden();
        $this->actingAs($profesor)->delete("{$this->base($topicAjeno)}/{$lesson->id}")->assertForbidden();
        $this->assertDatabaseHas('lessons', ['id' => $lesson->id]);
    }

    public function test_R04F02T01P01_una_leccion_no_se_abre_desde_otro_tema_ni_otro_curso(): void
    {
        $user = User::factory()->create();
        $topicA = $this->temaDe($user, 'profesor');
        $topicB = Topic::factory()->create(['course_id' => $topicA->course_id]);
        $lesson = Lesson::factory()->create(['topic_id' => $topicA->id]);

        $this->actingAs($user)->get("{$this->base($topicB)}/{$lesson->id}")->assertNotFound();

        $otroCurso = $this->temaDe($user, 'profesor');
        $this->actingAs($user)->get("{$this->base($otroCurso)}/{$lesson->id}")->assertNotFound();
    }

    public function test_R04F02T01P01_invitado_es_redirigido_al_login(): void
    {
        $topic = Topic::factory()->create();
        $lesson = Lesson::factory()->create(['topic_id' => $topic->id]);

        $this->get("{$this->base($topic)}/{$lesson->id}")->assertRedirect('/login');
        $this->post($this->base($topic), ['title' => 'X'])->assertRedirect('/login');
    }

    public function test_R04F02T01P01_courses_show_lista_las_lecciones_y_el_boton_solo_lo_ve_el_profesor(): void
    {
        $profesor = User::factory()->create();
        $alumno = User::factory()->create();
        $topic = $this->temaDe($profesor, 'profesor');
        $topic->course->users()->attach($alumno, ['role' => 'alumno']);
        Lesson::factory()->create(['topic_id' => $topic->id, 'title' => 'Lección uno', 'order' => 0]);
        Lesson::factory()->create(['topic_id' => $topic->id, 'title' => 'Lección dos', 'order' => 1]);

        $this->actingAs($profesor)->get(route('courses.show', $topic->course))
            ->assertSeeInOrder(['Lección uno', 'Lección dos'])
            ->assertSee('Añadir lección');

        $this->actingAs($alumno)->get(route('courses.show', $topic->course))
            ->assertSeeInOrder(['Lección uno', 'Lección dos'])
            ->assertDontSee('Añadir lección');
    }
}
