<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\UserGroup;
use App\Support\AdminMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Menu boczne: grupy to obszary pracy, każda pozycja ma miejsce, a rola widzi swoje obszary — własny zaraz po „Starcie”. */
class AdminMenuRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function user(string $role, array $modules = []): User
    {
        $group = $role === User::ROLE_EDITOR ? UserGroup::create(['name' => 'Redakcja', 'modules' => $modules]) : null;

        return User::factory()->create(['role' => $role, 'user_group_id' => $group?->id]);
    }

    /** @return array<string, array<string, mixed>> grupy menu po kluczu */
    private function menuGroups(User $user): array
    {
        $this->actingAs($user);

        return collect(AdminMenu::for($user->fresh()))->keyBy('key')->all();
    }

    /** @return array<int, string> */
    private function itemKeys(array $groups): array
    {
        return collect($groups)->flatMap(fn ($g) => $g['blocks'])->flatMap(fn ($b) => $b['items'])->pluck('key')->all();
    }

    public function test_zadna_pozycja_nie_ginie_z_menu(): void
    {
        // Pozycje „Portale”, „Jakość tekstów” i „Serwer MCP” były zdefiniowane, ale po przebudowie menu nie miały miejsca.
        $this->actingAs($admin = $this->user(User::ROLE_ADMIN));

        $this->assertSame([], AdminMenu::unplacedItemKeys($admin), 'Pozycja z sekcji bez miejsca w AdminMenu::layout() nie pokaże się w menu.');
    }

    public function test_pozycje_sa_w_obszarach_do_ktorych_naleza(): void
    {
        $groups = $this->menuGroups($this->user(User::ROLE_ADMIN));
        $blockOf = function (string $group, string $item) use ($groups): ?string {
            foreach ($groups[$group]['blocks'] as $block) {
                if (in_array($item, array_column($block['items'], 'key'), true)) {
                    return $block['heading'];
                }
            }

            return null;
        };

        $this->assertSame('Strony i nawigacja', $blockOf('content', 'nav'));
        $this->assertSame('Strona główna', $blockOf('content', 'hero'));
        $this->assertSame('Kontrola jakości', $blockOf('content', 'text_quality'));
        $this->assertSame('Działania', $blockOf('activity', 'portals'));
        $this->assertSame('Szkolenia i oferta', $blockOf('activity', 'shop'));
        // Landing pages, kampanie zbiórkowe i bannery to jedna promocja — nie rozrzucone po Treściach i Komunikacji.
        foreach (['landing', 'campaigns', 'banners'] as $promo) {
            $this->assertSame('Promocja i kampanie', $blockOf('comms', $promo));
        }
        $this->assertSame('Funkcje i integracje', $blockOf('settings', 'templates'));
        $this->assertSame('Moduły i integracje', $blockOf('system', 'mcp'));
        $this->assertSame('Moduły i integracje', $blockOf('system', 'modules'));
    }

    public function test_administrator_ma_najwyzej_siedem_grup_o_odrebnych_nazwach(): void
    {
        $groups = array_values($this->menuGroups($this->user(User::ROLE_ADMIN)));

        $this->assertLessThanOrEqual(7, count($groups));
        $labels = array_column($groups, 'label');
        $this->assertSame($labels, array_values(array_unique($labels)));
        $this->assertSame(['Start', 'Treści', 'Działalność', 'Komunikacja', 'Dokumenty i BIP', 'Ustawienia', 'System'], $labels);
    }

    /** @return array<string, array{0: string, 1: array<int, string>, 2: array<int, string>}> */
    public static function rolesAndAreas(): array
    {
        return [
            'edytor treści' => [User::ROLE_CONTENT_EDITOR, [], ['start', 'content', 'activity', 'comms', 'docs']],
            'koordynator zaczyna od Działalności' => [User::ROLE_COORDINATOR, [], ['start', 'activity', 'content']],
            'redaktor PR zaczyna od Treści i Komunikacji' => [User::ROLE_PR_EDITOR, [], ['start', 'content', 'comms', 'activity']],
            'BIP+ zaczyna od BIP' => [User::ROLE_BIP_EDITOR_PLUS, [], ['start', 'docs', 'library']],
            'BIP zaczyna od BIP' => [User::ROLE_BIP_EDITOR, [], ['start', 'docs', 'library']],
            'redaktor newslettera zaczyna od Komunikacji' => [User::ROLE_NEWSLETTER_EDITOR, [], ['start', 'comms', 'library']],
            'edytor z grupy ma kolejność domyślną' => [User::ROLE_EDITOR, ['news', 'pages', 'events', 'faq'], ['start', 'content', 'activity']],
        ];
    }

    #[DataProvider('rolesAndAreas')]
    public function test_rola_widzi_swoje_obszary_a_wlasny_zaraz_po_starcie(string $role, array $modules, array $expected): void
    {
        $groups = $this->menuGroups($this->user($role, $modules));

        $this->assertSame($expected, array_keys($groups));
        // Administracja (Ustawienia, System) tylko dla administratora.
        $this->assertArrayNotHasKey('settings', $groups);
        $this->assertArrayNotHasKey('system', $groups);
    }

    public function test_grupa_samych_plikow_nazywa_sie_pliki_a_nie_tresci(): void
    {
        foreach ([User::ROLE_BIP_EDITOR, User::ROLE_NEWSLETTER_EDITOR] as $role) {
            $groups = $this->menuGroups($this->user($role));

            $this->assertArrayNotHasKey('content', $groups, $role);
            $this->assertSame('Pliki', $groups['library']['label'], $role);
            $this->assertSame('fa-photo-film', $groups['library']['icon'], $role);
            $this->assertNull($groups['library']['blocks'][0]['heading'], 'Nagłówek bloku powtarzałby nazwę grupy.');
            $this->assertContains('media', $this->itemKeys($groups), $role);
        }

        // Rola z treściami ma „Pliki” jako blok w Treściach.
        $content = $this->menuGroups($this->user(User::ROLE_PR_EDITOR))['content'];
        $this->assertContains('Pliki', array_column($content['blocks'], 'heading'));
    }

    public function test_kosz_tylko_dla_rol_ktore_maja_w_nim_tresci(): void
    {
        // Kosz zbiera strony, aktualności, działania i dokumenty BIP — koordynator i redaktor newslettera mieliby go zawsze pustego.
        foreach ([User::ROLE_COORDINATOR, User::ROLE_NEWSLETTER_EDITOR] as $role) {
            $this->assertNotContains('trash', $this->itemKeys($this->menuGroups($this->user($role))), $role);
        }
        foreach ([User::ROLE_ADMIN, User::ROLE_CONTENT_EDITOR, User::ROLE_PR_EDITOR, User::ROLE_BIP_EDITOR] as $role) {
            $this->assertContains('trash', $this->itemKeys($this->menuGroups($this->user($role))), $role);
        }
    }

    public function test_licznik_grupy_nie_wlicza_licznikow_informacyjnych(): void
    {
        Page::create(['title' => 'Stara', 'slug' => 'stara', 'type' => 'standard', 'is_published' => false])->delete();

        $groups = $this->menuGroups($this->user(User::ROLE_ADMIN));
        $trash = collect($groups['content']['blocks'])->flatMap(fn ($b) => $b['items'])->firstWhere('key', 'trash');

        $this->assertSame(1, $trash['badge']);
        $this->assertSame('muted', $trash['badge_tone']);
        // Kosz to informacja, nie sprawa „do obsłużenia” — nie podbija licznika grupy Treści.
        $this->assertNull($groups['content']['badge']);
    }

    public function test_menu_renderuje_sie_dla_kazdej_roli(): void
    {
        foreach (self::rolesAndAreas() as [$role, $modules]) {
            $this->actingAs($this->user($role, $modules))->get(route('admin.dashboard'))->assertOk()
                ->assertSee('aria-label="Menu panelu"', false);
        }
    }
}
