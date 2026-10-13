<?php

namespace Tests\Feature;

use App\Mail\ContentReviewReminderMail;
use App\Models\News;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContentReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function age(string $table, int $id, int $months): void
    {
        DB::table($table)->where('id', $id)->update(['updated_at' => now()->subMonths($months)]);
    }

    public function test_due_content_is_unpublished_and_date_cleared(): void
    {
        $due = Page::create(['title' => 'Nabór', 'slug' => 'nabor', 'type' => 'standard', 'is_published' => true, 'unpublish_at' => now()->subMinute()]);
        $later = News::create(['title' => 'Promocja', 'slug' => 'promocja', 'content' => '<p>x</p>', 'is_published' => true, 'published_at' => now(), 'unpublish_at' => now()->addDay()]);

        $this->artisan('content:unpublish-due')->assertSuccessful();

        $this->assertFalse((bool) $due->fresh()->is_published);
        $this->assertNull($due->fresh()->unpublish_at);
        $this->assertTrue((bool) $later->fresh()->is_published);
    }

    public function test_stale_list_and_mark_reviewed(): void
    {
        $old = Page::create(['title' => 'Stary cennik', 'slug' => 'cennik', 'type' => 'standard', 'is_published' => true]);
        $fresh = Page::create(['title' => 'Świeża strona', 'slug' => 'swieza', 'type' => 'standard', 'is_published' => true]);
        $this->age('pages', $old->id, 14);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.przeglad-tresci.index'))
            ->assertOk()->assertSee('Stary cennik')->assertDontSee('Świeża strona');

        $this->actingAs($admin)->post(route('admin.przeglad-tresci.reviewed'), ['items' => ['pages:'.$old->id]])->assertRedirect();

        $this->assertNotNull($old->fresh()->reviewed_at);
        $this->assertTrue($old->fresh()->updated_at->lt(now()->subMonths(13)), 'Oznaczenie nie zmienia daty aktualizacji.');
        $this->actingAs($admin)->get(route('admin.przeglad-tresci.index'))->assertDontSee('Stary cennik');
    }

    public function test_reminder_mail_only_when_something_is_stale(): void
    {
        Mail::fake();
        $this->admin();

        $this->artisan('content:review-reminder')->assertSuccessful();
        Mail::assertNothingSent();

        $news = News::create(['title' => 'Stary wpis', 'slug' => 'stary-wpis', 'content' => '<p>x</p>', 'is_published' => true, 'published_at' => now()->subYears(2)]);
        $this->age('news', $news->id, 20);

        $this->artisan('content:review-reminder')->assertSuccessful();
        Mail::assertSent(ContentReviewReminderMail::class, fn ($m) => $m->rows->contains(fn ($r) => $r['model']->is($news)));
    }

    public function test_page_form_saves_unpublish_date(): void
    {
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona-u', 'type' => 'standard', 'is_published' => true, 'content' => '<p>x</p>']);

        $this->actingAs($this->admin())->put(route('admin.podstrony.update', $page), [
            'title' => 'Strona', 'slug' => 'strona-u', 'type' => 'standard', 'is_published' => 1, 'stay' => 1,
            'parent_id' => '', 'project_id' => '', 'order' => 0, 'content' => '<p>x</p>', 'unpublish_at' => '2027-01-31T23:59',
        ])->assertRedirect();

        $this->assertSame('2027-01-31 23:59', $page->fresh()->unpublish_at->format('Y-m-d H:i'));
        $this->actingAs($this->admin())->get(route('admin.podstrony.edit', $page))->assertSee('Zdejmij z publikacji');
    }
}
