<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\BlockRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Klocek „Numer telefonu” edytora blokowego: numer za przyciskiem, link tel:, czyszczenie danych. */
class PhoneBlockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function render(array $attrs): string
    {
        return (new BlockRenderer)->render([['id' => 'p1', 'type' => 'phone', 'attrs' => $attrs, 'inner' => []]]);
    }

    public function test_renders_reveal_card_with_tel_link_label_and_note(): void
    {
        $html = $this->render(['label' => 'Zadzwoń do biura', 'number' => '601 350 487', 'note' => 'pon.–śr. 10–12']);

        $this->assertStringContainsString('data-phone-reveal', $html);
        $this->assertStringContainsString('<button type="button" class="phone-reveal-btn" aria-expanded="false"><span>Pokaż numer telefonu</span></button>', $html);
        $this->assertStringContainsString('<a class="phone-reveal-number" href="tel:601350487">601 350 487</a>', $html);
        $this->assertStringContainsString('Zadzwoń do biura', $html);
        $this->assertStringContainsString('pon.–śr. 10–12', $html);
    }

    public function test_number_is_cleaned_and_empty_number_renders_nothing(): void
    {
        $html = $this->render(['number' => '+48 601-350-487 <script>x</script>']);
        $this->assertStringContainsString('href="tel:+48601350487"', $html);
        $this->assertStringNotContainsString('<script>', $html);

        $this->assertSame('', $this->render(['number' => 'brak']));
        $this->assertSame('', $this->render(['number' => '']));
    }

    public function test_label_is_escaped(): void
    {
        $this->assertStringNotContainsString('<b>', $this->render(['label' => '<b>x</b>', 'number' => '600 000 000']));
    }

    public function test_public_page_keeps_the_button_and_number(): void
    {
        $page = Page::create(['title' => 'Kontakt do nas', 'slug' => 'kontakt-do-nas', 'type' => 'standard', 'is_published' => true]);
        $page->forceFill([
            'blocks' => [['type' => 'phone', 'attrs' => ['label' => 'Zadzwoń', 'number' => '601 350 487', 'note' => '']]],
        ])->save();
        $page->forceFill(['content' => (new BlockRenderer)->render($page->blocks)])->saveQuietly();

        $this->get('/kontakt-do-nas')->assertOk()
            ->assertSee('phone-reveal-btn', false)
            ->assertSee('href="tel:601350487"', false);
    }

    public function test_editor_script_defines_block_and_public_script_reveals_it(): void
    {
        $editor = file_get_contents(resource_path('js/block-editor.js'));
        $this->assertStringContainsString("phone: { title: 'Numer telefonu'", $editor);
        $this->assertStringContainsString("n.hasAttribute('data-phone-reveal')", $editor);

        $app = file_get_contents(resource_path('js/app.js'));
        $this->assertStringContainsString("querySelectorAll('[data-phone-reveal]')", $app);
        $this->assertStringContainsString("matchMedia('(hover: hover)')", $app);
    }
}
