<?php

namespace Tests\Feature;

use App\Mail\FormSubmissionNotification;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\ContactForm;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Formularz kontaktowy z kreatora formularzy (Formularze → „Kontakt”) z polem „Osoba kontaktowa”. */
class ContactFormBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        SiteSetting::current()->update(['contact_email' => 'biuro@feer.test']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function coordinatorProject(): Project
    {
        $category = \App\Models\Category::create(['name' => 'Edukacja', 'slug' => 'edukacja']);

        return Project::create([
            'category_id' => $category->id,
            'title' => 'Klub Cyfrowy', 'slug' => 'klub-cyfrowy', 'content' => '<p>x</p>', 'is_published' => true,
            'is_completed' => false, 'show_coordinator' => true,
            'coordinator_name' => 'Anna Kowalska', 'coordinator_email' => 'anna@feer.test',
        ]);
    }

    private function submit(FormDefinition $form, array $data)
    {
        return $this->from(route('contact.show'))->post(route('formularz.store', $form->slug), [
            SpamGuard::TOKEN_FIELD => Crypt::encryptString(json_encode(['t' => now()->subSeconds(10)->timestamp, 'a' => 8])),
            SpamGuard::ANSWER_FIELD => '8',
            'data' => $data,
        ]);
    }

    /** Klucze pól formularza „Kontakt” po etykietach. */
    private function keys(FormDefinition $form): array
    {
        return collect($form->inputFields())->mapWithKeys(fn ($f) => [$f['type'] === 'coordinator' ? 'coordinator' : $f['label'] => $f['key']])->all();
    }

    public function test_contact_page_uses_builder_form_with_native_coordinator_select(): void
    {
        $this->coordinatorProject();

        $html = $this->get(route('contact.show'))->assertOk()->getContent();

        $form = FormDefinition::where('slug', ContactForm::SLUG)->first();
        $this->assertNotNull($form, 'Formularz „Kontakt” powinien utworzyć się sam.');
        $this->assertStringContainsString('action="'.route('formularz.store', 'kontakt').'"', $html);
        $this->assertMatchesRegularExpression('~<select id="field-kontakt-[a-z_]+"\s+name="data\[do_kogo_piszesz\]"~', $html);
        $this->assertStringContainsString('Anna Kowalska — Klub Cyfrowy', $html);
        $this->assertStringContainsString('Wyślij wiadomość', $html);
        $this->assertStringNotContainsString('role="combobox"', $html);
        // Objaśnienie gwiazdki jest przed pierwszym polem.
        $this->assertLessThan(strpos($html, 'name="data[imie_i_nazwisko]"'), strpos($html, 'są wymagane'));
    }

    public function test_message_goes_to_chosen_coordinator_or_general_address(): void
    {
        Mail::fake();
        $this->coordinatorProject();
        $form = ContactForm::definition();
        $k = $this->keys($form);
        $base = [$k['Imię i nazwisko'] => 'Jan Nowak', $k['E-mail'] => 'jan@example.com', $k['Wiadomość'] => 'Dzień dobry, mam pytanie o zajęcia.', $k[$form->inputFields()[6]['label']] => '1'];

        $this->submit($form, $base + [$k['coordinator'] => 'anna@feer.test'])->assertSessionHasNoErrors();
        Mail::assertSent(FormSubmissionNotification::class, fn ($m) => $m->hasTo('anna@feer.test') && ! $m->hasTo('biuro@feer.test'));

        $this->submit($form, $base + [$k['Wiadomość'] => 'Inna sprawa, ogólna.'])->assertSessionHasNoErrors();
        Mail::assertSent(FormSubmissionNotification::class, fn ($m) => $m->hasTo('biuro@feer.test'));

        $this->assertSame(2, FormSubmission::where('form_definition_id', $form->id)->count());
        $this->assertStringContainsString('Anna Kowalska', FormDefinition::displayValue(['type' => 'coordinator'], 'anna@feer.test'));
    }

    public function test_unknown_coordinator_is_rejected_and_errors_are_summarised_once(): void
    {
        $this->coordinatorProject();
        $form = ContactForm::definition();
        $k = $this->keys($form);

        $this->submit($form, [$k['coordinator'] => 'obcy@example.com', $k['Temat'] => 'pierwsza próba'])->assertSessionHasErrors('data.'.$k['coordinator']);

        $html = $this->followingRedirects()->from(route('contact.show'))->post(route('formularz.store', $form->slug), [
            SpamGuard::TOKEN_FIELD => Crypt::encryptString(json_encode(['t' => now()->subSeconds(10)->timestamp, 'a' => 8])),
            SpamGuard::ANSWER_FIELD => '8',
            'data' => [$k['coordinator'] => 'obcy@example.com'],
        ])->getContent();
        $this->assertStringContainsString('Proszę poprawić następujące błędy', $html);
        $this->assertSame(1, substr_count($html, "role=\"alert\""), "Alert tylko w podsumowaniu, nie przy każdym polu.");
        $this->assertStringContainsString('href="#field-kontakt-'.$k['coordinator'].'"', $html);
    }

    public function test_legacy_form_when_forms_module_is_disabled(): void
    {
        $settings = SiteSetting::current();
        $settings->update(['disabled_modules' => array_values(array_unique(array_merge($settings->disabled_modules ?? [], ['forms'])))]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->get(route('contact.show'))->assertOk()->getContent();
        $this->assertStringContainsString('action="'.route('contact.store').'"', $html);
        $this->assertNull(FormDefinition::where('slug', ContactForm::SLUG)->first());
    }

    public function test_admin_inbox_links_to_contact_form_submissions(): void
    {
        $form = ContactForm::definition();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.formularze.zgloszenia', $form))
            ->assertOk()
            ->assertSee(route('admin.formularze.zgloszenia', $form), false);
    }
}
