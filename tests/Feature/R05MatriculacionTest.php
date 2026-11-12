<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\EnrollmentRequest;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** R05: matriculación con aprobación del profesor y consulta de los cursos matriculados. */
class R05MatriculacionTest extends TestCase
{
    use RefreshDatabase;

    private function cursoConProfesor(?User $profesor = null, array $atributos = []): Course
    {
        $course = Course::factory()->create($atributos);
        $course->users()->attach($profesor ?? User::factory()->create(), ['role' => 'profesor']);

        return $course;
    }

    private function solicitud(User $alumno, Course $course, string $estado = EnrollmentRequest::PENDING): EnrollmentRequest
    {
        return EnrollmentRequest::create(['user_id' => $alumno->id, 'course_id' => $course->id, 'status' => $estado]);
    }

    public function test_R05F01T01P01_el_catalogo_muestra_los_cursos_a_los_que_no_se_pertenece(): void
    {
        $alumno = User::factory()->create();
        $ajeno = $this->cursoConProfesor(null, ['title' => 'Curso Ajeno']);
        $propio = $this->cursoConProfesor(null, ['title' => 'Curso Propio']);
        $propio->users()->attach($alumno, ['role' => 'alumno']);

        $this->actingAs($alumno)->get('/catalog')
            ->assertOk()
            ->assertSee('Curso Ajeno')
            ->assertDontSee('Curso Propio')
            ->assertSee('Solicitar matrícula');
    }

    public function test_R05F01T01P01_solicitar_matricula_deja_la_solicitud_pendiente_sin_dar_acceso(): void
    {
        $alumno = User::factory()->create();
        $course = $this->cursoConProfesor(null, ['title' => 'Curso Cerrado']);

        $this->actingAs($alumno)->post("/courses/{$course->slug}/enrollment-requests")->assertRedirect('/catalog');

        $this->assertDatabaseHas('enrollment_requests', ['user_id' => $alumno->id, 'course_id' => $course->id, 'status' => 'pendiente']);
        $this->assertFalse($alumno->isMemberOf($course));
        $this->actingAs($alumno)->get("/courses/{$course->slug}")->assertForbidden();
        $this->actingAs($alumno)->get('/courses')->assertOk()->assertDontSee('Curso Cerrado');
        $this->actingAs($alumno)->get('/catalog')->assertSee('Solicitud pendiente');
    }

    public function test_R05F01T01P01_solicitar_dos_veces_no_duplica_la_solicitud(): void
    {
        $alumno = User::factory()->create();
        $course = $this->cursoConProfesor();

        $this->actingAs($alumno)->post("/courses/{$course->slug}/enrollment-requests");
        $this->actingAs($alumno)->post("/courses/{$course->slug}/enrollment-requests")->assertRedirect('/catalog');

        $this->assertSame(1, EnrollmentRequest::count());
    }

    public function test_R05F01T01P01_quien_ya_participa_en_el_curso_no_puede_solicitar_matricula(): void
    {
        $profesor = User::factory()->create();
        $course = $this->cursoConProfesor($profesor);
        $alumno = User::factory()->create();
        $course->users()->attach($alumno, ['role' => 'alumno']);

        $this->actingAs($profesor)->post("/courses/{$course->slug}/enrollment-requests")->assertForbidden();
        $this->actingAs($alumno)->post("/courses/{$course->slug}/enrollment-requests")->assertForbidden();
        $this->assertSame(0, EnrollmentRequest::count());
    }

    public function test_R05F01T01P01_el_profesor_aprueba_y_el_alumno_pasa_a_ser_miembro_del_curso(): void
    {
        $profesor = User::factory()->create();
        $course = $this->cursoConProfesor($profesor, ['title' => 'Curso Aprobado']);
        $alumno = User::factory()->create();
        $solicitud = $this->solicitud($alumno, $course);

        $this->actingAs($profesor)->patch("/courses/{$course->slug}/enrollment-requests/{$solicitud->id}/approve")
            ->assertRedirect("/courses/{$course->slug}");

        $this->assertSame('aprobada', $solicitud->fresh()->status);
        $this->assertSame('alumno', $alumno->roleIn($course));
        $this->actingAs($alumno)->get('/courses')->assertOk()->assertSee('Curso Aprobado');
        $this->actingAs($alumno)->get("/courses/{$course->slug}")->assertOk();
        $this->actingAs($alumno)->get('/catalog')->assertDontSee('Curso Aprobado');
    }

    public function test_R05F01T01P01_el_profesor_rechaza_y_el_alumno_puede_volver_a_solicitar(): void
    {
        $profesor = User::factory()->create();
        $course = $this->cursoConProfesor($profesor);
        $alumno = User::factory()->create();
        $solicitud = $this->solicitud($alumno, $course);

        $this->actingAs($profesor)->patch("/courses/{$course->slug}/enrollment-requests/{$solicitud->id}/reject")->assertRedirect();

        $this->assertSame('rechazada', $solicitud->fresh()->status);
        $this->assertFalse($alumno->isMemberOf($course));
        $this->actingAs($alumno)->get('/catalog')->assertSee('Solicitud rechazada')->assertSee('Volver a solicitar');

        $this->actingAs($alumno)->post("/courses/{$course->slug}/enrollment-requests");
        $this->assertSame('pendiente', $solicitud->fresh()->status);
        $this->assertSame(1, EnrollmentRequest::count());
    }

