<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\VolunteerAd;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Lista ofert wolontariatu w szablonie FEER: krótki wstęp, jedna oferta w węższej karcie bez pustego boku. */
class VolunteerIndexLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function ad(string $slug): VolunteerAd
    {
        return VolunteerAd::create([
            'title' => 'Oferta '.$slug, 'slug' => $slug, 'lead' => 'Zajawka.', 'q_beneficiaries' => 'Odbiorcy.', 'q_tasks' => ['Zadanie'], 'q_mode' => 'zdalnie',
            'q_schedule' => 'elastycznie', 'q_time_commitment' => '3 godziny', 'q_benefits' => ['Portfolio'], 'q_how_to_apply' => 'Wypełnij formularz.', 'is_published' => true,
        ]);
    }

    private function feerTemplate(): void
    {
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_jedna_oferta_ma_wezsza_karte_a_dwie_uklad_dwukolumnowy(): void
    {
        $this->feerTemplate();
        $this->ad('pierwsza');

        $one = $this->get(route('volunteer.index'))->assertOk()->getContent();
        $this->assertStringContainsString('Każda oferta opisuje, na czym polega pomoc', $one);
        $this->assertStringNotContainsString('Poniżej znajdziesz aktualne oferty', $one);
        $this->assertMatchesRegularExpression('#<ul class="[^"]*max-w-3xl[^"]*" role="list">#', $one);
        $this->assertDoesNotMatchRegularExpression('#<ul class="[^"]*md:grid-cols-2[^"]*" role="list">#', $one);

        $this->ad('druga');
        $two = $this->get(route('volunteer.index'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#<ul class="[^"]*md:grid-cols-2[^"]*" role="list">#', $two);
    }
}
