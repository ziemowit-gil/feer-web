<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\MaterialSubscriber;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Skrzynki w panelu (zgłoszenia, wiadomości kontaktowe, zapisy na materiały) są osobne dla każdej witryny. */
class InboxSiteScopingTest extends TestCase
{
    use RefreshDatabase;

    private SiteSetting $main;

    private SiteSetting $sub;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        $this->main = SiteSetting::current();
        $this->sub = SiteSetting::create([
            'site_name' => 'Szkolenia', 'slug' => 'szkolenia', 'domain' => 'szkolenia.example.test',
            'parent_site_id' => $this->main->id, 'site_kind' => 'standard',
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => $this->main->site_template,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_nowe_wpisy_dostaja_biezaca_witryne(): void
    {
        $form = FormDefinition::withoutGlobalScopes()->create(['title' => 'Kontakt', 'slug' => 'kontakt', 'is_active' => true, 'fields' => [], 'settings' => []]);

        $this->assertSame($this->main->id, FormSubmission::create(['form_definition_id' => $form->id, 'data' => []])->site_id);
        $this->assertSame($this->main->id, ContactMessage::create(['name' => 'A', 'email' => 'a@example.test', 'subject' => 's', 'message' => 'm'])->site_id);
        $this->assertSame($this->main->id, MaterialSubscriber::create(['email' => 'a@example.test'])->site_id);
    }

    public function test_wiadomosci_kontaktowe_sa_osobne_per_witryna(): void
    {
        ContactMessage::create(['site_id' => $this->sub->id, 'name' => 'Ania', 'email' => 'a@example.test', 'subject' => 'Pytanie z sub', 'message' => 'x']);
        ContactMessage::create(['site_id' => $this->main->id, 'name' => 'Basia', 'email' => 'b@example.test', 'subject' => 'Pytanie z głównej', 'message' => 'x']);

        $this->actingAs($this->admin())->withSession(['admin_site_chosen' => true]);
        $this->get(route('admin.wiadomosci-kontaktowe.index'))->assertOk()->assertSee('Pytanie z głównej')->assertDontSee('Pytanie z sub');
        $this->assertSame(1, ContactMessage::unreadCount());

        $this->withSession(['admin_site_chosen' => true, 'admin_active_site_id' => $this->sub->id])
            ->get(route('admin.wiadomosci-kontaktowe.index'))->assertOk()->assertSee('Pytanie z sub')->assertDontSee('Pytanie z głównej');
    }

    public function test_zgloszenia_formularza_sa_osobne_per_witryna(): void
    {
        $form = FormDefinition::withoutGlobalScopes()->create(['title' => 'Kontakt', 'slug' => 'kontakt', 'is_active' => true, 'fields' => [], 'settings' => []]);
        FormSubmission::create(['form_definition_id' => $form->id, 'data' => ['a' => 1], 'site_id' => $this->main->id]);
        FormSubmission::create(['form_definition_id' => $form->id, 'data' => ['a' => 2], 'site_id' => $this->sub->id]);
        FormSubmission::create(['form_definition_id' => $form->id, 'data' => ['a' => 3], 'site_id' => $this->sub->id]);

        $this->actingAs($this->admin())->withSession(['admin_site_chosen' => true]);
        $this->get(route('admin.formularze.zgloszenia', $form))->assertOk();

        $this->assertSame(1, FormSubmission::forCurrentSite()->count());
        $this->assertSame(2, FormSubmission::where('site_id', $this->sub->id)->count());
        $this->assertSame(0, FormSubmission::where('site_id', $this->sub->id)->whereNotNull('read_at')->count(), 'odczyt w głównej nie oznacza zgłoszeń sub jako przeczytanych');
    }

    public function test_ten_sam_email_moze_zapisac_sie_w_kazdej_witrynie(): void
    {
        MaterialSubscriber::create(['site_id' => $this->main->id, 'email' => 'a@example.test']);
        MaterialSubscriber::create(['site_id' => $this->sub->id, 'email' => 'a@example.test']);

        $this->assertSame(1, MaterialSubscriber::forCurrentSite()->count());
        $this->assertSame(2, MaterialSubscriber::count());
    }
}
