<?php

namespace Database\Seeders;

use App\Models\VolunteerAd;
use Illuminate\Database\Seeder;

/**
 * Redakcja ofert wolontariatu: tytuły bez myślnika w roli dwukropka, zajawka oferty „Inkluzywny Copywriter” oraz
 * literówka w „Jak się zgłosić” („Wypełni” zamiast „Wypełnij”). Adresy (slugi) zostają bez zmian.
 *
 * Idempotentny: podmienia tylko pola o dokładnie pierwotnym brzmieniu, więc nie nadpisze zmian z panelu.
 *
 *   php artisan db:seed --class=VolunteerAdTextsHumanizedSeeder --force
 */
class VolunteerAdTextsHumanizedSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'title' => [
                'Obalaj stereotypy o niepełnosprawności – dołącz do zespołu Fundacji FEER!' => 'Obalaj stereotypy o niepełnosprawności: dołącz do zespołu Fundacji FEER!',
                'Inkluzywny Copywriter - masz lekkie pióro? Szukamy Ciebie!' => 'Inkluzywny Copywriter: masz lekkie pióro? Szukamy Ciebie!',
            ],
            'lead' => [
                'FEER działa na rzecz osób z niepełnosprawnościami i chcemy, żeby nasze treści to pokazywały — jasno, angażująco i z sensem. Szukamy kogoś, kto pomoże nam przebudować komunikację na stronie od podstaw.' => 'FEER działa na rzecz osób z niepełnosprawnościami i chcemy, żeby nasze treści to pokazywały: jasno, angażująco i z sensem. Szukamy kogoś, kto pomoże nam przebudować komunikację na stronie od podstaw.',
            ],
            'q_how_to_apply' => [
                'Wypełni formularz. Odpowiemy do 3 dni roboczych.' => 'Wypełnij formularz. Odpowiemy do 3 dni roboczych.',
            ],
        ];

        $changed = 0;

        foreach (VolunteerAd::withoutGlobalScopes()->get() as $ad) {
            $dirty = false;

            foreach ($map as $field => $pairs) {
                if (isset($pairs[$ad->{$field}])) {
                    $ad->{$field} = $pairs[$ad->{$field}];
                    $dirty = true;
                }
            }

            if ($dirty) {
                $ad->save();
                $changed++;
            }
        }

        $this->command?->info($changed > 0 ? "Zmieniono teksty ofert wolontariatu: {$changed}." : 'Pominięto: teksty ofert były już zmieniane.');
    }
}
