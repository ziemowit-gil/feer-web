<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** „Przenieś typ menu na podstrony”: styl nawigacji z działu trafia na wszystkie podstrony, także zagnieżdżone. */
class CopyNavStyleTest extends TestCase
{
    use RefreshDatabase;

    public function test_copies_nav_style_to_all_descendants_only(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $root = Page::create(['title' => 'Dział', 'slug' => 'dzial', 'type' => 'standard', 'is_published' => true, 'side_nav_style' => 'accordion']);
        $child = Page::create(['title' => 'Podstrona', 'slug' => 'podstrona', 'type' => 'standard', 'is_published' => true, 'parent_id' => $root->id, 'side_nav_style' => 'sidebar']);
        $grand = Page::create(['title' => 'Pod-podstrona', 'slug' => 'pod', 'type' => 'standard', 'is_published' => true, 'parent_id' => $child->id, 'side_nav_style' => 'tabs']);
        $other = Page::create(['title' => 'Inny dział', 'slug' => 'inny', 'type' => 'standard', 'is_published' => true, 'side_nav_style' => 'tiles']);

        $this->actingAs($admin)->post(route('admin.podstrony.styl-na-podstrony', $root))->assertRedirect();

        $this->assertSame('accordion', $child->fresh()->side_nav_style);
        $this->assertSame('accordion', $grand->fresh()->side_nav_style);
        $this->assertSame('accordion', $root->fresh()->side_nav_style);
        $this->assertSame('tiles', $other->fresh()->side_nav_style, 'Inne działy bez zmian.');
    }
}
