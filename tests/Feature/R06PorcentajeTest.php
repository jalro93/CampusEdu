<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\User;
use App\Support\Progress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/** R06F02: porcentaje de avance del alumno en cada curso. */
class R06PorcentajeTest extends TestCase
{
    use RefreshDatabase;

    /** Curso con un profesor y $n lecciones repartidas en dos temas. */
    private function cursoConLecciones(int $n): Course
    {
        $course = Course::factory()->create();
        $course->users()->attach(User::factory()->create(), ['role' => 'profesor']);
        $temas = Topic::factory()->count(2)->create(['course_id' => $course->id]);
        for ($i = 0; $i < $n; $i++) {
            Lesson::factory()->create(['topic_id' => $temas[$i % 2]->id, 'order' => $i]);
        }

        return $course;
    }

    private function alumnoEn(Course $course): User
    {
        $alumno = User::factory()->create();
        $course->users()->attach($alumno, ['role' => 'alumno']);

        return $alumno;
    }

    /** @return Collection<int, Lesson> */
    private function lecciones(Course $course): Collection
    {
        return $course->lessons()->orderBy('lessons.id')->get();
    }

    private function completar(User $alumno, Lesson ...$lessons): void
    {
        foreach ($lessons as $lesson) {
            $alumno->completedLessons()->attach($lesson->id, ['completed_at' => now()]);
        }
    }

    public function test_R06F02T01P01_completar_la_mitad_de_las_lecciones_muestra_un_50_por_ciento(): void
    {
        $course = $this->cursoConLecciones(4);
        $alumno = $this->alumnoEn($course);
        $this->completar($alumno, ...$this->lecciones($course)->take(2));

        $this->assertSame(['completed' => 2, 'total' => 4, 'percent' => 50], $course->progressFor($alumno));
        $this->actingAs($alumno)->get("/courses/{$course->slug}")
            ->assertOk()->assertSee('50 % completado')->assertSee('2 de 4 lecciones');
        $this->actingAs($alumno)->get('/courses')->assertOk()->assertSee('50 % completado');
    }

    public function test_R06F02T01P01_sin_lecciones_completadas_es_0_y_con_todas_es_100(): void
    {
        $course = $this->cursoConLecciones(3);
        $alumno = $this->alumnoEn($course);

        $this->assertSame(0, $course->progressFor($alumno)['percent']);
        $this->actingAs($alumno)->get("/courses/{$course->slug}")->assertSee('0 % completado');

        $this->completar($alumno, ...$this->lecciones($course));

        $this->assertSame(100, $course->progressFor($alumno)['percent']);
        $this->actingAs($alumno)->get("/courses/{$course->slug}")->assertSee('100 % completado');
    }

    public function test_R06F02T01P01_un_curso_sin_lecciones_muestra_0_por_ciento_sin_errores(): void
    {
        $course = $this->cursoConLecciones(0);
        $alumno = $this->alumnoEn($course);

        $this->assertSame(['completed' => 0, 'total' => 0, 'percent' => 0], $course->progressFor($alumno));
        $this->actingAs($alumno)->get("/courses/{$course->slug}")->assertOk()->assertSee('0 % completado')->assertSee('0 de 0 lecciones');
        $this->actingAs($alumno)->get('/courses')->assertOk()->assertSee('0 % completado');
    }

    public function test_R06F02T01P01_el_porcentaje_se_redondea_al_entero_mas_proximo(): void
    {
        $this->assertSame(33, Progress::percent(1, 3));
        $this->assertSame(67, Progress::percent(2, 3));
        $this->assertSame(0, Progress::percent(0, 7));
        $this->assertSame(100, Progress::percent(7, 7));
        $this->assertSame(0, Progress::percent(5, 0));
        $this->assertSame(100, Progress::percent(9, 4)); // nunca supera el 100 %
    }

    public function test_R06F02T01P01_el_progreso_es_individual_y_solo_cuenta_lecciones_del_propio_curso(): void
    {
        $course = $this->cursoConLecciones(2);
        $otro = $this->cursoConLecciones(2);
        $ana = $this->alumnoEn($course);
        $luis = $this->alumnoEn($course);
        $otro->users()->attach($ana, ['role' => 'alumno']);

        $this->completar($ana, $this->lecciones($course)->first());
        $this->completar($ana, ...$this->lecciones($otro));

        $this->assertSame(50, $course->progressFor($ana)['percent']);
        $this->assertSame(0, $course->progressFor($luis)['percent']);
        $this->assertSame(100, $otro->progressFor($ana)['percent']);
    }

    public function test_R06F02T01P01_al_borrar_una_leccion_el_porcentaje_se_recalcula(): void
    {
        $course = $this->cursoConLecciones(4);
        $alumno = $this->alumnoEn($course);
        $lecciones = $this->lecciones($course);
        $this->completar($alumno, $lecciones[0], $lecciones[1]);

        $lecciones[3]->delete(); // sin completar: 2 de 3
        $this->assertSame(67, $course->progressFor($alumno)['percent']);

        $lecciones[0]->delete(); // completada: 1 de 2
        $this->assertSame(['completed' => 1, 'total' => 2, 'percent' => 50], $course->progressFor($alumno));
    }

    public function test_R06F02T01P01_el_profesor_no_ve_barra_de_progreso(): void
    {
        $course = $this->cursoConLecciones(2);
        $profesor = $course->users()->first();

        $this->actingAs($profesor)->get("/courses/{$course->slug}")->assertOk()->assertDontSee('completado');
        $this->actingAs($profesor)->get('/courses')->assertOk()->assertDontSee('completado');
    }
}
