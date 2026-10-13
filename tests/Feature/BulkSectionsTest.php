<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Masowe akcje w edytorze sekcji: publikacja/ukrycie zaznaczonych podstron działania (istniejący endpoint zbiorczy). */
class BulkSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_unpublish_selected_subpages(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $a = Page::create(['title' => 'A', 'slug' => 'a-bulk', 'type' => 'standard', 'is_published' => true]);
        $b = Page::create(['title' => 'B', 'slug' => 'b-bulk', 'type' => 'standard', 'is_published' => true]);

        $this->actingAs($admin)->post(route('admin.podstrony.bulk'), ['action' => 'unpublish', 'ids' => [$a->id]])->assertRedirect();

        $this->assertFalse($a->fresh()->is_published);
        $this->assertTrue($b->fresh()->is_published, 'Niezaznaczona podstrona bez zmian.');
    }
}
