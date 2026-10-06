<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AboutCmsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    public function test_strona_o_cms_opisuje_system_i_autora_dla_kazdego_uzytkownika_panelu(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);

        $this->actingAs($editor)->get(route('admin.o-cms'))
            ->assertOk()
            ->assertSee('weCMS')
            ->assertSee('Ziemowit Gil')
            ->assertSee('Laravel '.app()->version())
            ->assertDontSee('Dokumentacja techniczna');
    }

    public function test_administrator_widzi_link_do_dokumentacji_a_menu_uzytkownika_prowadzi_do_strony(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.o-cms'))->assertOk()->assertSee('Dokumentacja techniczna');
        $this->actingAs($admin)->get(route('admin.podstrony.index'))->assertOk()->assertSee(route('admin.o-cms'), false);
    }

    public function test_gosc_jest_przekierowany_do_logowania(): void
    {
        $this->get(route('admin.o-cms'))->assertRedirect();
    }
}
