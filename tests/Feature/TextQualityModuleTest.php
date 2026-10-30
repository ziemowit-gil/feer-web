<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Project;
use App\Models\User;
use App\Services\TextQualityScanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TextQualityModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_skaner_rozpoznaje_wzorce_i_pomija_shortcody_oraz_zakresy(): void
    {
        $s = new TextQualityScanner;
        $rules = fn (string $t) => collect($s->matches($s->plain($t)))->pluck('rule')->unique()->sort()->values()->all();

        $this->assertSame(['dash'], $rules('<p>Piszemy do Was — i to ważne.</p>'));
        $this->assertSame([], $rules('<p>Godziny 11:00&ndash;14:00 oraz Fundacja-FEER.</p>'), 'zakres i łącznik bez spacji zostają');
        $this->assertSame(['placeholder'], $rules('<p>Do budynku prowadzi [chodnik / utwardzona droga].</p>'));
        $this->assertSame([], $rules('<p>[formularz:kontakt] [podstrony] [klauzule-rodo]</p>'), 'shortcody nie są nawiasami szablonowymi');
        $this->assertContains('contrast', $rules('<p>To nie kolejna konferencja, lecz spotkanie dla wszystkich.</p>'));
        $this->assertContains('slogan', $rules('<p>Każda pomoc ma znaczenie.</p>'));
        $this->assertContains('sales', $rules('<p>Oferujemy kompleksowe wsparcie.</p>'));
        $this->assertContains('space', $rules('<p>Fundacja w Krakowa .</p>'));
    }

    public function test_fragment_ma_kontekst_i_podswietlone_dopasowanie(): void
    {
        $text = 'Zaczynamy tu — i kończymy tam.';
        $m = (new TextQualityScanner)->matches($text)[0];

        $this->assertSame('dash', $m['rule']);
        $this->assertSame('Zaczynamy tu', $m['before']);
        $this->assertSame('i kończymy tam.', $m['after']);
        $this->assertSame(' — ', $m['match']);
    }

    public function test_strona_w_panelu_pokazuje_liczniki_trafienia_linki_edycji_i_filtry(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $category = Category::firstOrCreate(['slug' => 'testowa'], ['name' => 'Testowa']);
        $page = Page::withoutGlobalScopes()->create(['title' => 'Strona testowa', 'slug' => 'strona-testowa', 'type' => 'standard', 'is_published' => true, 'content' => '<p>Wsparcie — i jeszcze [liczba] miejsc.</p>']);
        Project::create(['title' => 'Działanie testowe', 'slug' => 'dzialanie-testowe', 'category_id' => $category->id, 'content' => '<p>Czysty tekst.</p>', 'why' => 'Wierzymy, że warto.', 'is_published' => true]);
        Faq::create(['question' => 'Pytanie?', 'answer' => 'Nie prowadzimy zbiórek, lecz edukujemy.', 'category' => 'X', 'is_published' => true]);

        $html = $this->actingAs($admin)->get(route('admin.jakosc-tekstow.index'))->assertOk()->getContent();
        $this->assertStringContainsString('Myślnik jako spójnik', $html);
        $this->assertStringContainsString('Nawiasy szablonowe do uzupełnienia', $html);
        $this->assertStringContainsString(route('admin.podstrony.edit', $page), $html);
        $this->assertMatchesRegularExpression('#<mark[^>]*>\[liczba\]</mark>#', $html);

        $only = $this->actingAs($admin)->get(route('admin.jakosc-tekstow.index', ['regula' => 'placeholder', 'zrodlo' => 'page']))->assertOk()->getContent();
        $this->assertStringContainsString('[liczba]', $only);
        $this->assertStringNotContainsString('Nie prowadzimy zbiórek', $only);

        $bad = $this->actingAs($admin)->get(route('admin.jakosc-tekstow.index', ['regula' => 'nieznana']))->assertOk();
        $bad->assertSee('Miejsca do poprawy');
    }

    public function test_dostep_tylko_dla_zalogowanych_administratorow(): void
    {
        $this->get(route('admin.jakosc-tekstow.index'))->assertRedirect();
        $user = User::factory()->create(['role' => 'editor']);
        $this->actingAs($user)->get(route('admin.jakosc-tekstow.index'))->assertStatus(403);
    }
}
