<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PageImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageSliderSlideContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_slajd_strony_zapisuje_tytul_tekst_i_przycisk(): void
    {
        $page = Page::create(['title' => 'O nas', 'slug' => 'o-nas']);
        $image = $page->images()->create(['alt' => 'Zespół', 'order' => 1]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->put(route('admin.podstrony.zdjecia.update', $image), [
                'alt' => 'Zespół',
                'title' => 'Poznaj nas',
                'text' => 'Działamy od 2016 roku.',
                'button_label' => 'Kontakt',
                'button_url' => '/kontakt',
            ])
            ->assertRedirect(route('admin.podstrony.edit', $page));

        $this->assertSame(['Poznaj nas', 'Działamy od 2016 roku.', 'Kontakt', '/kontakt'],
            array_values($image->fresh()->only(['title', 'text', 'button_label', 'button_url'])));
    }

    public function test_przycisk_wymaga_adresu(): void
    {
        $page = Page::create(['title' => 'O nas', 'slug' => 'o-nas']);
        $image = $page->images()->create(['order' => 1]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->put(route('admin.podstrony.zdjecia.update', $image), ['button_label' => 'Kontakt'])
            ->assertSessionHasErrors('button_url');
    }

    public function test_slider_pokazuje_tresc_slajdu_pod_zdjeciem(): void
    {
        $slides = collect([
            new PageImage(['alt' => 'A', 'title' => 'Poznaj nas', 'text' => 'Działamy od 2016 roku.', 'button_label' => 'Kontakt', 'button_url' => '/kontakt']),
            new PageImage(['alt' => 'B', 'caption' => 'Sam podpis']),
        ]);

        $html = view('partials.page-slider', ['slides' => $slides, 'label' => 'O nas'])->render();

        $this->assertStringContainsString('Poznaj nas', $html);
        $this->assertStringContainsString('Działamy od 2016 roku.', $html);
        $this->assertStringContainsString('href="/kontakt"', $html);
        $this->assertStringContainsString('Sam podpis', $html);
    }
}
