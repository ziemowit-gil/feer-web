<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\FormDefinition;
use App\Models\Page;
use Database\Seeders\FaqPaidActivityFormTextsHumanizedSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqPaidActivityFormTextsHumanizedSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_poprawia_faq_strone_i_formularz_oraz_jest_idempotentny(): void
    {
        $faq = Faq::create(['question' => 'Czy rekrutujecie trenerów?', 'answer' => 'To zależy – nabory na trenerów prowadzimy okresowo, w zależności od aktualnych potrzeb i realizowanych projektów. Informacje o wszelkich otwartych naborach i możliwościach współpracy trenerskiej zawsze na bieżąco publikujemy na naszej stronie internetowej', 'category' => 'X', 'is_published' => true]);
        $own = Faq::create(['question' => 'Własne?', 'answer' => 'Własna odpowiedź redakcji.', 'category' => 'X', 'is_published' => true]);
        $page = Page::create(['title' => 'Odpłatna działalność pożytku publicznego — jak to u nas działa', 'slug' => 'odplatna-dzialalnosc-pozytku-publicznego', 'type' => 'standard', 'is_published' => true,
            'content' => '<p>Kierujemy się zasadą pełnej otwartości. Często pytasz, dlaczego.</p><p>Twoje wpłaty pokrywają wyłącznie prawdziwe wydatki:</p>']);
        $form = FormDefinition::create(['title' => 'Wsparcie', 'slug' => 'wsparcie-technologiczne-ngo', 'is_active' => true,
            'fields' => [['label' => 'Telefon', 'type' => 'tel', 'help_text' => 'Opcjonalnie — przyda się, jeśli wolisz rozmowę.']]]);

        $this->seed(FaqPaidActivityFormTextsHumanizedSeeder::class);
        $this->seed(FaqPaidActivityFormTextsHumanizedSeeder::class);

        $this->assertStringStartsWith('To zależy. Nabory na trenerów', $faq->fresh()->answer);
        $this->assertStringEndsWith('internetowej.', $faq->fresh()->answer);
        $this->assertSame('Własna odpowiedź redakcji.', $own->fresh()->answer);
        $this->assertSame('<p>Często pytasz, dlaczego.</p><p>Twoje wpłaty pokrywają wyłącznie rzeczywiste wydatki:</p>', $page->fresh()->content);
        $this->assertSame('Odpłatna działalność pożytku publicznego: jak to u nas działa', $page->fresh()->title);
        $this->assertSame('Opcjonalnie: przyda się, jeśli wolisz rozmowę.', $form->fresh()->fields[0]['help_text']);
    }
}
