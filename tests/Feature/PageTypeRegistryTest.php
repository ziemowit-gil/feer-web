<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\PageTypes\PageType;
use App\PageTypes\PageTypeRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Rejestr typów stron (app/PageTypes): odtwarza dawne stałe Page::TYPES,
 * TYPE_GROUPS, TYPE_ICONS, TYPE_DATA_TYPES, a nowy typ = jedna klasa + widoki,
 * bez zmian w modelu Page, kontrolerze i widokach wspólnych.
 */
class PageTypeRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    protected function tearDown(): void
    {
        PageTypeRegistry::reset();
        parent::tearDown();
    }

    public function test_registry_reproduces_previous_type_constants(): void
    {
        $this->assertSame([
            'standard' => 'Standardowa',
            'event' => 'Wydarzenie',
            'schedule' => 'Harmonogram zajęć / spotkań',
            'about' => 'O organizacji',
            'faq' => 'FAQ (pytania i odpowiedzi)',
            'training_institution' => 'Instytucja szkoleniowa',
            'bip_move' => 'Przeniesiono do BIP',
            'internal' => 'Wewnętrzna (dostęp ograniczony)',
            'internal_hub' => 'Strefa współpracownika (wewnętrzny panel: komunikaty i odnośniki)',
            'links_hub' => 'Strona z kafelkami — metro (publiczne linki do działów)',
            'tiles_grid' => 'Siatka kafelków + treść (kafelki z ikonami, treść nad lub pod nimi, boczne menu)',
            'wspolpraca' => 'Współpraca z FEER (partnerstwo, sektory, formy, CTA)',
            'legacy' => 'Prezentacja tego, co było',
            'brand_assets' => 'Marka — identyfikacja wizualna (pliki do pobrania)',
            'about_person' => 'O organizacji — osoba',
            'contact' => 'Kontakt (formularz, dane teleadresowe, spotkania)',
            'form_page' => 'Formularz (wstęp, formularz, co przygotować, co dalej)',
            'service' => 'Oferta / usługa (korzyści, dla kogo, jak działamy, CTA)',
            'guide' => 'Poradnik krok po kroku (numerowane kroki, wymagania, podsumowanie)',
            'glossary' => 'Słownik pojęć (hasła z definicjami i indeksem liter)',
            'case_study' => 'Studium przypadku (wyzwanie, rozwiązanie, efekty, cytat)',
            'pricing' => 'Cennik i opłaty (karty cen, zasady płatności)',
            'team' => 'Zespół (osoby z rolą i krótkim opisem)',
            'documents' => 'Dokumenty (grupy odnośników do plików i stron)',
            'regulation' => 'Regulamin lub dokument (data obowiązywania, spis treści)',
            'redirect' => 'Przekierowanie (strona przenosi na inny adres)',
            'reports' => 'Sprawozdania roczne (tabela z modułu Sprawozdania — np. do BIP)',
            'partners' => 'Partnerzy i sponsorzy (logotypy w grupach, z modułu Partnerzy lub własne)',
            'recruitment' => 'Rekrutacja do projektu (status naboru, terminy, kryteria, dokumenty, zgłoszenie)',
            'tender' => 'Zapytanie ofertowe (zasada konkurencyjności: termin ofert, warunki, kryteria, zmiany, wynik)',
        ], Page::types());

        $this->assertSame([
            'Treść i informacja' => ['standard', 'faq', 'glossary', 'guide', 'documents', 'regulation'],
            'Oferta i edukacja' => ['service', 'pricing', 'case_study', 'training_institution', 'recruitment'],
            'Organizacja' => ['about', 'about_person', 'team', 'wspolpraca', 'legacy', 'brand_assets', 'reports', 'partners', 'tender'],
            'Kafelki i nawigacja' => ['links_hub', 'tiles_grid'],
            'Kontakt i wydarzenia' => ['contact', 'form_page', 'event', 'schedule'],
            'Wewnętrzne i przekierowania' => ['internal', 'internal_hub', 'bip_move', 'redirect'],
        ], Page::typeGroups());

        // Dawne TYPE_ICONS + ikona instytucji szkoleniowej (wcześniej tylko w drzewie stron).
        $icons = Page::typeIcons();
        ksort($icons);
        $expected = [
            'pricing' => 'fa-coins', 'team' => 'fa-people-group', 'documents' => 'fa-folder-open', 'regulation' => 'fa-scale-balanced', 'redirect' => 'fa-share', 'reports' => 'fa-file-invoice', 'partners' => 'fa-handshake',
            'recruitment' => 'fa-user-plus', 'tender' => 'fa-file-signature', 'service' => 'fa-briefcase', 'guide' => 'fa-list-ol', 'glossary' => 'fa-book', 'case_study' => 'fa-chart-line',
            'faq' => 'fa-circle-question', 'event' => 'fa-calendar', 'schedule' => 'fa-calendar-days', 'links_hub' => 'fa-table-cells-large',
            'tiles_grid' => 'fa-table-cells', 'internal' => 'fa-lock', 'internal_hub' => 'fa-user-lock', 'bip_move' => 'fa-landmark',
            'about' => 'fa-building', 'contact' => 'fa-envelope', 'wspolpraca' => 'fa-handshake', 'brand_assets' => 'fa-palette', 'legacy' => 'fa-clock-rotate-left',
            'training_institution' => 'fa-graduation-cap', 'form_page' => 'fa-pen-to-square',
        ];
        ksort($expected);
        $this->assertSame($expected, $icons);

        $this->assertSame(
            ['form_page', 'service', 'guide', 'glossary', 'case_study', 'pricing', 'team', 'documents', 'regulation', 'redirect', 'reports', 'partners', 'recruitment', 'tender'],
            Page::typeDataTypes(),
        );

        // Układ standardowy mają tylko strona standardowa i wewnętrzna (oraz typy spoza rejestru).
        $standard = array_keys(array_filter(PageTypeRegistry::all(), fn (PageType $t) => $t->standardLayout()));
        $this->assertSame(['standard', 'internal'], $standard);
        $this->assertTrue((new Page(['type' => 'nieznany']))->usesStandardLayout());

        $listKeys = PageTypeRegistry::listKeys();
        sort($listKeys);
        $this->assertSame(['audience', 'benefits', 'bonus', 'criteria', 'docs', 'members', 'notices', 'partner_groups', 'partners', 'price_rows', 'rec_faq', 'requirements', 'results', 'steps', 'terms', 'versions'], $listKeys);

        // Każdy typ `type_data` ma widok publiczny i sekcję pól w panelu, które istnieją.
        foreach (PageTypeRegistry::all() as $key => $type) {
            if ($type->usesTypeData()) {
                $this->assertTrue(View::exists($type->frontView()), "Brak widoku publicznego typu {$key}");
                $this->assertTrue(View::exists($type->adminView()), "Brak sekcji pól typu {$key}");
                $this->assertNotEmpty($type->rules(), "Typ {$key} nie ma reguł type_data");
            } else {
                $this->assertNull($type->frontView());
                $this->assertNull($type->adminView());
            }
        }
    }

    public function test_new_type_is_a_single_class_without_touching_page_or_controller(): void
    {
        View::addNamespace('pagetype-fixture', base_path('tests/fixtures/page-types/views'));
        PageTypeRegistry::register(new class extends PageType
        {
            public function key(): string { return 'fixture_type'; }

            public function label(): string { return 'Typ testowy (tylko w testach)'; }

            public function group(): string { return 'Treść i informacja'; }

            public function order(): int { return 1000; }

            public function icon(): ?string { return 'fa-flask'; }

            public function usesTypeData(): bool { return true; }

            public function rules(): array
            {
                return [
                    'type_data.fixture_note' => ['nullable', 'string', 'max:20'],
                    'type_data.fixture_rows' => ['nullable', 'array'],
                    'type_data.fixture_rows.*.text' => ['nullable', 'string', 'max:100'],
                ];
            }

            public function listKeys(): array { return ['fixture_rows']; }

            public function frontView(): ?string { return 'pagetype-fixture::front'; }

            public function adminView(): ?string { return 'pagetype-fixture::admin'; }
        });

        $this->assertSame('Typ testowy (tylko w testach)', Page::types()['fixture_type']);
        $this->assertContains('fixture_type', Page::typeGroups()['Treść i informacja']);
        $this->assertContains('fixture_type', Page::typeDataTypes());
        $this->assertSame('fa-flask', Page::typeIcons()['fixture_type']);

        $page = Page::create(['title' => 'Strona testowa', 'slug' => 'typ-testowy', 'type' => 'fixture_type', 'is_published' => true]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Panel: typ w wyborze typu i sekcja pól typu w formularzu.
        $this->actingAs($admin)->get(route('admin.podstrony.edit', $page))->assertOk()
            ->assertSee('<option value="fixture_type" selected>', false)
            ->assertSee('data-type-fields="fixture_type"', false)
            ->assertSee('name="type_data[fixture_note]"', false)
            ->assertSee('name="type_data[fixture_rows][__INDEX__][text]"', false);

        // Zapis: reguły typu walidują, pola zapisują się w type_data, pola spoza reguł odpadają.
        $payload = ['title' => 'Strona testowa', 'slug' => 'typ-testowy', 'type' => 'fixture_type', 'parent_id' => '', 'is_published' => '1'];
        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), $payload + [
            'type_data' => ['fixture_note' => str_repeat('x', 21)],
        ])->assertSessionHasErrors('type_data.fixture_note');

        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), $payload + [
            'type_data' => [
                'fixture_note' => 'Działa bez zmian',
                'fixture_rows' => [['text' => 'Pierwszy'], ['text' => '  ']],
                'undeclared' => 'odrzucone',
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $page->refresh();
        $this->assertSame('Działa bez zmian', $page->type_data['fixture_note']);
        $this->assertArrayNotHasKey('undeclared', $page->type_data);
        // listKeys(): puste wiersze usuwa Page::typeData().
        $this->assertSame([['text' => 'Pierwszy']], $page->typeData()['fixture_rows']);
        $this->assertFalse($page->usesStandardLayout());

        // Strona publiczna: widok wskazany przez frontView().
        auth()->logout();
        $this->get('/typ-testowy')->assertOk()
            ->assertSee('data-fixture-front', false)
            ->assertSee('Notatka: Działa bez zmian')
            ->assertSee('Wiersze: 1');
    }
}