    public function test_R05F01T01P01_solo_el_profesor_del_curso_puede_aprobar_o_rechazar(): void
    {
        $course = $this->cursoConProfesor();
        $alumno = User::factory()->create();
        $solicitud = $this->solicitud($alumno, $course);
        $otroProfesor = $this->cursoConProfesor()->users()->first();
        $otroAlumno = User::factory()->create();
        $course->users()->attach($otroAlumno, ['role' => 'alumno']);
        $url = "/courses/{$course->slug}/enrollment-requests/{$solicitud->id}";

        foreach ([$alumno, $otroAlumno, $otroProfesor] as $usuario) {
            $this->actingAs($usuario)->patch("{$url}/approve")->assertForbidden();
            $this->actingAs($usuario)->patch("{$url}/reject")->assertForbidden();
        }

        $this->assertSame('pendiente', $solicitud->fresh()->status);
        $this->assertFalse($alumno->isMemberOf($course));
    }

    public function test_R05F01T01P01_una_solicitud_ya_resuelta_no_se_puede_volver_a_resolver(): void
    {
        $profesor = User::factory()->create();
        $course = $this->cursoConProfesor($profesor);
        $alumno = User::factory()->create();
        $solicitud = $this->solicitud($alumno, $course, EnrollmentRequest::REJECTED);
        $url = "/courses/{$course->slug}/enrollment-requests/{$solicitud->id}";

        $this->actingAs($profesor)->patch("{$url}/approve")->assertForbidden();

        $this->assertFalse($alumno->isMemberOf($course));
        $this->assertSame('rechazada', $solicitud->fresh()->status);
    }

    public function test_R05F01T01P01_una_solicitud_no_se_resuelve_desde_otro_curso(): void
    {
        $profesor = User::factory()->create();
        $suyo = $this->cursoConProfesor($profesor);
        $otro = $this->cursoConProfesor();
        $alumno = User::factory()->create();
        $solicitud = $this->solicitud($alumno, $otro);

        $this->actingAs($profesor)->patch("/courses/{$suyo->slug}/enrollment-requests/{$solicitud->id}/approve")->assertNotFound();

        $this->assertFalse($alumno->isMemberOf($otro));
        $this->assertFalse($alumno->isMemberOf($suyo));
    }

    public function test_R05F01T01P01_solo_el_profesor_ve_las_solicitudes_pendientes_de_su_curso(): void
    {
        $profesor = User::factory()->create();
        $course = $this->cursoConProfesor($profesor);
        $solicitante = User::factory()->create(['name' => 'Ana Solicitante']);
        $this->solicitud($solicitante, $course);
        $alumno = User::factory()->create();
        $course->users()->attach($alumno, ['role' => 'alumno']);

        $this->actingAs($profesor)->get("/courses/{$course->slug}")
            ->assertOk()->assertSee('Solicitudes de matrícula pendientes')->assertSee('Ana Solicitante')->assertSee('Aprobar');
        $this->actingAs($alumno)->get("/courses/{$course->slug}")
            ->assertOk()->assertDontSee('Solicitudes de matrícula pendientes')->assertDontSee('Ana Solicitante');
    }

    public function test_R05F01T01P01_hay_que_haber_iniciado_sesion_para_usar_la_matriculacion(): void
    {
        $course = $this->cursoConProfesor();

        $this->get('/catalog')->assertRedirect('/login');
        $this->post("/courses/{$course->slug}/enrollment-requests")->assertRedirect('/login');
    }

    public function test_R05F02T01P01_el_alumno_solo_ve_en_su_panel_los_cursos_en_los_que_esta_matriculado(): void
    {
        $alumno = User::factory()->create();
        $matriculado = $this->cursoConProfesor(null, ['title' => 'Curso Matriculado']);
        $matriculado->users()->attach($alumno, ['role' => 'alumno']);
        $pendiente = $this->cursoConProfesor(null, ['title' => 'Curso Pendiente']);
        $this->solicitud($alumno, $pendiente);
        $this->cursoConProfesor(null, ['title' => 'Curso Sin Relacion']);

        $this->actingAs($alumno)->get('/courses')
            ->assertOk()
            ->assertSee('Curso Matriculado')
            ->assertDontSee('Curso Pendiente')
            ->assertDontSee('Curso Sin Relacion');
    }

    public function test_R05F02T01P01_el_alumno_matriculado_consulta_los_temas_y_lecciones_de_su_curso(): void
    {
        $profesor = User::factory()->create();
        $course = $this->cursoConProfesor($profesor);
        $topic = Topic::factory()->create(['course_id' => $course->id, 'title' => 'Tema Uno']);
        $lesson = Lesson::factory()->create(['topic_id' => $topic->id, 'title' => 'Leccion Uno']);
        $alumno = User::factory()->create();
        $solicitud = $this->solicitud($alumno, $course);

        $this->actingAs($alumno)->get("/courses/{$course->slug}/topics/{$topic->id}/lessons/{$lesson->id}")->assertForbidden();

        $this->actingAs($profesor)->patch("/courses/{$course->slug}/enrollment-requests/{$solicitud->id}/approve");

        $this->actingAs($alumno)->get("/courses/{$course->slug}")->assertOk()->assertSee('Tema Uno')->assertSee('Leccion Uno');
        $this->actingAs($alumno)->get("/courses/{$course->slug}/topics/{$topic->id}/lessons/{$lesson->id}")->assertOk()->assertSee('Leccion Uno');
    }
}
