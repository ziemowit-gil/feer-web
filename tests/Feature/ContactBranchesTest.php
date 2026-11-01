<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Sekcja „Oddziały” na stronie kontaktowej (np. oddział bez osobowości prawnej). */
class ContactBranchesTest extends TestCase
{
    use RefreshDatabase;

    private function settings(): SiteSetting
    {
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();

        return SiteSetting::current();
    }

    public function test_oddzialy_pokazuja_sie_na_kontakcie_w_kazdym_ukladzie_a_bez_nich_sekcji_nie_ma(): void
    {
        $s = $this->settings();
        $this->get('/kontakt')->assertOk()->assertDontSee('id="oddzialy"', false);

        $s->forceFill(['site_template' => 'feer', 'contact_branches' => [
            ['name' => 'Oddział w Nowym Sączu', 'status' => 'Oddział bez osobowości prawnej, działa w strukturze Fundacji', 'address' => 'ul. Przykładowa 1', 'city' => '33-300 Nowy Sącz', 'email' => 'ns@example.test', 'phone' => '600 000 000', 'hours' => 'wtorki 10:00 do 14:00', 'note' => 'Spotkania po umówieniu.'],
            ['name' => '', 'status' => 'pusty wiersz'],
        ]])->save();

        foreach (['feer', 'default'] as $template) {
            $s->forceFill(['site_template' => $template])->save();
            $this->get('/kontakt')->assertOk()
                ->assertSee('Oddziały')->assertSee('Oddział w Nowym Sączu')->assertSee('bez osobowości prawnej')
                ->assertSee('33-300 Nowy Sącz')->assertSee('ns@example.test')->assertSee('wtorki 10:00 do 14:00')->assertDontSee('pusty wiersz');
        }
        $this->assertCount(1, $s->fresh()->contactBranches(), 'wiersz bez nazwy jest pomijany');
    }

    public function test_oddzialy_zapisuja_sie_z_formularza_strony_kontakt(): void
    {
        $this->settings();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $page = \App\Models\Page::withoutGlobalScopes()->firstOrCreate(['slug' => 'kontakt-2'], ['title' => 'Kontakt', 'type' => 'contact', 'is_published' => true, 'content' => '']);
        $page->forceFill(['type' => 'contact'])->save();

        $this->actingAs($admin)->withSession(['admin_site_chosen' => true])->put(route('admin.podstrony.update', $page), [
            'title' => 'Kontakt', 'slug' => $page->slug, 'type' => 'contact', 'content' => '', 'is_published' => 1, 'parent_id' => null,
            'contact_address' => 'ul. Testowa 1', 'contact_city' => '00-001 Warszawa', 'contact_email' => 'kontakt@example.pl',
            'contact_branches_title' => 'Nasze oddziały',
            'contact_branches' => [['name' => ' Oddział Kraków ', 'status' => 'oddział bez osobowości prawnej', 'email' => 'krakow@example.pl'], ['name' => '', 'email' => '']],
        ])->assertSessionHasNoErrors();

        $fresh = SiteSetting::query()->find(SiteSetting::current()->id);
        $this->assertSame('Nasze oddziały', $fresh->contact_branches_title);
        $this->assertCount(1, $fresh->contact_branches);
        $this->assertSame('Oddział Kraków', $fresh->contact_branches[0]['name']);

        $this->get(route('admin.podstrony.edit', $page))->assertOk()->assertSee('Dodaj oddział');
    }
}
