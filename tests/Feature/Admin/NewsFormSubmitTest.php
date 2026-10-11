<?php

namespace Tests\Feature\Admin;

use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Formularz newsa musi mieć token CSRF i (przy edycji) PUT — bez nich zapis kończył się błędem 419/405. */
class NewsFormSubmitTest extends TestCase
{
    use RefreshDatabase;

    private function scopedForm(string $html): string
    {
        $start = strpos($html, '<form id="news-form"');
        $this->assertNotFalse($start, 'brak formularza news-form');

        return substr($html, $start, 600);
    }

    public function test_formularz_dodawania_ma_token_csrf(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $form = $this->scopedForm($this->get(route('admin.newsy.create'))->assertOk()->getContent());

        $this->assertStringContainsString('name="_token"', $form);
        $this->assertStringNotContainsString('name="_method"', $form);
    }

    public function test_formularz_edycji_ma_token_i_metode_put_a_zapis_dziala_z_ukladem_portal(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $news = News::create(['title' => 'Od 19 do 22 nie pracujemy!', 'slug' => 'od-19-do-22', 'content' => '<p>x</p>', 'is_published' => true, 'published_at' => now()]);

        $form = $this->scopedForm($this->get(route('admin.newsy.edit', $news))->assertOk()->getContent());
        $this->assertStringContainsString('name="_token"', $form);
        $this->assertStringContainsString('name="_method" value="PUT"', $form);

        $this->put(route('admin.newsy.update', $news), [
            'title' => 'Od 19 do 22 nie pracujemy!', 'slug' => 'od-19-do-22', 'content' => '<p>x</p>',
            'published_at' => now()->format('Y-m-d H:i'), 'article_layout' => 'portal', 'after' => 'stay',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('portal', $news->fresh()->article_layout);
    }
}
