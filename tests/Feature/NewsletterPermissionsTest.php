<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use App\Models\UserGroup;
use App\Support\AdminMenu;
use App\Support\NewsletterPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Newsletter\Models\NewsletterCampaign;
use Tests\TestCase;

/** Uprawnienia modułu Newsletter: role, grupy, trasy panelu (403 bez uprawnienia) i menu. */
class NewsletterPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function user(string $role, array $groupPermissions = []): User
    {
        $group = $groupPermissions ? UserGroup::create(['name' => 'Grupa testowa', 'modules' => $groupPermissions]) : null;

        return User::factory()->create(['role' => $role, 'user_group_id' => $group?->id]);
    }

    private function campaign(string $status = NewsletterCampaign::STATUS_DRAFT): NewsletterCampaign
    {
        return NewsletterCampaign::create([
            'site_id' => SiteSetting::current()->id, 'title' => 'Kampania testowa', 'subject' => 'Temat',
            'channels' => ['email'], 'audience' => ['all' => true], 'status' => $status,
        ]);
    }

    public function test_administrator_ma_wszystkie_uprawnienia(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);

        $this->assertSame(NewsletterPermissions::keys(), NewsletterPermissions::for($admin));
        $this->actingAs($admin)->get(route('admin.newsletter.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('admin.newsletter.subskrybenci.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.newsletter.ustawienia.edit'))->assertOk();
    }

    public function test_redaktor_newslettera_tworzy_kampanie_ale_nie_wysyla_i_nie_widzi_subskrybentow(): void
    {
        $editor = $this->user(User::ROLE_NEWSLETTER_EDITOR);
        $campaign = $this->campaign();

        $this->actingAs($editor)->get(route('admin.newsletter.dashboard'))->assertOk()
            ->assertSee(route('admin.newsletter.kampanie.create'), false)
            ->assertDontSee(route('admin.newsletter.subskrybenci.index'), false)
            ->assertDontSee(route('admin.newsletter.ustawienia.edit'), false);
        $this->actingAs($editor)->get(route('admin.newsletter.kampanie.create'))->assertOk();
        $this->actingAs($editor)->get(route('admin.newsletter.szablony.index'))->assertOk();
        $this->actingAs($editor)->get(route('admin.newsletter.kampanie.show', $campaign))->assertOk()
            ->assertDontSee(route('admin.newsletter.kampanie.send', $campaign), false)
            ->assertSee('Kampanie: wysyłka');

        $this->actingAs($editor)->post(route('admin.newsletter.kampanie.send', $campaign))->assertForbidden();
        $this->actingAs($editor)->post(route('admin.newsletter.kampanie.pause', $this->campaign(NewsletterCampaign::STATUS_SENDING)))->assertForbidden();
        $this->actingAs($editor)->post(route('admin.newsletter.kampanie.schedule', $campaign), ['scheduled_at' => now()->addDay()->format('Y-m-d\TH:i')])->assertForbidden();
        $this->actingAs($editor)->get(route('admin.newsletter.subskrybenci.index'))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.newsletter.subskrybenci.export'))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.newsletter.listy.index'))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.newsletter.segmenty.index'))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.newsletter.ustawienia.edit'))->assertForbidden();
        $this->assertSame(NewsletterCampaign::STATUS_DRAFT, $campaign->fresh()->status);
    }

    public function test_wydawca_newslettera_wysyla_i_zarzadza_subskrybentami(): void
    {
        $publisher = $this->user(User::ROLE_NEWSLETTER_PUBLISHER);
        $campaign = $this->campaign();

        $this->actingAs($publisher)->get(route('admin.newsletter.subskrybenci.index'))->assertOk();
        $this->actingAs($publisher)->get(route('admin.newsletter.listy.index'))->assertOk();
        $this->actingAs($publisher)->get(route('admin.newsletter.kampanie.show', $campaign))->assertOk()
            ->assertSee(route('admin.newsletter.kampanie.send', $campaign), false);
        $sending = $this->campaign(NewsletterCampaign::STATUS_SENDING);
        $this->actingAs($publisher)->post(route('admin.newsletter.kampanie.pause', $sending))->assertRedirect();
        $this->assertSame(NewsletterCampaign::STATUS_PAUSED, $sending->fresh()->status);
        $this->actingAs($publisher)->get(route('admin.newsletter.ustawienia.edit'))->assertForbidden();
    }

    public function test_grupa_z_samymi_raportami_widzi_raport_ale_nie_edytuje(): void
    {
        $user = $this->user(User::ROLE_EDITOR, [NewsletterPermissions::REPORTS]);
        $campaign = $this->campaign(NewsletterCampaign::STATUS_SENT);

        $this->assertTrue($user->hasNewsletterPermission(NewsletterPermissions::VIEW), 'Każde uprawnienie zawiera przeglądanie.');
        $this->actingAs($user)->get(route('admin.newsletter.dashboard'))->assertOk();
        $this->actingAs($user)->get(route('admin.newsletter.kampanie.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.newsletter.kampanie.report', $campaign))->assertOk();
        $this->actingAs($user)->get(route('admin.newsletter.kampanie.create'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.newsletter.kampanie.duplicate', $campaign))->assertForbidden();
        $this->actingAs($user)->get(route('admin.newsletter.szablony.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.newsletter.mosaico.gallery'))->assertForbidden();
    }

    public function test_bez_uprawnien_brak_dostepu_i_pozycji_w_menu(): void
    {
        foreach ([$this->user(User::ROLE_COORDINATOR), $this->user(User::ROLE_EDITOR, ['news'])] as $user) {
            $this->assertFalse($user->canAccessModule('newsletter'));
            $this->actingAs($user)->get(route('admin.newsletter.dashboard'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.newsletter.kampanie.index'))->assertForbidden();

            $items = collect(AdminMenu::for($user))->flatMap(fn ($g) => $g['blocks'])->flatMap(fn ($b) => $b['items'])->pluck('key');
            $this->assertNotContains('newsletter', $items);
        }

        $items = collect(AdminMenu::for($this->user(User::ROLE_NEWSLETTER_EDITOR)))->flatMap(fn ($g) => $g['blocks'])->flatMap(fn ($b) => $b['items'])->pluck('key');
        $this->assertContains('newsletter', $items);
        $this->assertNotContains('pages', $items);
    }

    public function test_grupa_zapisuje_uprawnienia_newslettera(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);

        $this->actingAs($admin)->get(route('admin.grupy.create'))->assertOk()->assertSee('Kampanie: wysyłka');
        $this->actingAs($admin)->post(route('admin.grupy.store'), [
            'name' => 'Newsletter i aktualności',
            'modules' => ['news', NewsletterPermissions::CAMPAIGNS, NewsletterPermissions::SEND],
        ])->assertRedirect(route('admin.grupy.index'));

        $group = UserGroup::where('name', 'Newsletter i aktualności')->firstOrFail();
        $this->assertSame(['news', 'newsletter.campaigns', 'newsletter.send'], $group->modules);

        $this->actingAs($admin)->post(route('admin.grupy.store'), ['name' => 'Zła', 'modules' => ['newsletter.wszystko']])
            ->assertSessionHasErrors('modules.0');
    }

    public function test_migracja_dodaje_gotowe_grupy(): void
    {
        $this->assertSame(
            NewsletterPermissions::ROLE_PRESETS[User::ROLE_NEWSLETTER_PUBLISHER],
            UserGroup::where('name', 'Wydawcy newslettera')->value('modules'),
        );
        $this->assertSame(
            NewsletterPermissions::ROLE_PRESETS[User::ROLE_NEWSLETTER_EDITOR],
            UserGroup::where('name', 'Redakcja newslettera')->value('modules'),
        );
    }
}
