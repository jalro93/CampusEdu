<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** R07F01: la interfaz se adapta a móvil, tablet y escritorio. */
class R07ResponsiveTest extends TestCase
{
    use RefreshDatabase;

    private const VIEWPORT = '<meta name="viewport" content="width=device-width, initial-scale=1">';

    /** R07F01T01P01: todas las pantallas principales declaran el viewport. */
    public function test_las_pantallas_principales_declaran_el_viewport(): void
    {
        $profesor = User::factory()->create();
        $course = Course::factory()->create();
        $course->users()->attach($profesor, ['role' => 'profesor']);
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['topic_id' => $topic->id]);

        $this->get('/')->assertSee(self::VIEWPORT, false);
        $this->get('/login')->assertSee(self::VIEWPORT, false);

        $this->actingAs($profesor);
        foreach ([
            route('dashboard'),
            route('courses.index'),
            route('catalog.index'),
            route('courses.show', $course),
            route('courses.topics.lessons.show', [$course, $topic, $lesson]),
        ] as $url) {
            $this->get($url)->assertOk()->assertSee(self::VIEWPORT, false);
        }
    }

    /** R07F01T01P02: las cabeceras con acciones se apilan en pantallas estrechas. */
    public function test_las_cabeceras_con_acciones_se_apilan_en_movil(): void
    {
        $profesor = User::factory()->create();
        $course = Course::factory()->create();
        $course->users()->attach($profesor, ['role' => 'profesor']);
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['topic_id' => $topic->id]);

        $this->actingAs($profesor);

        $this->get(route('courses.show', $course))->assertSee('flex-col', false)->assertSee('sm:flex-row', false);
        $this->get(route('courses.topics.lessons.show', [$course, $topic, $lesson]))
            ->assertSee('flex-col', false)->assertSee('sm:flex-row', false);
    }

    /** R07F01T01P03: un título muy largo no rompe la maquetación (se parte en varias líneas). */
    public function test_los_titulos_largos_se_pueden_partir(): void
    {
        $profesor = User::factory()->create();
        $course = Course::factory()->create(['title' => str_repeat('Largo', 40)]);
        $course->users()->attach($profesor, ['role' => 'profesor']);

        $this->actingAs($profesor)->get(route('courses.show', $course))->assertOk()->assertSee('break-words', false);
        $this->get(route('courses.index'))->assertOk()->assertSee('break-words', false);
    }

    /** R07F01T01P04: el contenido Markdown no se desborda (hoja de estilos). */
    public function test_el_css_contiene_las_reglas_de_contencion_del_contenido(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('overflow-wrap: anywhere', $css);
        $this->assertStringContainsString('max-width: 100%', $css);
        $this->assertStringContainsString('overflow-x: auto', $css);
    }
}
