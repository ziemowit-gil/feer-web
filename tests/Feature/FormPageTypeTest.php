<?php

namespace Tests\Feature;

use App\Models\FormDefinition;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormPageTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function form(array $settings = []): FormDefinition
    {
        return FormDefinition::create([
            'title' => 'Zgłoszenie organizacji', 'slug' => 'zgloszenie-org', 'is_active' => true, 'settings' => $settings,
            'fields' => [['label' => 'Nazwa', 'type' => 'text', 'required' => true]],
        ]);
    }

    public function test_strona_pokazuje_wstep_formularz_i_informacje(): void
    {
        $this->form(['open_until' => now()->addDays(5)->format('Y-m-d H:i'), 'max_submissions' => 10]);
        Page::create([
            'title' => 'Zgłoś organizację', 'slug' => 'zglos', 'type' => 'form_page', 'is_published' => true,
            'content' => '<p>Wstęp do formularza.</p>',
            'type_data' => [
                'lead' => 'Krótko o programie.', 'fp_form' => 'zgloszenie-org', 'fp_time' => 'ok. 5 minut',
                'requirements' => [['text' => 'Numer KRS'], ['text' => '']],
                'fp_after' => 'Odpowiemy w 5 dni.', 'contact_email' => 'a@example.org',
            ],
        ]);

        $html = $this->get('/zglos')->assertOk()
            ->assertSee('Krótko o programie.')
            ->assertSee('Wstęp do formularza.')
            ->assertSee('id="formularz-zgloszenie-org"', false)
            ->assertSee('<h2 id="form-heading-zgloszenie-org"', false)
            ->assertSee('ok. 5 minut')
            ->assertSee('Zgłoszenia do')
            ->assertSee('Pozostałe miejsca')
            ->assertSee('Numer KRS')
            ->assertSee('Odpowiemy w 5 dni.')
            ->assertSee('href="#formularz-strony"', false)
            ->getContent();

        // Wstęp jest przed formularzem.
        $this->assertLessThan(strpos($html, 'id="formularz-zgloszenie-org"'), strpos($html, 'Wstęp do formularza.'));
    }

    public function test_wlasny_naglowek_obniza_tytul_formularza_i_brak_formularza(): void
    {
        $this->form();
        Page::create(['title' => 'A', 'slug' => 'a', 'type' => 'form_page', 'is_published' => true,
            'type_data' => ['fp_form' => 'zgloszenie-org', 'fp_heading' => 'Zapisz się']]);
        $this->get('/a')->assertOk()->assertSee('Zapisz się')->assertSee('<h3 id="form-heading-zgloszenie-org"', false);

        Page::create(['title' => 'B', 'slug' => 'b', 'type' => 'form_page', 'is_published' => true, 'type_data' => ['fp_form' => 'nie-ma']]);
        $this->get('/b')->assertOk()->assertSee('Formularz jest chwilowo niedostępny');
    }

    public function test_panel_zapisuje_pola_typu(): void
    {
        $this->form();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $page = Page::create(['title' => 'F', 'slug' => 'f', 'type' => 'standard', 'is_published' => true]);

        $this->actingAs($admin)->get(route('admin.podstrony.edit', $page))->assertOk()
            ->assertSee('data-type-fields="form_page"', false)
            ->assertSee('<option value="zgloszenie-org"', false);

        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), [
            'title' => 'F', 'slug' => 'f', 'type' => 'form_page', 'parent_id' => '', 'is_published' => '1',
            'type_data' => ['fp_form' => 'zgloszenie-org', 'fp_time' => '5 minut', 'requirements' => [['text' => 'KRS']], 'fp_after' => 'Odezwiemy się.'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $td = $page->refresh()->type_data;
        $this->assertSame('zgloszenie-org', $td['fp_form']);
        $this->assertSame('KRS', $td['requirements'][0]['text']);
        $this->assertSame('Odezwiemy się.', $td['fp_after']);
    }
}
