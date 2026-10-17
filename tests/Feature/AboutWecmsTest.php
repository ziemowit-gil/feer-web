<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Panel → O weCMS: wersja główna, nowości z linkami (dla administratora), możliwości, autor. */
class AboutWecmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_strona_o_wecms_ma_wersje_nowosci_i_autora(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get(route('admin.o-cms'))->assertOk()->getContent();

        $this->assertStringContainsString('Wersja '.config('cms.version'), $html);
        $this->assertStringContainsString('Nowe w wersji '.config('cms.version'), $html);
        $this->assertStringContainsString(route('admin.health.index'), $html);
        $this->assertStringContainsString('Utrzymanie serwisu', $html);
        $this->assertStringContainsString('Autor weCMS', $html);
        $this->assertStringNotContainsString('Autor i twórca', $html);
        // Widoczna treść bez słowa „Joomla” (komentarze w CSS motywu się nie liczą).
        $this->assertStringNotContainsString('Joomla', preg_replace('~<style\b.*?</style>~s', '', $html));
    }

    public function test_redaktor_nie_dostaje_linkow_do_stron_administratora(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);

        $html = $this->actingAs($editor)->get(route('admin.o-cms'))->assertOk()->getContent();

        $this->assertStringNotContainsString(route('admin.baza-mysql.index'), $html);
        $this->assertStringContainsString(route('profile.edit').'#wyglad-panelu', $html);
    }
}
