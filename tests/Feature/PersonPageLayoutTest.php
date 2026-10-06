<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonPageLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function parent(): Page
    {
        return Page::create(['title' => 'O nas', 'slug' => 'o-nas', 'type' => 'about', 'is_published' => true]);
    }

    private function person(Page $parent, string $name, array $extra = []): Page
    {
        $slug = 'o-nas/osoba/'.str($name)->slug();

        return Page::create($extra + ['title' => $name, 'slug' => $slug, 'type' => 'about_person', 'is_published' => true, 'parent_id' => $parent->id]);
    }

    public function test_profil_osoby_ma_hero_tresc_i_karte_kontaktu(): void
    {
        $parent = $this->parent();
        $person = $this->person($parent, 'Anna Kowalska', [
            'person_role' => 'Koordynatorka projektów', 'person_bio' => 'Łączę ludzi i pomysły.', 'person_email' => 'anna@example.com',
            'person_phone' => '600 100 200', 'person_member_label' => 'Zarząd', 'content' => '<p>Pracuję w fundacji od lat.</p>',
            'person_social' => ['linkedin' => 'https://linkedin.com/in/anna', 'website' => 'https://anna.example.com'],
            'founder_quote' => 'Dostępność to prawo, nie przywilej.',
        ]);

        $html = $this->get('/o-nas/osoba/anna-kowalska')->assertOk()->getContent();

        $this->assertStringContainsString('Anna Kowalska', $html);
        $this->assertStringContainsString('Koordynatorka projektów', $html);
        $this->assertStringContainsString('Łączę ludzi i pomysły.', $html);
        $this->assertStringContainsString('Napisz wiadomość', $html);
        $this->assertStringContainsString('mailto:anna@example.com', $html);
        $this->assertStringContainsString('tel:600100200', $html);
        $this->assertStringContainsString('Zarząd', $html);
        $this->assertStringContainsString('Dostępność to prawo, nie przywilej.', $html);
        $this->assertStringContainsString('O mnie', $html);
        $this->assertStringContainsString('Pracuję w fundacji od lat.', $html);
        $this->assertStringContainsString('id="person-contact-heading"', $html);
        $this->assertStringContainsString('https://linkedin.com/in/anna', $html);
        $this->assertStringContainsString('(otwiera się w nowej karcie)', $html);
        $this->assertSame(1, substr_count($html, '<h1'), 'Dokładnie jeden nagłówek h1.');
    }

    public function test_profil_bez_zdjecia_ma_inicjaly_a_bez_tresci_i_kontaktu_nie_ma_pustych_sekcji(): void
    {
        $parent = $this->parent();
        $this->person($parent, 'Jan Nowak');

        $html = $this->get('/o-nas/osoba/jan-nowak')->assertOk()->getContent();

        $this->assertStringContainsString('JN', $html);
        $this->assertStringNotContainsString('person-contact-heading', $html);
        $this->assertStringNotContainsString('>O mnie<', $html);
        $this->assertStringNotContainsString('Napisz wiadomość', $html);
    }

    public function test_sekcja_poznaj_zespol_pokazuje_pozostale_osoby_z_tej_samej_strony(): void
    {
        $parent = $this->parent();
        $this->person($parent, 'Anna Kowalska');
        $this->person($parent, 'Piotr Wiśniewski', ['person_role' => 'Trener']);
        $this->person($parent, 'Ukryta Osoba', ['is_published' => false]);
        $other = Page::create(['title' => 'Inna', 'slug' => 'inna', 'type' => 'about', 'is_published' => true]);
        $this->person($other, 'Obca Osoba');

        $html = $this->get('/o-nas/osoba/anna-kowalska')->assertOk()->getContent();

        $this->assertStringContainsString('Poznaj zespół', $html);
        $this->assertStringContainsString('Piotr Wiśniewski', $html);
        $this->assertStringContainsString('Trener', $html);
        $this->assertStringNotContainsString('Ukryta Osoba', $html);
        $this->assertStringNotContainsString('Obca Osoba', $html);
    }
}
