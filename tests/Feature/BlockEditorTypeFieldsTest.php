<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Edytor blokowy: okno „Pola typu strony” — pola typu (wydarzenie, harmonogram,
 * FAQ, type_data…) edytowane i zapisywane razem z blokami.
 */
class BlockEditorTypeFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function page(array $attributes = []): Page
    {
        return Page::create($attributes + ['title' => 'Strona', 'slug' => 'strona', 'content' => '<p>Treść</p>', 'is_published' => true]);
    }

    private function save(Page $page, array $fields, array $extra = [])
    {
        return $this->actingAs($this->admin())->putJson(route('admin.podstrony.bloki.update', $page), $extra + [
            'title' => $page->title,
            'blocks' => [['type' => 'paragraph', 'attrs' => ['text' => 'Nowa treść']]],
            'fields' => $fields,
        ]);
    }

    public function test_editor_shows_type_fields_dialog_for_event_page(): void
    {
        $page = $this->page(['type' => 'event', 'event_when' => '12 marca 2026, 18:00']);

        $this->actingAs($this->admin())
            ->get(route('admin.podstrony.bloki.edit', $page))
            ->assertOk()
            ->assertSee('id="be-type-fields"', false)
            ->assertSee('Pola typu strony: Wydarzenie')
            ->assertSee('name="event_when"', false)
            ->assertSee('12 marca 2026, 18:00')
            ->assertSee('typeFields', false);
    }

    public function test_standard_page_has_no_type_fields_and_ignores_them(): void
    {
        $page = $this->page();

        $this->actingAs($this->admin())
            ->get(route('admin.podstrony.bloki.edit', $page))
            ->assertOk()
            ->assertDontSee('id="be-type-fields"', false);

        $this->save($page, ['event_when' => 'jutro'])->assertOk();
        $this->assertNull($page->fresh()->event_when);
    }

    public function test_saves_event_fields_with_blocks_and_keeps_other_settings(): void
    {
        $page = $this->page(['type' => 'event', 'show_in_menu' => true, 'meta_title' => 'SEO']);

        $this->save($page, [
            'event_mode' => array_key_first(Page::EVENT_MODES),
            'event_when' => '1 kwietnia, 10:00',
            'event_location' => 'Nowy Sącz',
            'event_how_to_join' => 'Zapisy mailowe',
            'event_registration_url' => 'https://example.org/zapisy',
        ])->assertOk();

        $page->refresh();
        $this->assertSame('1 kwietnia, 10:00', $page->event_when);
        $this->assertSame('Nowy Sącz', $page->event_location);
        $this->assertSame('https://example.org/zapisy', $page->event_registration_url);
        $this->assertStringContainsString('Nowa treść', $page->content);
        $this->assertTrue((bool) $page->show_in_menu);
        $this->assertSame('SEO', $page->meta_title);
    }

    public function test_invalid_type_field_returns_422_and_saves_nothing(): void
    {
        $page = $this->page(['type' => 'event']);

        $this->save($page, ['event_registration_url' => 'to nie adres'])
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_starts_with($m, 'Pola typu strony: '));

        $this->assertSame('<p>Treść</p>', $page->fresh()->content);
    }

    public function test_schedule_rows_are_normalized_like_in_the_form(): void
    {
        $page = $this->page(['type' => 'schedule']);

        $this->save($page, [
            'schedule_pending' => '1',
            'schedule_change_notice' => 'Zmiana terminu',
            'schedule_items' => [
                ['date' => '2026-11-03', 'time' => '10:00', 'location' => ' sala 12 ', 'note' => '', 'changed' => '1'],
                ['date' => '', 'time' => '', 'location' => '', 'note' => '', 'changed' => '0'],
            ],
        ])->assertOk();

        $page->refresh();
        $this->assertTrue((bool) $page->schedule_pending);
        $this->assertSame('Zmiana terminu', $page->schedule_change_notice);
        $this->assertSame([['date' => '2026-11-03', 'time' => '10:00', 'location' => 'sala 12', 'note' => '', 'changed' => true]], $page->schedule_items);
    }

    public function test_faq_items_drop_empty_rows(): void
    {
        $page = $this->page(['type' => 'faq']);

        $this->save($page, [
            'faq_intro' => 'Najczęstsze pytania',
            'faq_items' => [
                ['question' => 'Jak się zapisać?', 'answer' => '<p>Przez formularz.</p>'],
                ['question' => '', 'answer' => ''],
            ],
        ])->assertOk();

        $page->refresh();
        $this->assertSame('Najczęstsze pytania', $page->faq_intro);
        $this->assertSame([['question' => 'Jak się zapisać?', 'answer' => '<p>Przez formularz.</p>']], $page->faq_items);
    }

    public function test_saves_type_data_of_documents_page(): void
    {
        $page = $this->page(['type' => 'documents']);

        $this->actingAs($this->admin())
            ->get(route('admin.podstrony.bloki.edit', $page))
            ->assertOk()
            ->assertSee('data-type-fields="documents"', false)
            ->assertDontSee('data-type-fields="tender"', false);

        $this->save($page, ['type_data' => [
            'lead' => 'Statut i sprawozdania',
            'docs' => [['group' => 'Statut', 'title' => 'Statut FEER', 'url' => '/storage/statut.pdf', 'note' => '']],
            'nieznane' => 'pomijane',
        ]])->assertOk();

        $data = $page->fresh()->type_data;
        $this->assertSame('Statut i sprawozdania', $data['lead']);
        $this->assertSame('Statut FEER', $data['docs'][0]['title']);
        $this->assertArrayNotHasKey('nieznane', $data);
    }

    public function test_classic_only_types_keep_the_form(): void
    {
        $page = $this->page(['type' => 'bip_move']);

        $this->assertFalse(\App\Support\PageTypeFields::supports($page));
        $this->save($page, ['bip_move_url' => 'https://bip.example.org'])->assertOk();
        $this->assertNull($page->fresh()->bip_move_url);
    }

    public function test_classic_form_still_saves_event_fields(): void
    {
        $page = $this->page(['type' => 'event']);

        $this->actingAs($this->admin())->put(route('admin.podstrony.update', $page), [
            'title' => 'Wydarzenie',
            'slug' => 'strona',
            'parent_id' => '',
            'type' => 'event',
            'event_when' => 'w piątek',
            'legacy_name' => 'czyszczone poza typem',
        ])->assertRedirect();

        $page->refresh();
        $this->assertSame('w piątek', $page->event_when);
        $this->assertNull($page->legacy_name);
    }
}
