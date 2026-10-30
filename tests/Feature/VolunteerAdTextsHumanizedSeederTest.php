<?php

namespace Tests\Feature;

use App\Models\VolunteerAd;
use Database\Seeders\VolunteerAdTextsHumanizedSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VolunteerAdTextsHumanizedSeederTest extends TestCase
{
    use RefreshDatabase;

    private function ad(array $attrs): VolunteerAd
    {
        return VolunteerAd::create($attrs + [
            'q_beneficiaries' => 'Odbiorcy.', 'q_tasks' => ['Zadanie'], 'q_mode' => 'zdalnie', 'q_schedule' => 'elastycznie',
            'q_time_commitment' => '3 godziny', 'q_benefits' => ['Portfolio'], 'q_how_to_apply' => 'Wypełnij formularz.', 'is_published' => true,
        ]);
    }

    public function test_poprawia_teksty_ofert_i_zostawia_adres(): void
    {
        $ad = $this->ad([
            'title' => 'Inkluzywny Copywriter - masz lekkie pióro? Szukamy Ciebie!', 'slug' => 'inkluzywny-copywriter',
            'lead' => 'FEER działa na rzecz osób z niepełnosprawnościami i chcemy, żeby nasze treści to pokazywały — jasno, angażująco i z sensem. Szukamy kogoś, kto pomoże nam przebudować komunikację na stronie od podstaw.',
            'q_how_to_apply' => 'Wypełni formularz. Odpowiemy do 3 dni roboczych.',
        ]);
        $own = $this->ad(['title' => 'Własny tytuł oferty', 'slug' => 'wlasna', 'lead' => 'Własna zajawka.']);

        $this->seed(VolunteerAdTextsHumanizedSeeder::class);
        $this->seed(VolunteerAdTextsHumanizedSeeder::class);

        $ad->refresh();
        $this->assertSame('Inkluzywny Copywriter: masz lekkie pióro? Szukamy Ciebie!', $ad->title);
        $this->assertSame('inkluzywny-copywriter', $ad->slug);
        $this->assertStringNotContainsString('—', $ad->lead);
        $this->assertSame('Wypełnij formularz. Odpowiemy do 3 dni roboczych.', $ad->q_how_to_apply);
        $this->assertSame('Własna zajawka.', $own->fresh()->lead);
    }
}
