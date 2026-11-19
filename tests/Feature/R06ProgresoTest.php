<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** R06F01: el alumno marca las lecciones como completadas. */
class R06ProgresoTest extends TestCase
{
    use RefreshDatabase;

    private function leccion(): Lesson
    {
        $course = Course::factory()->create();
        $course->users()->attach(User::factory()->create(), ['role' => 'profesor']);
        $topic = Topic::factory()->create(['course_id' => $course->id]);

        return Lesson::factory()->create(['topic_id' => $topic->id]);
    }

    private function alumnoEn(Lesson $lesson): User
    {
        $alumno = User::factory()->create();
        $lesson->topic->course->users()->attach($alumno, ['role' => 'alumno']);

        return $alumno;
    }

    private function url(Lesson $lesson): string
    {
        return "/courses/{$lesson->topic->course->slug}/topics/{$lesson->topic_id}/lessons/{$lesson->id}";
    }

    public function test_R06F01T01P01_el_alumno_marca_una_leccion_y_queda_registrada_con_su_fecha(): void
    {
        $lesson = $this->leccion();
        $alumno = $this->alumnoEn($lesson);

        $this->actingAs($alumno)->post($this->url($lesson).'/complete')->assertRedirect($this->url($lesson));

        $this->assertDatabaseHas('lesson_progress', ['user_id' => $alumno->id, 'lesson_id' => $lesson->id]);
        $this->assertNotNull($alumno->completedLessons()->first()->pivot->completed_at);
    }

    public function test_R06F01T01P01_marcar_dos_veces_no_duplica_el_registro_ni_cambia_la_fecha(): void
    {
        $lesson = $this->leccion();
        $alumno = $this->alumnoEn($lesson);

        $this->travelTo(now()->setTime(10, 0));
        $this->actingAs($alumno)->post($this->url($lesson).'/complete');
        $primera = $alumno->completedLessons()->first()->pivot->completed_at;

        $this->travelTo(now()->addDay());
        $this->actingAs($alumno)->post($this->url($lesson).'/complete')->assertRedirect();

        $this->assertSame(1, $alumno->completedLessons()->count());
        $this->assertSame($primera, $alumno->completedLessons()->first()->pivot->completed_at);
    }

    public function test_R06F01T01P01_el_alumno_puede_desmarcar_una_leccion(): void
    {
        $lesson = $this->leccion();
        $alumno = $this->alumnoEn($lesson);
        $this->actingAs($alumno)->post($this->url($lesson).'/complete');

        $this->actingAs($alumno)->delete($this->url($lesson).'/complete')->assertRedirect($this->url($lesson));

        $this->assertSame(0, $alumno->completedLessons()->count());
    }

    public function test_R06F01T01P01_el_progreso_es_individual_de_cada_alumno(): void
    {
        $lesson = $this->leccion();
        $ana = $this->alumnoEn($lesson);
        $luis = $this->alumnoEn($lesson);

        $this->actingAs($ana)->post($this->url($lesson).'/complete');

        $this->assertSame(1, $ana->completedLessons()->count());
        $this->assertSame(0, $luis->completedLessons()->count());
        $this->actingAs($luis)->get($this->url($lesson))->assertOk()->assertSee('Marcar como completada')->assertDontSee('Lección completada');
    }

    public function test_R06F01T01P01_solo_los_alumnos_del_curso_pueden_marcar_lecciones(): void
    {
        $lesson = $this->leccion();
        $profesor = $lesson->topic->course->users()->first();
        $ajeno = User::factory()->create();

        $this->actingAs($profesor)->post($this->url($lesson).'/complete')->assertForbidden();
        $this->actingAs($ajeno)->post($this->url($lesson).'/complete')->assertForbidden();
        $this->actingAs($ajeno)->delete($this->url($lesson).'/complete')->assertForbidden();

        $this->assertDatabaseCount('lesson_progress', 0);
    }

    public function test_R06F01T01P01_una_leccion_no_se_marca_desde_otro_tema_ni_otro_curso(): void
    {
        $lesson = $this->leccion();
        $alumno = $this->alumnoEn($lesson);
        $otroTema = Topic::factory()->create(['course_id' => $lesson->topic->course_id]);
        $slug = $lesson->topic->course->slug;

        $this->actingAs($alumno)->post("/courses/{$slug}/topics/{$otroTema->id}/lessons/{$lesson->id}/complete")->assertNotFound();
        $this->assertDatabaseCount('lesson_progress', 0);
    }

    public function test_R06F01T01P01_la_vista_muestra_el_estado_y_solo_ofrece_el_boton_al_alumno(): void
    {
        $lesson = $this->leccion();
        $alumno = $this->alumnoEn($lesson);
        $profesor = $lesson->topic->course->users()->first();

        $this->actingAs($alumno)->get($this->url($lesson))->assertSee('Marcar como completada');
        $this->actingAs($alumno)->post($this->url($lesson).'/complete');
        $this->actingAs($alumno)->get($this->url($lesson))->assertSee('Lección completada')->assertSee('Desmarcar');
        $this->actingAs($alumno)->get("/courses/{$lesson->topic->course->slug}")->assertSee('Lección completada');
        $this->actingAs($profesor)->get($this->url($lesson))->assertOk()->assertDontSee('Marcar como completada');
    }
}
