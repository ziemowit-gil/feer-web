<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ImageSizes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pulpit panelu: karta „Wymiary grafik” z listą zalecanych rozmiarów (App\Support\ImageSizes). */
class DashboardImageSizesTest extends TestCase
{
    use RefreshDatabase;

    public function test_pulpit_pokazuje_wymiary_grafik(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('id="dash-image-sizes"', false)
            ->assertSee('Wymiary grafik')
            ->assertSee('<caption class="sr-only">Zalecane wymiary grafik w serwisie</caption>', false);

        $response->assertSee('id="dash-banner-sizes"', false)->assertSee('Kolumna boczna aktualności')->assertSee('512×640 px');

        foreach ([...ImageSizes::LIST, ...ImageSizes::BANNERS] as [$where, $size]) {
            $response->assertSee($where)->assertSee($size);
        }
    }

    public function test_multimedia_korzystaja_z_tej_samej_listy(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.multimedia.index'))->assertOk()
            ->assertSee('Zalecane wymiary grafik')
            ->assertSee('Obraz w mega menu')
            ->assertSee('1400×1400 px');
    }
}
