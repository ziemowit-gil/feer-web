<?php
namespace Tests\Feature\Admin;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class PageFormRenderTest extends TestCase {
    use RefreshDatabase;
    protected function setUp(): void {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }
    public function test_page_create_form_renders(): void {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route('admin.podstrony.create'))
            ->assertOk()
            ->assertSee('Widoczność i status')
            ->assertSee('Powiązania i kolejność')
            ->assertSee('Wewnętrzna (dostęp ograniczony)')
            ->assertSee('Treść archiwalna');
    }
}
