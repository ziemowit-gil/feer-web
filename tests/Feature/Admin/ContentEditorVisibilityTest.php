<?php
namespace Tests\Feature\Admin;
use App\Models\Page; use App\Models\SiteSetting; use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase; use Tests\TestCase;
class ContentEditorVisibilityTest extends TestCase {
    use RefreshDatabase;
    protected function setUp(): void { parent::setUp();
        \Closure::bind(function(){ static::$cached=null; }, null, SiteSetting::class)(); }
    private function admin(){ return User::factory()->create(['role'=>User::ROLE_ADMIN]); }
    public function test_content_editor_hidden_for_about_and_bip(): void {
        $about = Page::create(['title'=>'O nas','slug'=>'o-nas','type'=>'about','is_published'=>true]);
        $this->actingAs($this->admin())->get(route('admin.podstrony.edit',$about))
            ->assertOk()->assertSee('data-content-field class="hidden"', false);
        $bip = Page::create(['title'=>'BIP','slug'=>'bip-p','type'=>'bip_move','is_published'=>true]);
        $this->actingAs($this->admin())->get(route('admin.podstrony.edit',$bip))
            ->assertOk()->assertSee('data-content-field class="hidden"', false);
    }
    public function test_content_editor_visible_for_standard(): void {
        $std = Page::create(['title'=>'Zwykla','slug'=>'zwykla','type'=>'standard','is_published'=>true]);
        $this->actingAs($this->admin())->get(route('admin.podstrony.edit',$std))
            ->assertOk()->assertSee('data-content-field class=""', false);
    }
}
