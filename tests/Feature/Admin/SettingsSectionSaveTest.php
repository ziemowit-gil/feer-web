<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Zapis ustawień per sekcja: serwer waliduje i zapisuje tylko pola z _section_fields[],
 * więc błąd (albo puste wymagane pole) w innej sekcji nie blokuje zapisu.
 */
class SettingsSectionSaveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_saving_one_section_ignores_other_sections(): void
    {
        $settings = SiteSetting::current();
        $settings->update(['maintenance_mode' => true, 'brand_color' => '#1752bf', 'mail_transport' => 'default']);

        $this->actingAs($this->admin())
            ->put(route('admin.ustawienia.update'), [
                '_redirect_tab' => 'general',
                '_section_fields' => ['site_name', 'tagline', 'content_editor'],
                'site_name' => 'Nowa nazwa',
                'tagline' => 'Nowy podtytuł',
                'content_editor' => $settings->contentEditorValue(),
                // Pola innych sekcji: puste wymagane i błędne — mają zostać zignorowane.
                'brand_color' => '',
                'mail_transport' => 'nie-ma-takiego',
                'ga_measurement_id' => 'zly-identyfikator',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.ustawienia.edit', ['tab' => 'general']))
            ->assertSessionHas('status', 'Zapisano sekcję „Ogólne”.');

        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $fresh = SiteSetting::query()->find($settings->id);
        $this->assertSame('Nowa nazwa', $fresh->site_name);
        $this->assertSame('Nowy podtytuł', $fresh->tagline);
        $this->assertTrue((bool) $fresh->maintenance_mode, 'Przełącznik z sekcji „Serwis” nie może się wyłączyć.');
        $this->assertSame('#1752bf', $fresh->brand_color);
    }

    public function test_errors_in_the_saved_section_still_block_saving(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.ustawienia.update'), [
                '_redirect_tab' => 'general',
                '_section_fields' => ['site_name'],
                'site_name' => '',
            ])
            ->assertSessionHasErrors('site_name');
    }

    public function test_every_settings_field_belongs_to_a_section(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.ustawienia.edit'))->assertOk()->getContent();

        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new DOMXPath($dom);
        $orphans = [];
        foreach ($xpath->query('//form[@id="settings-form"]//*[@name]') as $field) {
            $name = $field->getAttribute('name');
            if (str_starts_with($name, '_') || $field->getAttribute('form') !== '') {
                continue;
            }
            $inSection = false;
            for ($node = $field->parentNode; $node instanceof \DOMElement; $node = $node->parentNode) {
                if (preg_match("/^tab === '[a-z_]+'$/", trim($node->getAttribute('x-show')))) {
                    $inSection = true;
                    break;
                }
            }
            if (! $inSection) {
                $orphans[] = $name;
            }
        }

        $this->assertSame([], array_values(array_unique($orphans)), 'Pola poza sekcjami nie zapiszą się przy zapisie jednej sekcji.');
    }
}
