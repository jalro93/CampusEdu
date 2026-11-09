<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\User;
use App\Support\VideoEmbed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** R04F03: vídeo externo (YouTube o Bunny.net) asociado a una lección. */
class R04VideoTest extends TestCase
{
    use RefreshDatabase;

    private const YOUTUBE_ID = 'dQw4w9WgXcQ';

    private const BUNNY_GUID = '0f0f0f0f-1a2b-4c3d-8e9f-0123456789ab';

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

    public function test_R04F03T01P01_una_url_de_youtube_se_guarda_y_se_muestra_incrustada(): void
    {
        $user = User::factory()->create();
        $topic = $this->temaDe($user, 'profesor');
        $url = 'https://www.youtube.com/watch?v='.self::YOUTUBE_ID;

        $this->actingAs($user)->post($this->base($topic), ['title' => 'Con vídeo', 'video_url' => $url])->assertRedirect();

        $lesson = Lesson::where('topic_id', $topic->id)->firstOrFail();
        $this->assertSame($url, $lesson->video_url);
        $this->actingAs($user)->get("{$this->base($topic)}/{$lesson->id}")
            ->assertOk()
            ->assertSee('<iframe src="https://www.youtube-nocookie.com/embed/'.self::YOUTUBE_ID.'"', false);
    }

    public function test_R04F03T01P01_se_aceptan_los_formatos_habituales_de_youtube(): void
    {
        $esperado = 'https://www.youtube-nocookie.com/embed/'.self::YOUTUBE_ID;

        foreach ([
            'https://youtu.be/'.self::YOUTUBE_ID,
            'https://www.youtube.com/embed/'.self::YOUTUBE_ID,
            'https://m.youtube.com/watch?v='.self::YOUTUBE_ID.'&t=30s',
        ] as $url) {
            $this->assertSame($esperado, VideoEmbed::embedUrl($url), $url);
        }
    }

    public function test_R04F03T01P01_una_url_de_bunny_se_muestra_incrustada(): void
    {
        $user = User::factory()->create();
        $topic = $this->temaDe($user, 'profesor');
        $lesson = Lesson::factory()->create([
            'topic_id' => $topic->id,
            'video_url' => 'https://iframe.mediadelivery.net/play/12345/'.self::BUNNY_GUID,
        ]);

        $this->actingAs($user)->get("{$this->base($topic)}/{$lesson->id}")
            ->assertOk()
            ->assertSee('<iframe src="https://player.mediadelivery.net/embed/12345/'.self::BUNNY_GUID.'"', false);
    }

    public function test_R04F03T01P01_las_urls_que_no_son_de_youtube_o_bunny_se_rechazan(): void
    {
        $user = User::factory()->create();
        $topic = $this->temaDe($user, 'profesor');

        foreach ([
            'https://vimeo.com/123456',
            'http://www.youtube.com/watch?v='.self::YOUTUBE_ID,
            'javascript:alert(1)',
            'https://youtube.com.evil.com/watch?v='.self::YOUTUBE_ID,
            'https://www.youtube.com@evil.com/watch?v='.self::YOUTUBE_ID,
            'https://www.youtube.com/watch?v=corto',
            'https://www.youtube.com/watch?v='.self::YOUTUBE_ID.'" onload="alert(1)',
            'https://player.mediadelivery.net/embed/abc/'.self::BUNNY_GUID,
            'esto no es una url',
        ] as $url) {
            $this->actingAs($user)->post($this->base($topic), ['title' => 'Mala', 'video_url' => $url])
                ->assertSessionHasErrors('video_url');
        }

        $this->assertDatabaseCount('lessons', 0);
    }

    public function test_R04F03T01P01_el_video_es_opcional_y_se_puede_quitar(): void
    {
        $user = User::factory()->create();
        $topic = $this->temaDe($user, 'profesor');

        $this->actingAs($user)->post($this->base($topic), ['title' => 'Sin vídeo'])->assertSessionHasNoErrors();
        $lesson = Lesson::where('topic_id', $topic->id)->firstOrFail();
        $this->assertNull($lesson->video_url);
        $this->actingAs($user)->get("{$this->base($topic)}/{$lesson->id}")->assertDontSee('<iframe', false);

        $lesson->update(['video_url' => 'https://youtu.be/'.self::YOUTUBE_ID]);
        $this->actingAs($user)->patch("{$this->base($topic)}/{$lesson->id}", ['title' => 'Sin vídeo', 'video_url' => ''])
            ->assertSessionHasNoErrors();
        $this->assertNull($lesson->fresh()->video_url);
    }

    public function test_R04F03T01P01_el_alumno_ve_el_video_de_su_curso(): void
    {
        $alumno = User::factory()->create();
        $topic = $this->temaDe($alumno, 'alumno');
        $lesson = Lesson::factory()->create(['topic_id' => $topic->id, 'video_url' => 'https://youtu.be/'.self::YOUTUBE_ID]);

        $this->actingAs($alumno)->get("{$this->base($topic)}/{$lesson->id}")
            ->assertOk()
            ->assertSee('youtube-nocookie.com/embed/'.self::YOUTUBE_ID, false);
    }
}
