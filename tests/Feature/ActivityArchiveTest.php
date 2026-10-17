<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\BipDocument;
use App\Models\User;
use App\Support\ActivityArchive;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/** Dziennik zdarzeń: wpisy starsze niż 30 dni do plików TXT (miesięcznie), rejestr BIP zostaje w bazie. */
class ActivityArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        File::deleteDirectory(ActivityArchive::dir());
        parent::tearDown();
    }

    private function entry(string $description, int $daysAgo, ?string $subjectType = null): Activity
    {
        $a = Activity::create(['log_name' => 'cms', 'description' => $description, 'event' => 'updated', 'subject_type' => $subjectType, 'subject_id' => $subjectType ? 5 : null, 'properties' => ['label' => $description]]);
        $a->forceFill(['created_at' => now()->subDays($daysAgo), 'updated_at' => now()->subDays($daysAgo)])->save();

        return $a;
    }

    public function test_stare_wpisy_do_txt_bip_i_nowe_zostaja(): void
    {
        $old = $this->entry('Stara zmiana strony', 45, \App\Models\Page::class);
        $bip = $this->entry('Zmiana dokumentu BIP', 60, BipDocument::class);
        $fresh = $this->entry('Świeża zmiana', 3, \App\Models\Page::class);

        $r = ActivityArchive::run();

        $this->assertSame(1, $r['archived']);
        $this->assertNull(Activity::find($old->id));
        $this->assertNotNull(Activity::find($bip->id), 'Rejestr zmian BIP zostaje w bazie.');
        $this->assertNotNull(Activity::find($fresh->id));

        $file = ActivityArchive::dir().'/dziennik-'.now()->subDays(45)->format('Y-m').'.txt';
        $txt = file_get_contents($file);
        $this->assertStringContainsString('# Dziennik zdarzeń — archiwum', $txt);
        $this->assertStringContainsString(now()->subDays(45)->format('Y-m-d').' ', $txt);
        $this->assertStringContainsString('| System | Edycja | ', $txt);
        $this->assertStringContainsString('#5 „Stara zmiana strony”', $txt);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get(route('admin.dziennik.index'))->assertOk()
            ->assertSee('Archiwum (starsze niż 30 dni)')->assertSee(route('admin.dziennik.archive', basename($file)), false);
        $this->actingAs($admin)->get(route('admin.dziennik.archive', basename($file)))->assertOk()->assertDownload(basename($file));
        $this->actingAs($admin)->get('/edycja/dziennik/archiwum/..%2F.env')->assertNotFound();
    }

    public function test_polecenie_jest_w_harmonogramie(): void
    {
        $this->artisan('dziennik:archiwizuj')->expectsOutputToContain('Brak wpisów do archiwizacji.')->assertSuccessful();
        $events = collect(app(Schedule::class)->events())->map->command->filter()->implode(' ');
        $this->assertStringContainsString('dziennik:archiwizuj', $events);
    }
}
