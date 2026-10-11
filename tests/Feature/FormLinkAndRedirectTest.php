<?php

namespace Tests\Feature;

use App\Models\FormDefinition;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class FormLinkAndRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function token(): array
    {
        return [
            SpamGuard::TOKEN_FIELD => Crypt::encryptString(json_encode(['t' => now()->subSeconds(10)->timestamp, 'a' => 8])),
            SpamGuard::ANSWER_FIELD => '8',
        ];
    }

    public function test_element_tekst_z_przyciskiem_renderuje_link_i_nie_jest_polem_danych(): void
    {
        $form = FormDefinition::create(['title' => 'Z linkiem', 'slug' => 'z-linkiem', 'is_active' => true, 'fields' => [
            ['label' => 'Imię', 'type' => 'text', 'required' => true],
            ['label' => 'Zobacz regulamin', 'type' => 'link', 'help_text' => 'Przed wysłaniem przeczytaj zasady.', 'url' => 'https://example.org/regulamin', 'new_tab' => true],
            ['label' => 'Zły adres', 'type' => 'link', 'url' => 'javascript:alert(1)'],
        ]]);

        $this->get(route('formularz.show', $form->slug))->assertOk()
            ->assertSee('Przed wysłaniem przeczytaj zasady.')
            ->assertSee('href="https://example.org/regulamin" target="_blank" rel="noopener"', false)
            ->assertSee('(otwiera się w nowej karcie)')
            ->assertDontSee('javascript:alert', false);

        $this->assertSame(['imie'], array_column($form->inputFields(), 'key'));
        $this->assertArrayNotHasKey('data.zobacz_regulamin', $form->validationRules([]));
    }

    public function test_po_wyslaniu_przekierowuje_na_ustawiony_adres(): void
    {
        $form = FormDefinition::create(['title' => 'Przekierowanie', 'slug' => 'przekierowanie', 'is_active' => true,
            'fields' => [['label' => 'Imię', 'type' => 'text', 'required' => true]],
            'settings' => ['redirect_url' => '/dziekujemy']]);

        $this->post(route('formularz.store', $form->slug), $this->token() + ['data' => ['imie' => 'Anna']])
            ->assertRedirect(url('/dziekujemy'));
        $this->assertSame(1, $form->submissions()->count());
    }

    public function test_kreator_zapisuje_element_i_przekierowanie_oraz_odrzuca_niebezpieczny_adres(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $base = ['title' => 'Nowy', 'slug' => 'nowy-link', 'is_active' => '1'];

        $this->actingAs($admin)->post(route('admin.formularze.store'), $base + [
            'fields' => [['label' => 'Dalej', 'type' => 'link', 'url' => '/kontakt', 'new_tab' => '1', 'help_text' => 'Tekst']],
            'settings' => ['redirect_url' => 'https://example.org/dzieki'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $form = FormDefinition::where('slug', 'nowy-link')->firstOrFail();
        $this->assertSame('/kontakt', $form->fields[0]['url']);
        $this->assertTrue($form->fields[0]['new_tab']);
        $this->assertSame('https://example.org/dzieki', $form->settings['redirect_url']);

        $this->actingAs($admin)->post(route('admin.formularze.store'), ['slug' => 'inny'] + $base + [
            'fields' => [['label' => 'Zły', 'type' => 'link', 'url' => 'javascript:alert(1)']],
        ])->assertSessionHasErrors('fields.0.url');
    }
}
