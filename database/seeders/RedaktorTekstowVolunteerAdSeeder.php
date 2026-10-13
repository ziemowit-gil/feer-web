<?php

namespace Database\Seeders;

use App\Models\VolunteerAd;
use Illuminate\Database\Seeder;

/**
 * Ogłoszenie wolontariackie: redaktor/ka tekstów internetowych (dziennikarstwo / komunikacja społeczna).
 *
 * Bezpieczne na produkcji — aktualizuje ogłoszenie po slugu, nie rusza innych.
 * Uruchomienie: php artisan db:seed --class=RedaktorTekstowVolunteerAdSeeder
 */
class RedaktorTekstowVolunteerAdSeeder extends Seeder
{
    public function run(): void
    {
        VolunteerAd::updateOrCreate(
            ['slug' => 'redaktor-tekstow-internetowych'],
            [
                'title'             => 'Redaktor/ka tekstów internetowych (dziennikarstwo / komunikacja społeczna) – wolontariat w FEER',
                'lead'              => 'FEER działa na rzecz osób z niepełnosprawnościami i chcemy, żeby nasze treści to pokazywały — jasno, angażująco i z sensem. Szukamy kogoś, kto pomoże nam przebudować komunikację na stronie od podstaw.',
                'q_beneficiaries'   => 'Strona FEER wymaga przemyślenia — nie tylko korekty, ale realnego przepisania treści tak, by skutecznie trafiały do różnych odbiorców: instytucji, darczyńców i osób z niepełnosprawnościami. Twoja praca przełoży się bezpośrednio na to, jak organizacja jest postrzegana i czy jej misja jest zrozumiała.',
                'q_tasks'           => [
                    'Analiza obecnych tekstów na feer.org.pl pod kątem komunikacyjnym',
                    'Przebudowa struktury i języka wybranych podstron (zakładka „O nas”, opisy projektów, aktualności)',
                    'Pisanie nowych treści w stylu dziennikarskim lub contentowym',
                    'Wypracowanie spójnego tonu komunikacji dla organizacji',
                    'Konsultacje z zespołem FEER w trakcie procesu',
                ],
                'q_mode'            => 'zdalnie',
                'q_location'        => null,
                'q_schedule'        => 'Elastyczny, ustalany indywidualnie',
                'q_time_commitment' => 'Ok. 3–5 godzin miesięcznie, w zależności od etapu prac',
                'q_benefits'        => [
                    'Portfolio: realne teksty opublikowane na stronie aktywnej organizacji',
                    'Doświadczenie w komunikacji NGO i contencie społecznym',
                    'Zaświadczenie o wolontariacie',
                    'Kontakt z tematyką dostępności i projektowania inkluzywnego',
                ],
                'q_how_to_apply'    => 'Preferujemy osoby z wykształceniem lub doświadczeniem w dziennikarstwie bądź komunikacji społecznej (nie jest to warunek konieczny). Napisz kilka zdań o sobie i — jeśli masz — dołącz link do swoich tekstów.',
                'contact_name'      => 'Koordynacja wolontariatu',
                'contact_email'     => 'wolontariat@feer.org.pl',
                'audience'          => 'brand',
                'is_published'      => true,
                'order'             => 1,
            ],
        );
    }
}
