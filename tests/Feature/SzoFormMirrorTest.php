<?php

namespace Tests\Feature;

use App\Services\SzoClient;
use App\Services\SzoFormMirror;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SzoFormMirrorTest extends TestCase
{
    use RefreshDatabase;

    public function test_pola_dodatkowe_crm_dostaja_etykiete_typ_opcje_i_podpowiedz(): void
    {
        $this->mock(SzoClient::class, fn ($m) => $m->shouldReceive('form')->andReturn([
            'slug' => 'redaktor-www',
            'title' => 'Redaktor/ka',
            'description' => "Akapit 1\n\nAkapit 2",
            'fields' => [
                ['name' => 'imie_nazwisko', 'label' => 'Imię i nazwisko', 'type' => 'text', 'required' => true, 'field_def_id' => null],
                ['name' => '', 'label' => 'Doświadczenie', 'type' => 'textarea', 'required' => true, 'field_def_id' => 2, 'placeholder' => 'Kierunek, redakcja…'],
                ['name' => '', 'label' => 'Preferowany kontakt', 'type' => 'select', 'required' => false, 'field_def_id' => 5, 'options' => ['E-mail', 'Teams']],
                ['name' => '', 'label' => 'Lista bez opcji', 'type' => 'select', 'required' => false, 'field_def_id' => 6, 'options' => []],
            ],
            'consents' => [['id' => 'c1', 'text' => 'Zgoda', 'required' => true]],
        ]));

        $form = app(SzoFormMirror::class)->sync('redaktor-www');
        $by = collect($form->fields)->keyBy('szo_name');

        $this->assertSame('Imię i nazwisko', $by['imie_nazwisko']['label']);
        $this->assertSame('textarea', $by['custom:2']['type']);
        $this->assertSame('Doświadczenie', $by['custom:2']['label']);
        $this->assertSame('Kierunek, redakcja…', $by['custom:2']['placeholder']);
        $this->assertSame('select', $by['custom:5']['type']);
        $this->assertSame('E-mail, Teams', $by['custom:5']['options']);
        $this->assertSame('text', $by['custom:6']['type']);
        $this->assertSame('checkbox', $by['c1']['type']);
    }
}
