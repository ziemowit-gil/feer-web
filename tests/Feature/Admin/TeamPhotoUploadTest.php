<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeamPhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_uploaded_team_photo_is_stored_and_set_on_row(): void
    {
        Storage::fake('public');

        $page = Page::create(['title' => 'O nas', 'slug' => 'o-nas', 'type' => 'about', 'is_published' => true]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->put(route('admin.podstrony.update', $page), [
                'title' => 'O nas',
                'slug' => 'o-nas',
                'type' => 'about',
                'is_published' => '1',
                'parent_id' => '',
                'project_id' => '',
                'about_team' => [
                    ['name' => 'Anna Kowalska', 'role' => 'Koordynatorka', 'photo' => ''],
                ],
                'about_team_photos' => [
                    0 => UploadedFile::fake()->image('anna.jpg', 200, 200),
                ],
            ])
            ->assertRedirect(route('admin.podstrony.index'));

        $team = $page->fresh()->about_team;

        $this->assertCount(1, $team);
        $this->assertNotEmpty($team[0]['photo']);
        $this->assertStringContainsString('/storage/zespol/', $team[0]['photo']);
        $this->assertCount(1, Storage::disk('public')->allFiles('zespol'));
    }

    public function test_manual_url_still_works_when_no_file(): void
    {
        Storage::fake('public');

        $page = Page::create(['title' => 'O nas', 'slug' => 'o-nas', 'type' => 'about', 'is_published' => true]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->put(route('admin.podstrony.update', $page), [
                'title' => 'O nas', 'slug' => 'o-nas', 'type' => 'about', 'is_published' => '1', 'parent_id' => '', 'project_id' => '',
                'about_team' => [
                    ['name' => 'Jan', 'role' => 'Prezes', 'photo' => 'https://example.com/jan.jpg',
                     'website' => 'https://jan.example.com', 'substack' => 'https://jan.substack.com'],
                ],
            ])
            ->assertRedirect();

        $saved = $page->fresh()->about_team[0];
        $this->assertSame('https://example.com/jan.jpg', $saved['photo']);
        $this->assertSame('https://jan.example.com', $saved['website']);
        $this->assertSame('https://jan.substack.com', $saved['substack']);
    }
}
