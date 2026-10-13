<?php

namespace Tests\Feature\Admin;

use App\Models\News;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrashEmptyTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_trash_force_deletes_everything_but_live_content(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $live = Page::create(['title' => 'Żywa', 'slug' => 'zywa', 'type' => 'standard', 'is_published' => true]);
        $p = Page::create(['title' => 'Usunięta', 'slug' => 'usunieta', 'type' => 'standard', 'is_published' => true]);
        $n = News::create(['title' => 'Stara', 'slug' => 'stara', 'content' => '<p>x</p>', 'is_published' => true, 'published_at' => now()]);
        $p->delete();
        $n->delete();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.kosz.index'))->assertSee('Usuń wszystkie (2)');
        $this->actingAs($admin)->delete(route('admin.kosz.empty'))->assertRedirect()->assertSessionHas('status', 'Kosz opróżniony — trwale usunięto: 2.');

        $this->assertNull(Page::withTrashed()->find($p->id));
        $this->assertNull(News::withTrashed()->find($n->id));
        $this->assertNotNull(Page::find($live->id));
    }
}
