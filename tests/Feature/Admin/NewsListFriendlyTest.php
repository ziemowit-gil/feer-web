<?php

namespace Tests\Feature\Admin;

use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Lista aktualności dla redaktora: tytuł jako link do edycji, status słowami z datą, wyraźne „Edytuj”. */
class NewsListFriendlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_ma_tytul_jako_link_status_z_data_i_edytuj(): void
    {
        $live = News::create(['title' => 'Napisz, zadzwoń', 'slug' => 'napisz', 'content' => '<p>x</p>', 'published_at' => now()->subDay(), 'is_published' => true]);
        $draft = News::create(['title' => 'Nowa aktualność', 'slug' => 'nowa', 'content' => '<p>x</p>', 'is_published' => false]);
        News::create(['title' => 'Zaplanowana', 'slug' => 'plan', 'content' => '<p>x</p>', 'published_at' => now()->addDays(3)->setTime(10, 0), 'is_published' => true]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get(route('admin.newsy.index'))->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('admin.newsy.edit', $live).'" class="text-base font-bold', $html);
        $this->assertStringContainsString('Opublikowana', $html);
        $this->assertStringContainsString('na '.now()->addDays(3)->format('d.m.Y').' 10:00', $html);
        $this->assertStringContainsString('niewidoczna na stronie', $html);
        $this->assertStringContainsString('Edytuj<span class="sr-only">: Nowa aktualność</span>', $html);
        $this->assertStringContainsString('form="delete-'.$draft->id.'"', $html);
        $this->assertStringContainsString('<form id="delete-'.$draft->id.'"', $html);
        $this->assertStringContainsString('Dodaj aktualność', $html);
        $this->assertStringNotContainsString('Dodaj news', $html);
    }
}
