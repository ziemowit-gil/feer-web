<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\Newsletter\Jobs\SendDoubleOptIn;
use Modules\Newsletter\Models\NewsletterConsent;
use Modules\Newsletter\Models\NewsletterImport;
use Modules\Newsletter\Models\NewsletterList;
use Modules\Newsletter\Models\NewsletterSuppression;
use Tests\TestCase;

/** Import subskrybentów z GetResponse (API v3 i eksport CSV): listy, czarna lista, zgody, bez oświadczenia i bez double opt-in. */
class NewsletterGetResponseImportTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'gr-test-key-9876';

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        Storage::fake('local');
        Bus::fake([SendDoubleOptIn::class]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    /** Fałszywe API GetResponse: Anna na dwóch listach, Jan na jednej, czarna lista z adresem i maską domeny. */
    private function fakeGetResponse(): void
    {
        $contacts = [
            ['contactId' => 'c1', 'email' => 'anna@example.org', 'name' => 'Anna Nowak', 'createdOn' => '2024-03-01T10:00:00+0000', 'ipAddress' => '203.0.113.7', 'campaign' => ['campaignId' => 'A', 'name' => 'Newsletter FEER']],
            ['contactId' => 'c2', 'email' => 'jan@example.org', 'name' => 'Jan', 'createdOn' => '2024-04-01T10:00:00+0000', 'ipAddress' => null, 'campaign' => ['campaignId' => 'B', 'name' => 'Wydarzenia']],
            ['contactId' => 'c3', 'email' => 'anna@example.org', 'name' => 'Anna Nowak', 'createdOn' => '2024-05-01T10:00:00+0000', 'ipAddress' => '203.0.113.8', 'campaign' => ['campaignId' => 'B', 'name' => 'Wydarzenia']],
        ];

        Http::fake(function (HttpRequest $request) use ($contacts) {
            $this->assertSame('api-key '.self::KEY, $request->header('X-Auth-Token')[0] ?? null);
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);
            $path = (string) parse_url($request->url(), PHP_URL_PATH);

            if (str_ends_with($path, '/v3/contacts')) {
                $campaign = $q['query']['campaignId'] ?? null;
                $rows = array_values(array_filter($contacts, fn ($c) => ! $campaign || $c['campaign']['campaignId'] === $campaign));
                $perPage = (int) ($q['perPage'] ?? 100);
                $page = (int) ($q['page'] ?? 1);
                $pages = (int) max(1, ceil(count($rows) / $perPage));

                return Http::response(array_slice($rows, ($page - 1) * $perPage, $perPage), 200, ['TotalCount' => count($rows), 'TotalPages' => $pages]);
            }
            if (str_ends_with($path, '/v3/campaigns')) {
                return Http::response([['campaignId' => 'A', 'name' => 'Newsletter FEER'], ['campaignId' => 'B', 'name' => 'Wydarzenia']], 200, ['TotalPages' => 1]);
            }
            if (str_ends_with($path, '/v3/accounts/blocklists')) {
                return Http::response(['masks' => ['zly@example.org', '@spam.example']]);
            }

            return Http::response(['message' => 'Not found'], 404);
        });
    }

    public function test_podglad_i_import_z_api_bez_oswiadczenia_i_bez_double_opt_in(): void
    {
        $this->fakeGetResponse();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.newsletter.subskrybenci.import.getresponse'), ['token' => self::KEY])->assertRedirect();
        $import = NewsletterImport::latest('id')->first();
        $this->assertSame(NewsletterImport::SOURCE_GETRESPONSE_API, $import->source);
        $this->assertSame(['active' => 3, 'blocklisted' => 1], $import->summary['source_status']);
        $this->assertSame(['A' => 1, 'B' => 2], collect($import->summary['groups'])->pluck('count', 'key')->all());
        $this->assertNotSame(self::KEY, DB::table('site_settings')->value('newsletter_getresponse_token'));

        $this->actingAs($admin)->get(route('admin.newsletter.subskrybenci.import.show', $import))->assertOk()
            ->assertSee('Statusy w GetResponse')->assertSee('Wydarzenia')->assertSee('Przenieś czarną listę GetResponse')->assertDontSee('Oświadczam');

        $this->actingAs($admin)->put(route('admin.newsletter.subskrybenci.import.update', $import), [
            'action' => 'run', 'import_blocklist' => 1, 'group_map' => ['A' => 'new_list', 'B' => 'new_list'],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $import->refresh();

        $this->assertSame(NewsletterImport::STATUS_COMPLETED, $import->status);
        $this->assertSame([3, 1, 0, 0], [$import->created_count, $import->updated_count, $import->skipped_count, $import->failed_count]);

        $anna = Subscriber::findByEmail('anna@example.org');
        $this->assertSame(Subscriber::STATUS_CONFIRMED, $anna->status);
        $this->assertSame('getresponse', $anna->source);
        $this->assertSame(['Newsletter FEER', 'Wydarzenia'], $anna->lists()->orderBy('name')->pluck('name')->all());
        $consent = NewsletterConsent::where('subscriber_id', $anna->id)->sole();
        $this->assertSame('import GetResponse', $consent->source);
        $this->assertSame('getresponse_import', $consent->method);
        $this->assertSame(Subscriber::hashIp('203.0.113.7'), $consent->ip_hash);
        $this->assertSame('2024-03-01', $consent->granted_at->format('Y-m-d'));

        $this->assertSame(Subscriber::STATUS_CONFIRMED, Subscriber::findByEmail('jan@example.org')->status);
        $this->assertSame(Subscriber::STATUS_UNSUBSCRIBED, Subscriber::findByEmail('zly@example.org')->status);
        $this->assertTrue(NewsletterSuppression::contains(Subscriber::hashEmail('zly@example.org')));
        $this->assertSame('getresponse_unsubscribed', NewsletterSuppression::sole()->reason);
        $this->assertSame(2, NewsletterList::count());
        Bus::assertNotDispatched(SendDoubleOptIn::class);

        // Ponowny import: bez duplikatów subskrybentów, zgód i list.
        $this->actingAs($admin)->post(route('admin.newsletter.subskrybenci.import.getresponse'));
        $again = NewsletterImport::latest('id')->first();
        $this->actingAs($admin)->put(route('admin.newsletter.subskrybenci.import.update', $again), ['action' => 'run', 'import_blocklist' => 1, 'group_map' => ['A' => 'new_list', 'B' => 'new_list']]);
        $this->assertSame(3, Subscriber::count());
        $this->assertSame(2, NewsletterConsent::count());
        $this->assertSame(2, NewsletterList::count());
        $this->assertSame([0, 4], [$again->fresh()->created_count, $again->fresh()->updated_count]);
    }

    public function test_czarna_lista_jest_opcjonalna(): void
    {
        $this->fakeGetResponse();
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.newsletter.subskrybenci.import.getresponse'), ['token' => self::KEY]);
        $import = NewsletterImport::latest('id')->first();

        $this->actingAs($admin)->put(route('admin.newsletter.subskrybenci.import.update', $import), ['action' => 'run']);

        $this->assertSame(NewsletterImport::STATUS_COMPLETED, $import->fresh()->status);
        $this->assertNull(Subscriber::findByEmail('zly@example.org'));
        $this->assertSame(0, NewsletterSuppression::count());
    }

    public function test_bledny_klucz_pokazuje_komunikat(): void
    {
        Http::fake(['api.getresponse.com/*' => Http::response(['message' => 'Unable to authenticate'], 401)]);

        $this->actingAs($this->admin())->from(route('admin.newsletter.subskrybenci.import'))
            ->post(route('admin.newsletter.subskrybenci.import.getresponse'), ['token' => 'zly'])
            ->assertRedirect(route('admin.newsletter.subskrybenci.import'))
            ->assertSessionHasErrors('gr_token');
        $this->assertSame(0, NewsletterImport::count());
    }

    public function test_klucz_z_env_jako_fallback(): void
    {
        config(['services.getresponse.token' => self::KEY]);
        $this->fakeGetResponse();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.newsletter.subskrybenci.import'))->assertOk()->assertSee('••••9876')->assertSee('GETRESPONSE_API_KEY');
        $this->actingAs($admin)->post(route('admin.newsletter.subskrybenci.import.getresponse'))->assertRedirect();
        $this->assertSame(1, NewsletterImport::count());
    }

    public function test_eksport_csv_z_getresponse_bez_oswiadczenia(): void
    {
        $admin = $this->admin();
        $csv = "email,name,campaign,created_on,ip_address\nola@example.org,Ola,Newsletter FEER,2024-02-02 12:00:00,198.51.100.4\n";
        $this->actingAs($admin)->post(route('admin.newsletter.subskrybenci.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('getresponse.csv', $csv), 'source' => NewsletterImport::SOURCE_GETRESPONSE_CSV,
        ]);
        $import = NewsletterImport::latest('id')->first();
        $this->assertSame(['email' => 0, 'name' => 1, 'subscribed_at' => 3, 'ip' => 4, 'groups' => 2], $import->mapping);

        $this->actingAs($admin)->put(route('admin.newsletter.subskrybenci.import.update', $import), [
            'action' => 'run', 'mapping' => $import->mapping, 'default_status' => 'confirmed', 'group_map' => ['Newsletter FEER' => 'new_list'],
        ])->assertSessionHasNoErrors();

        $ola = Subscriber::findByEmail('ola@example.org');
        $this->assertSame(Subscriber::STATUS_CONFIRMED, $ola->status);
        $this->assertSame('import GetResponse', $ola->consents()->sole()->source);
        $this->assertTrue($ola->lists()->where('name', 'Newsletter FEER')->exists());
        Bus::assertNotDispatched(SendDoubleOptIn::class);
    }
}
