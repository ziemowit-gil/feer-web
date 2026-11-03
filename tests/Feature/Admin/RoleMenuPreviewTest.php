<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use App\Models\UserGroup;
use App\Support\AdminMenu;
use App\Support\RoleMenuPreview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Użytkownicy → „Podgląd menu ról”: menu panelu roli, grupy edytora albo użytkownika, bez logowania na jego konto. */
class RoleMenuPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function actingAdmin(): static
    {
        return $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))->withSession(['admin_site_chosen' => true]);
    }

    private function group(array $modules = [], string $name = 'Redakcja'): UserGroup
    {
        return UserGroup::create(['name' => $name, 'modules' => $modules]);
    }

    /** @return list<string> */
    private function labels(array $preview): array
    {
        return collect($preview['groups'])->flatMap(fn ($g) => $g['blocks'])->flatMap(fn ($b) => $b['items'])->pluck('label')->all();
    }

    private function html(string $choice): string
    {
        return $this->get(route('admin.uzytkownicy.podglad-menu', ['jako' => $choice]))->assertOk()->getContent();
    }

    public function test_wymaga_zalogowanego_administratora(): void
    {
        $this->get(route('admin.uzytkownicy.podglad-menu'))->assertRedirect();

        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);
        $this->actingAs($editor)->get(route('admin.uzytkownicy.podglad-menu'))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.uzytkownicy.podglad-menu', ['jako' => 'rola:admin']))->assertForbidden();
    }

    public function test_bez_wyboru_pokazuje_formularz_z_rolami_grupami_i_uzytkownikami(): void
    {
        $this->group(['news'], 'Redakcja');
        User::factory()->create(['name' => 'Anna Kowalska', 'role' => User::ROLE_COORDINATOR]);

        $html = $this->actingAdmin()->get(route('admin.uzytkownicy.podglad-menu'))->assertOk()->getContent();

        // Pole z etykietą powiązaną (WCAG), grupy opcji i podpowiedź zamiast pustego wyniku.
        $this->assertStringContainsString('<label for="jako"', $html);
        $this->assertStringContainsString('<select id="jako" name="jako"', $html);
        $this->assertStringContainsString('<optgroup label="Role">', $html);
        $this->assertStringContainsString('<optgroup label="Użytkownicy">', $html);
        foreach (User::ROLES as $role => $label) {
            if ($role !== User::ROLE_EDITOR) {
                $this->assertStringContainsString('value="rola:'.$role.'"', $html, $role);
            }
        }
        // Edytor z grupy wybiera się przez grupę, bo to ona decyduje o modułach.
        $this->assertStringNotContainsString('value="rola:editor"', $html);
        $this->assertStringContainsString('Edytor (grupy): Redakcja', $html);
        $this->assertStringContainsString('Anna Kowalska — Koordynator', $html);
        $this->assertStringContainsString('Wybierz, czyje menu chcesz zobaczyć', $html);
        $this->assertStringNotContainsString('id="h-podglad"', $html);
    }

    /** @return array<string, array{0: string, 1: list<string>}> */
    public static function rolesAndGroups(): array
    {
        return [
            'administrator' => [User::ROLE_ADMIN, ['start', 'content', 'activity', 'comms', 'docs', 'settings', 'system']],
            'edytor treści' => [User::ROLE_CONTENT_EDITOR, ['start', 'content', 'activity', 'comms', 'docs']],
            'koordynator' => [User::ROLE_COORDINATOR, ['start', 'activity', 'content']],
            'redaktor PR' => [User::ROLE_PR_EDITOR, ['start', 'content', 'comms', 'activity']],
            'edytor BIP+' => [User::ROLE_BIP_EDITOR_PLUS, ['start', 'docs', 'library']],
            'edytor BIP' => [User::ROLE_BIP_EDITOR, ['start', 'docs', 'library']],
            'redaktor newslettera' => [User::ROLE_NEWSLETTER_EDITOR, ['start', 'comms', 'library']],
            'wydawca newslettera' => [User::ROLE_NEWSLETTER_PUBLISHER, ['start', 'comms', 'library']],
        ];
    }

    #[DataProvider('rolesAndGroups')]
    public function test_rola_pokazuje_grupy_menu_w_kolejnosci_panelu(string $role, array $expectedGroups): void
    {
        $html = $this->actingAdmin()->get(route('admin.uzytkownicy.podglad-menu', ['jako' => 'rola:'.$role]))->assertOk()->getContent();

        $this->assertStringContainsString('Menu: '.User::ROLES[$role], $html);
        preg_match_all('/id="podglad-grupa-([a-z_]+)"/', $html, $found);
        $this->assertSame($expectedGroups, $found[1], 'Grupy mają być w kolejności panelu tej roli.');
    }

    public function test_koordynator_ma_obszar_roboczy_zaraz_po_starcie_i_nie_widzi_administracji(): void
    {
        $html = $this->actingAdmin()->get(route('admin.uzytkownicy.podglad-menu', ['jako' => 'rola:'.User::ROLE_COORDINATOR]))->assertOk()->getContent();

        // Obszar roboczy tylko przy grupie Działalność.
        $this->assertSame(1, substr_count($html, 'obszar roboczy tej roli'));
        $this->assertMatchesRegularExpression('#id="podglad-grupa-activity".{0,400}obszar roboczy tej roli#s', $html);

        $preview = RoleMenuPreview::build(RoleMenuPreview::subject('rola:'.User::ROLE_COORDINATOR));
        $hidden = collect($preview['hidden'])->pluck('items', 'label');
        $this->assertTrue($hidden->has('Ustawienia') && $hidden->has('System'), 'Administracja jest tylko dla administratora.');
        $this->assertContains('Użytkownicy', $hidden['System']);
        $this->assertStringContainsString('Czego nie widzi ('.($preview['total'] - $preview['visible']).')', $html);
        $this->assertGreaterThan(0, $preview['visible']);
        $this->assertLessThan($preview['total'], $preview['visible']);
    }

    public function test_administrator_widzi_cale_menu(): void
    {
        $html = $this->actingAdmin()->get(route('admin.uzytkownicy.podglad-menu', ['jako' => 'rola:admin']))->assertOk()->getContent();

        $preview = RoleMenuPreview::build(RoleMenuPreview::subject('rola:admin'));
        $this->assertSame($preview['total'], $preview['visible']);
        $this->assertSame([], $preview['hidden']);
        $this->assertStringContainsString('Niczego: ta osoba widzi całe menu.', $html);
        $this->assertStringNotContainsString('Czego nie widzi (', $html);
    }

    public function test_edytor_z_grupy_widzi_tylko_moduly_zaznaczone_w_grupie(): void
    {
        $withNews = $this->group(['news'], 'Tylko aktualności');
        $without = $this->group([], 'Bez modułów');

        $news = RoleMenuPreview::build(RoleMenuPreview::subject('grupa:'.$withNews->id));
        $none = RoleMenuPreview::build(RoleMenuPreview::subject('grupa:'.$without->id));

        $this->assertContains('Aktualności', $this->labels($news));
        $this->assertNotContains('Aktualności', $this->labels($none));
        $this->assertContains('Aktualności', collect($none['hidden'])->flatMap(fn ($h) => $h['items'])->all(), 'Czego nie widzi grupa bez modułu.');

        $this->actingAdmin();
        $this->assertStringContainsString('Menu: Edytor (grupy): Tylko aktualności', $this->html('grupa:'.$withNews->id));
    }

    public function test_podglad_uzytkownika_zgadza_sie_z_jego_prawdziwym_menu(): void
    {
        $user = User::factory()->create(['name' => 'Jan Redaktor', 'role' => User::ROLE_PR_EDITOR]);

        $html = $this->actingAdmin()->get(route('admin.uzytkownicy.podglad-menu', ['jako' => 'uzytkownik:'.$user->id]))->assertOk()->getContent();

        $real = collect(AdminMenu::for($user->fresh()))->flatMap(fn ($g) => $g['blocks'])->flatMap(fn ($b) => $b['items'])->pluck('label')->all();
        $this->assertSame($real, $this->labels(RoleMenuPreview::build($user->fresh())));
        $this->assertStringContainsString('Menu: Jan Redaktor (Redaktor PR)', $html);
        $this->assertStringContainsString(route('admin.uzytkownicy.edit', $user), $html);
    }

    public function test_podglad_roli_podaje_osoby_z_ta_rola_jako_odnosniki_do_ich_podgladu(): void
    {
        $anna = User::factory()->create(['name' => 'Anna Koordynatorka', 'role' => User::ROLE_COORDINATOR]);
        User::factory()->create(['name' => 'Ktoś z PR', 'role' => User::ROLE_PR_EDITOR]);

        $html = $this->actingAdmin()->get(route('admin.uzytkownicy.podglad-menu', ['jako' => 'rola:coordinator']))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#Osoby z tą rolą:(.*?)</p>#s', $html);
        preg_match('#Osoby z tą rolą:(.*?)</p>#s', $html, $members);
        $this->assertStringContainsString(route('admin.uzytkownicy.podglad-menu', ['jako' => 'uzytkownik:'.$anna->id]), $members[1]);
        $this->assertStringContainsString('Anna Koordynatorka', $members[1]);
        $this->assertStringNotContainsString('Ktoś z PR', $members[1], 'Lista osób to tylko osoby z tą rolą (inni są tylko na liście wyboru).');

        $this->get(route('admin.uzytkownicy.podglad-menu', ['jako' => 'rola:bip_editor']))->assertOk()->assertSee('Nikt nie ma teraz tej roli.');
    }

    public function test_nieznany_wybor_to_404(): void
    {
        $this->actingAdmin();

        foreach (['rola:nie-ma', 'rola:', 'uzytkownik:999999', 'uzytkownik:abc', 'grupa:999999', 'grupa:1; drop', 'cos:1', 'bez-dwukropka'] as $choice) {
            $this->get(route('admin.uzytkownicy.podglad-menu', ['jako' => $choice]))->assertNotFound();
        }
    }

    public function test_podglad_niczego_nie_zmienia_a_zalogowany_zostaje_soba(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $other = User::factory()->create(['role' => User::ROLE_COORDINATOR]);
        $users = User::query()->count();

        $this->actingAs($admin)->withSession(['admin_site_chosen' => true]);
        foreach (['rola:coordinator', 'rola:editor', 'uzytkownik:'.$other->id] as $choice) {
            $this->get(route('admin.uzytkownicy.podglad-menu', ['jako' => $choice]))->assertOk();
        }

        $this->assertAuthenticatedAs($admin);
        $this->assertSame($users, User::query()->count(), 'Podgląd roli nie tworzy kont.');
        $this->assertSame(User::ROLE_COORDINATOR, $other->fresh()->role);
    }

    public function test_podglad_roli_i_grupy_jest_taki_sam_jak_menu_prawdziwego_uzytkownika(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $cases = [];
        foreach (array_keys(User::ROLES) as $role) {
            if ($role !== User::ROLE_EDITOR) {
                $cases['rola:'.$role] = [$role, null];
            }
        }
        foreach ([[], ['news'], ['news', 'pages', 'events', 'faq'], array_keys(SiteSetting::MODULES)] as $i => $modules) {
            $group = $this->group($modules, 'Grupa '.$i);
            $cases['grupa:'.$group->id] = [User::ROLE_EDITOR, $group];
        }

        $shape = fn (array $groups) => collect($groups)->map(fn ($g) => [
            $g['key'], $g['label'], collect($g['blocks'])->map(fn ($b) => [$b['heading'], collect($b['items'])->pluck('key')->all()])->all(),
        ])->all();

        foreach ($cases as $choice => [$role, $group]) {
            // Menu prawdziwego użytkownika liczone, gdy to on jest zalogowany (tak jak w jego panelu)…
            $real = User::factory()->create(['role' => $role, 'user_group_id' => $group?->id]);
            $this->actingAs($real);
            $expected = $shape(AdminMenu::for($real->fresh()));

            // …a podgląd — gdy zalogowany jest administrator, bez konta w bazie.
            $this->actingAs($admin);
            $preview = RoleMenuPreview::build(RoleMenuPreview::subject($choice));
            $actual = collect($preview['groups'])->map(fn ($g) => [
                $g['key'], $g['label'], collect($g['blocks'])->map(fn ($b) => [$b['heading'], collect($b['items'])->pluck('key')->all()])->all(),
            ])->all();

            $this->assertSame($expected, $actual, 'Podgląd „'.$choice.'” różni się od menu prawdziwego użytkownika.');
        }
    }

    public function test_edytor_bez_grupy_ma_podstawowe_menu(): void
    {
        $preview = RoleMenuPreview::build(RoleMenuPreview::subject('rola:editor'));

        $this->assertNotEmpty($preview['groups']);
        $this->assertSame('start', $preview['groups'][0]['key']);
        $this->assertNotContains('Aktualności', $this->labels($preview));
    }

    public function test_odnosniki_do_podgladu_sa_w_liscie_uzytkownikow_i_w_macierzy_uprawnien(): void
    {
        $user = User::factory()->create(['name' => 'Ewa Test', 'role' => User::ROLE_COORDINATOR]);
        $this->actingAdmin();

        $index = $this->get(route('admin.uzytkownicy.index'))->assertOk()->getContent();
        $this->assertStringContainsString(route('admin.uzytkownicy.podglad-menu'), $index);
        $this->assertStringContainsString('Podgląd menu ról', $index);
        $this->assertStringContainsString(route('admin.uzytkownicy.podglad-menu', ['jako' => 'uzytkownik:'.$user->id]), $index);
        $this->assertStringContainsString('Podgląd menu: Ewa Test</span>', $index, 'Ikona ma nazwę dla czytnika ekranu.');

        $this->get(route('admin.uzytkownicy.uprawnienia'))->assertOk()
            ->assertSee(route('admin.uzytkownicy.podglad-menu'), false)
            ->assertSee('podglądzie menu ról');
    }

    public function test_struktura_podgladu_jest_dostepna_naglowki_listy_i_ikony_dekoracyjne(): void
    {
        $html = $this->actingAdmin()->get(route('admin.uzytkownicy.podglad-menu', ['jako' => 'rola:coordinator']))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#<h2 id="h-podglad"#', $html);
        $this->assertMatchesRegularExpression('#<section aria-labelledby="h-podglad"#', $html);
        $this->assertMatchesRegularExpression('#<section aria-labelledby="podglad-grupa-start"#', $html);
        $this->assertStringContainsString('<summary class="flex min-h-11', $html);
        // Każda ikona w podglądzie jest dekoracyjna.
        preg_match_all('/<i class="fa-solid [^"]*"(?![^>]*aria-hidden)[^>]*>/', substr($html, (int) strpos($html, 'id="h-podglad"')), $noHidden);
        $this->assertSame([], $noHidden[0]);
    }
}
