<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Mój profil: karta konta, spis sekcji i sekcje w kartach z zachowanymi kotwicami. */
class ProfileLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_uklad_z_karta_konta_spisem_i_kotwicami(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN, 'name' => 'Anna Kowalska']);

        $html = $this->actingAs($user)->get(route('profile.edit'))->assertOk()->getContent();

        $this->assertStringContainsString('aria-label="Sekcje profilu"', $html);
        $this->assertStringContainsString('>AK</span>', $html);
        $this->assertStringContainsString('Administrator', $html);
        foreach (['dane-profilu', 'haslo', 'dwuetapowe', 'wyglad-panelu', 'powiadomienia', 'usun-konto'] as $anchor) {
            $this->assertStringContainsString('href="#'.$anchor.'"', $html);
            $this->assertStringContainsString('<section id="'.$anchor.'"', $html);
        }
    }

    public function test_konto_microsoft_bez_hasla_i_usuwania(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR, 'microsoft_id' => 'ms-1']);

        $html = $this->actingAs($user)->get(route('profile.edit'))->assertOk()->getContent();

        $this->assertStringContainsString('Konto zarządzane przez Microsoft 365', $html);
        $this->assertStringNotContainsString('<section id="haslo"', $html);
        $this->assertStringNotContainsString('<section id="usun-konto"', $html);
        $this->assertStringContainsString('<section id="wyglad-panelu"', $html);
    }
}
