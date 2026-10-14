<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Edytor osi czasu w panelu: nowy widok i zapis (kolejność, linki, pomijanie pustych wpisów). */
class TimelineEditorTest extends TestCase
{
    use RefreshDatabase;

    private function about(): Page
    {
        return Page::create(['title' => 'Fundacja Oś', 'slug' => 'fundacja-os', 'type' => 'about', 'content' => '<p>x</p>', 'is_published' => true,
            'about_timeline' => [['year' => '2017', 'text' => 'Powstanie stowarzyszenia', 'url' => 'https://example.org/krs', 'label' => 'KRS', 'color' => '#1E6DFF']]]);
    }

    public function test_widok_edytora_z_wpisami_i_podgladem(): void
    {
        $page = $this->about();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route('admin.os-czasu.edit', ['page' => $page->id]))->assertOk()
            ->assertSee('timelineEditor(', false)
            ->assertSee('Powstanie stowarzyszenia')
            ->assertSee('Sortuj wg roku')
            ->assertSee('Podgląd')
            ->assertSee('Zapisz oś czasu');
    }

    public function test_zapis_pomija_puste_wpisy_z_samym_kolorem(): void
    {
        $page = $this->about();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->put(route('admin.os-czasu.update', $page), ['about_timeline' => [
                ['year' => '2019', 'text' => 'Rejestracja w KRS', 'url' => 'https://example.org/krs', 'label' => 'KRS', 'color' => '#EA8F00'],
                ['year' => '', 'text' => '', 'color' => '#1E6DFF'],
                ['year' => '2017', 'text' => 'Powstanie', 'color' => '#1E6DFF'],
            ]])->assertRedirect();

        $timeline = $page->fresh()->about_timeline;
        $this->assertCount(2, $timeline);
        $this->assertSame(['2019', '2017'], array_column($timeline, 'year'));
        $this->assertSame('KRS', $timeline[0]['label']);
        $this->assertSame('#EA8F00', $timeline[0]['color']);
    }
}
