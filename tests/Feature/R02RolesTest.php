<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class R02RolesTest extends TestCase
{
    use RefreshDatabase;

    private function curso(User $user, string $rol): Course
    {
        $course = Course::factory()->create();
        $course->users()->attach($user, ['role' => $rol]);

        return $course;
    }

    public function test_R02F01T01P01_el_rol_es_por_curso(): void
    {
        $user = User::factory()->create();
        $a = $this->curso($user, 'profesor');
        $b = $this->curso($user, 'alumno');

        $this->assertTrue($user->teachesCourse($a));
        $this->assertTrue($user->isEnrolledIn($b));
        $this->assertFalse($user->teachesCourse($b));
    }

    public function test_R02F02T01P01_profesor_puede_editar_su_curso(): void
    {
        $user = User::factory()->create();
        $course = $this->curso($user, 'profesor');

        $this->assertTrue($user->can('update', $course));
        $this->assertTrue($user->can('delete', $course));
    }

    public function test_R02F02T02P01_alumno_no_puede_editar(): void
    {
        $user = User::factory()->create();
        $course = $this->curso($user, 'alumno');

        $this->assertTrue($user->can('view', $course));
        $this->assertFalse($user->can('update', $course));
        $this->assertFalse($user->can('delete', $course));
    }

    public function test_R02F02T03P01_ajeno_no_ve_el_curso(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $this->assertFalse($user->can('view', $course));
        $this->assertFalse($user->can('update', $course));
    }
}
