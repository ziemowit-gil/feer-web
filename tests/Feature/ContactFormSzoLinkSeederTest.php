<?php

namespace Tests\Feature;

use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Services\SzoClient;
use Database\Seeders\ContactFormSzoLinkSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContactFormSzoLinkSeederTest extends TestCase
{
    use RefreshDatabase;

    private function contact(array $settings = []): FormDefinition
    {
        return FormDefinition::withoutGlobalScopes()->updateOrCreate(['slug' => 'kontakt'], [
            'title' => 'Formularz kontaktowy', 'is_active' => true, 'fields' => [], 'settings' => $settings + ['delivery' => 'email'],
        ]);
    }

    private function szoOn(): void
    {
        config(['szo.enabled' => true, 'szo.url' => 'https://szo.test', 'szo.token' => 'token-testowy']);
    }

    public function test_podpina_formularz_gdy_szo_ma_formularz_kontakt_i_jest_idempotentny(): void
    {
        $this->szoOn();
        Http::fake(['szo.test/api/v1/forms.php*' => Http::response(['form' => ['slug' => 'kontakt', 'title' => 'Kontakt', 'fields' => [], 'consents' => []]])]);
        $form = $this->contact();

        $this->seed(ContactFormSzoLinkSeeder::class);
        $this->seed(ContactFormSzoLinkSeeder::class);

        $this->assertSame('kontakt', $form->fresh()->settings['szo_form_slug']);
        $this->assertSame('email', $form->fresh()->settings['delivery'], 'pozostałe ustawienia zostają');
    }

    public function test_nie_podpina_gdy_szo_nie_ma_formularza(): void
    {
        $this->szoOn();
        Http::fake(['szo.test/api/v1/forms.php*' => Http::response(['error' => 'not found'], 404)]);
        $form = $this->contact();

        $this->seed(ContactFormSzoLinkSeeder::class);

        $this->assertArrayNotHasKey('szo_form_slug', $form->fresh()->settings);
    }

    public function test_nie_nadpisuje_innego_podpiecia_i_nie_dziala_przy_wylaczonym_szo(): void
    {
        $this->szoOn();
        Http::fake(['szo.test/api/v1/forms.php*' => Http::response(['form' => ['slug' => 'kontakt']])]);
        $other = $this->contact(['szo_form_slug' => 'inny-formularz']);
        $this->seed(ContactFormSzoLinkSeeder::class);
        $this->assertSame('inny-formularz', $other->fresh()->settings['szo_form_slug']);

        config(['szo.enabled' => false]);
        $other->update(['settings' => ['delivery' => 'email']]);
        $this->seed(ContactFormSzoLinkSeeder::class);
        $this->assertArrayNotHasKey('szo_form_slug', $other->fresh()->settings);
    }

    public function test_po_podpieciu_zgloszenie_trafia_do_szo(): void
    {
        $this->szoOn();
        Http::fake(fn ($request) => $request->method() === 'POST'
            ? Http::response(['ok' => true, 'contact_id' => 77])
            : Http::response(['form' => ['slug' => 'kontakt']]));
        $form = $this->contact(['szo_form_slug' => 'kontakt']);
        $form->update(['fields' => [['key' => 'imie_i_nazwisko', 'label' => 'Imię i nazwisko', 'type' => 'text'], ['key' => 'e_mail', 'label' => 'E-mail', 'type' => 'email']]]);
        $submission = FormSubmission::create(['form_definition_id' => $form->id, 'data' => ['imie_i_nazwisko' => 'Jan Test', 'e_mail' => 'jan@example.org']]);

        $this->assertTrue(app(SzoClient::class)->pushSubmission($submission->fresh('form')));
        $this->assertSame(77, (int) $submission->fresh()->szo_contact_id);
        $this->assertNotNull($submission->fresh()->szo_synced_at);
    }
}
