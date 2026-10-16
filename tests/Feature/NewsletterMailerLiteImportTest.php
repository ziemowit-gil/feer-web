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
use Modules\Newsletter\Jobs\RunSubscriberImport;
use Modules\Newsletter\Jobs\SendDoubleOptIn;
use Modules\Newsletter\Models\NewsletterConsent;
use Modules\Newsletter\Models\NewsletterImport;
use Modules\Newsletter\Models\NewsletterList;
use Modules\Newsletter\Models\NewsletterSuppression;
use Tests\TestCase;

/** Import subskrybentów z MailerLite (API i eksport CSV): podgląd, mapowanie, zgody, tłumienie, idempotencja. */
class NewsletterMailerLiteImportTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'ml-test-token-1234';

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

    /** Fałszywe API MailerLite: 5 subskrybentów w dwóch stronach kursora, jedna grupa, pola. */
    private function fakeMailerLite(): void
    {
        $subscribers = [
            ['id' => '1', 'email' => 'Anna@Example.org', 'status' => 'active', 'opted_in_at' => '2024-03-01 10:00:00', 'optin_ip' => '203.0.113.7',
                'fields' => ['name' => 'Anna', 'last_name' => 'Nowak', 'phone' => '+48 600 100 200'], 'groups' => [['id' => '111', 'name' => 'Darczyńcy']]],
            ['id' => '2', 'email' => 'bartek@example.org', 'status' => 'unconfirmed', 'created_at' => '2024-04-01 10:00:00', 'fields' => ['name' => 'Bartek'], 'groups' => []],
            ['id' => '3', 'email' => 'celina@example.org', 'status' => 'unsubscribed', 'subscribed_at' => '2023-01-01 10:00:00', 'unsubscribed_at' => '2024-05-01 10:00:00', 'fields' => [], 'groups' => [['id' => '111', 'name' => 'Darczyńcy']]],
            ['id' => '4', 'email' => 'dawid@example.org', 'status' => 'bounced', 'fields' => [], 'groups' => []],
            ['id' => '5', 'email' => 'ewa@example.org', 'status' => 'junk', 'fields' => [], 'groups' => []],
        ];

        Http::fake(function (HttpRequest $request) use ($subscribers) {
            $this->assertSame('Bearer '.self::TOKEN, $request->header('Authorization')[0] ?? null);
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);
            $path = (string) parse_url($request->url(), PHP_URL_PATH);

            if (str_ends_with($path, '/api/subscribers')) {
                if ((string) ($q['limit'] ?? '') === '0') {
                    $status = $q['filter']['status'] ?? null;

                    return Http::response(['total' => count(array_filter($subscribers, fn ($s) => ! $status || $s['status'] === $status))]);
                }
                $this->assertSame('groups', $q['include'] ?? null);
                $cursor = $q['cursor'] ?? null;

                return $cursor === 'page2'
                    ? Http::response(['data' => array_slice($subscribers, 3), 'meta' => ['next_cursor' => null]])
                    : Http::response(['data' => array_slice($subscribers, 0, 3), 'meta' => ['next_cursor' => 'page2']]);
            }
            if (str_ends_with($path, '/api/groups')) {
                return Http::response(['data' => [['id' => '111', 'name' => 'Darczyńcy', 'active_count' => 1, 'unsubscribed_count' => 1]], 'meta' => ['last_page' => 1]]);
            }
            if (str_ends_with($path, '/api/fields')) {
                return Http::response(['data' => [['key' => 'name', 'name' => 'Name'], ['key' => 'last_name', 'name' => 'Last name'], ['key' => 'phone', 'name' => 'Phone']]]);
            }

            return Http::response(['message' => 'Not found'], 404);
        });
    }

    private function runApiImport(User $admin, array $options = []): NewsletterImport
    {
        $this->actingAs($admin)->post(route('admin.newsletter.subskrybenci.import.mailerlite'), ['token' => self::TOKEN])
            ->assertRedirect();
        $import = NewsletterImport::latest('id')->first();

        $this->actingAs($admin)->put(route('admin.newsletter.subskrybenci.import.update', $import), $options + [
            'action' => 'run', 'group_map' => ['111' => 'new_list'], 'topics' => ['news'],
        ])->assertRedirect(route('admin.newsletter.subskrybenci.import.show', $import));

        return $import->fresh();
    }

    public function test_podglad_z_api_pokazuje_liczby_wg_statusu_i_grupy(): void
    {
        $this->fakeMailerLite();
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.newsletter.subskrybenci.import.mailerlite'), ['token' => self::TOKEN]);
        $import = NewsletterImport::latest('id')->first();
        $response->assertRedirect(route('admin.newsletter.subskrybenci.import.show', $import));

        $this->assertSame(NewsletterImport::STATUS_PREVIEW, $import->status);
        $this->assertSame(5, $import->summary['total']);
        $this->assertSame(['active' => 1, 'unsubscribed' => 1, 'unconfirmed' => 1, 'bounced' => 1, 'junk' => 1], $import->summary['source_status']);
        $this->assertSame(0, Subscriber::count(), 'podgląd niczego nie zapisuje');

        $this->actingAs($admin)->get(route('admin.newsletter.subskrybenci.import.show', $import))->assertOk()
            ->assertSee('Darczyńcy')->assertSee('Niepotwierdzeni')->assertSee('Importuj 5 rekordów');

        // Token zapisany zaszyfrowany, z fallbackiem odczytu przez model.
        $raw = DB::table('site_settings')->value('newsletter_mailerlite_token');
        $this->assertNotSame(self::TOKEN, $raw);
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        $this->assertSame(self::TOKEN, SiteSetting::current()->newsletter_mailerlite_token);
    }

    public function test_import_z_api_mapuje_statusy_zgody_grupy_i_tlumienie(): void
    {
        $this->fakeMailerLite();
        $import = $this->runApiImport($this->admin());

        $this->assertSame(NewsletterImport::STATUS_COMPLETED, $import->status);
        $this->assertSame([5, 0, 0, 0], [$import->created_count, $import->updated_count, $import->skipped_count, $import->failed_count]);

        $anna = Subscriber::findByEmail('anna@example.org');
        $this->assertSame(Subscriber::STATUS_CONFIRMED, $anna->status);
        $this->assertSame('Anna Nowak', $anna->name);
        $this->assertSame('+48600100200', $anna->phone);
        $this->assertSame('mailerlite', $anna->source);
        $this->assertSame('2024-03-01', $anna->confirmed_at->format('Y-m-d'));

        $consent = NewsletterConsent::where('subscriber_id', $anna->id)->sole();
        $this->assertSame('import MailerLite', $consent->source);
        $this->assertSame('2024-03-01 10:00:00', $consent->granted_at->format('Y-m-d H:i:s'));
        $this->assertSame(Subscriber::hashIp('203.0.113.7'), $consent->ip_hash);

        $list = NewsletterList::where('name', 'Darczyńcy')->sole();
        $this->assertTrue($anna->lists()->whereKey($list->id)->exists());

        $this->assertSame(Subscriber::STATUS_PENDING, Subscriber::findByEmail('bartek@example.org')->status);
        $celina = Subscriber::findByEmail('celina@example.org');
        $this->assertSame(Subscriber::STATUS_UNSUBSCRIBED, $celina->status);
        $this->assertSame('2024-05-01', $celina->unsubscribed_at->format('Y-m-d'));
        $this->assertSame(Subscriber::STATUS_BOUNCED, Subscriber::findByEmail('dawid@example.org')->status);
        $this->assertSame(Subscriber::STATUS_COMPLAINED, Subscriber::findByEmail('ewa@example.org')->status);

        foreach (['celina', 'dawid', 'ewa'] as $who) {
            $this->assertTrue(NewsletterSuppression::contains(Subscriber::hashEmail("{$who}@example.org")), "{$who} na liście tłumienia");
        }
        $this->assertFalse(NewsletterSuppression::contains(Subscriber::hashEmail('anna@example.org')));

        // Aktywnych z MailerLite nie wysyłamy ponownie przez double opt-in.
        Bus::assertNotDispatched(SendDoubleOptIn::class);
    }

    public function test_ponowny_import_aktualizuje_bez_duplikatow(): void
    {
        $this->fakeMailerLite();
        $admin = $this->admin();
        $this->runApiImport($admin);
        $second = $this->runApiImport($admin);

        $this->assertSame(5, Subscriber::count());
        $this->assertSame([0, 5], [$second->created_count, $second->updated_count]);
        $this->assertSame(1, NewsletterConsent::count(), 'zgoda z importu nie dubluje się');
        $this->assertSame(1, NewsletterList::where('name', 'Darczyńcy')->count(), 'grupa nie tworzy drugiej listy');
        $this->assertSame(3, NewsletterSuppression::count());
    }

    public function test_opcja_ponownego_potwierdzenia_wysyla_double_opt_in(): void
    {
        $this->fakeMailerLite();
        $this->runApiImport($this->admin(), ['resend_doi' => 1]);

        $anna = Subscriber::findByEmail('anna@example.org');
        $this->assertSame(Subscriber::STATUS_PENDING, $anna->status);
        $this->assertSame(0, NewsletterConsent::count());
        Bus::assertDispatched(SendDoubleOptIn::class, 2); // Anna (aktywna) i Bartek (niepotwierdzony)
    }

    public function test_plik_z_innego_systemu_bez_oswiadczenia_o_zgodach_nie_importuje_aktywnych(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.newsletter.subskrybenci.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('lista.csv', "email,status\njan@example.org,active\n"),
        ]);
        $import = NewsletterImport::latest('id')->first();

        $this->actingAs($admin)->put(route('admin.newsletter.subskrybenci.import.update', $import), ['action' => 'run', 'mapping' => $import->mapping, 'consent_source' => 'baza SZO'])
            ->assertSessionHasErrors('has_consent');
        $this->assertSame(NewsletterImport::STATUS_PREVIEW, $import->fresh()->status);
        $this->assertSame(0, Subscriber::count());
    }

    public function test_bledny_token_pokazuje_komunikat(): void
    {
        Http::fake(['connect.mailerlite.com/*' => Http::response(['message' => 'Unauthenticated.'], 401)]);

        $this->actingAs($this->admin())->from(route('admin.newsletter.subskrybenci.import'))
            ->post(route('admin.newsletter.subskrybenci.import.mailerlite'), ['token' => 'zly'])
            ->assertRedirect(route('admin.newsletter.subskrybenci.import'))
            ->assertSessionHasErrors('token');
        $this->assertSame(0, NewsletterImport::count());
    }

    public function test_token_z_env_jako_fallback(): void
    {
        config(['services.mailerlite.token' => self::TOKEN]);
        $this->fakeMailerLite();

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.newsletter.subskrybenci.import'))->assertOk()->assertSee('••••1234')->assertSee('MAILERLITE_API_TOKEN');
        $this->actingAs($admin)->post(route('admin.newsletter.subskrybenci.import.mailerlite'))->assertRedirect();
        $this->assertSame(1, NewsletterImport::count());
        $this->actingAs($admin)->get(route('admin.newsletter.subskrybenci.import'))->assertOk()->assertSee('Ostatnie importy');
        $this->assertNull(DB::table('site_settings')->value('newsletter_mailerlite_token'));
    }

    public function test_import_csv_z_mailerlite_z_mapowaniem_kolumn(): void
    {
        $admin = $this->admin();
        // Wypisany u nas — aktywny w MailerLite nie może go przywrócić.
        Subscriber::create(['email' => 'stary@example.org', 'status' => Subscriber::STATUS_UNSUBSCRIBED, 'topics' => ['news']]);
        $csv = "Subscriber;First name;Surname;Status;Subscribed;IP address;Groups\n"
            ."ola@example.org;Ola;Kowal;active;2024-02-02 12:00:00;198.51.100.4;Wolontariusze, Darczyńcy\n"
            ."piotr@example.org;Piotr;;unsubscribed;2023-02-02;;Darczyńcy\n"
            ."stary@example.org;Stary;;active;2022-01-01;;\n"
            ."to-nie-adres;X;;active;;;\n";

        $this->actingAs($admin)->post(route('admin.newsletter.subskrybenci.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('subscribers.csv', $csv), 'source' => NewsletterImport::SOURCE_MAILERLITE_CSV,
        ])->assertRedirect();
        $import = NewsletterImport::latest('id')->first();

        $this->assertSame(['email' => 0, 'name' => 1, 'last_name' => 2, 'status' => 3, 'subscribed_at' => 4, 'ip' => 5, 'groups' => 6], $import->mapping);
        $this->assertSame([Subscriber::STATUS_CONFIRMED => 2, Subscriber::STATUS_UNSUBSCRIBED => 1], $import->summary['by_status']);
        $this->assertSame(1, $import->summary['invalid']);
        $this->assertSame(1, $import->summary['existing']);
        $this->actingAs($admin)->get(route('admin.newsletter.subskrybenci.import.show', $import))->assertOk()->assertSee('Wolontariusze')->assertSee('ola@example.org');

        $topicKey = array_key_first(Subscriber::availableTopics());
        $this->actingAs($admin)->put(route('admin.newsletter.subskrybenci.import.update', $import), [
            'action' => 'run', 'mapping' => $import->mapping,
            'group_map' => ['Wolontariusze' => 'new_list', 'Darczyńcy' => 'topic:'.$topicKey],
        ])->assertRedirect();
        $import->refresh();

        $this->assertSame(NewsletterImport::STATUS_COMPLETED, $import->status);
        $this->assertSame([2, 0, 1, 1], [$import->created_count, $import->updated_count, $import->skipped_count, $import->failed_count]);
        $ola = Subscriber::findByEmail('ola@example.org');
        $this->assertSame(Subscriber::STATUS_CONFIRMED, $ola->status);
        $this->assertSame('Ola Kowal', $ola->name);
        $this->assertContains($topicKey, $ola->topics);
        $this->assertTrue($ola->lists()->where('name', 'Wolontariusze')->exists());
        $this->assertSame('import MailerLite', $ola->consents()->sole()->source);
        $this->assertSame(Subscriber::STATUS_UNSUBSCRIBED, Subscriber::findByEmail('piotr@example.org')->status);
        $this->assertSame(Subscriber::STATUS_UNSUBSCRIBED, Subscriber::findByEmail('stary@example.org')->status);
        $this->assertFalse(Storage::disk('local')->exists((string) $import->file_path), 'plik usunięty po imporcie');

        $this->actingAs($admin)->get(route('admin.newsletter.subskrybenci.import.show', $import))->assertOk()
            ->assertSee('Raport importu')->assertSee('wypisany lub zablokowany w newsletterze')->assertSee('niepoprawny adres');
    }

    public function test_zwykly_plik_ze_statusem_oczekujacym_wysyla_double_opt_in(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.newsletter.subskrybenci.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('lista.csv', "email,name\njan@example.org,Jan\n"),
        ]);
        $import = NewsletterImport::latest('id')->first();

        $this->actingAs($admin)->put(route('admin.newsletter.subskrybenci.import.update', $import), ['action' => 'run', 'mapping' => $import->mapping, 'default_status' => 'pending'])->assertRedirect();

        $this->assertSame(Subscriber::STATUS_PENDING, Subscriber::findByEmail('jan@example.org')->status);
        Bus::assertDispatched(SendDoubleOptIn::class, 1);
    }

    public function test_duzy_import_idzie_do_kolejki_newsletter(): void
    {
        $this->fakeMailerLite();
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.newsletter.subskrybenci.import.mailerlite'), ['token' => self::TOKEN]);
        $import = NewsletterImport::latest('id')->first();

        Bus::fake([RunSubscriberImport::class]);
        $this->actingAs($admin)->put(route('admin.newsletter.subskrybenci.import.update', $import), ['action' => 'run']);

        Bus::assertDispatched(RunSubscriberImport::class, fn ($job) => $job->importId === $import->id && $job->queue === 'newsletter');
        $this->assertSame(NewsletterImport::STATUS_QUEUED, $import->fresh()->status);
        $this->actingAs($admin)->getJson(route('admin.newsletter.subskrybenci.import.progress', $import))->assertOk()->assertJson(['status' => 'queued', 'finished' => false]);
    }

    public function test_bez_uprawnienia_do_subskrybentow_brak_dostepu(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_NEWSLETTER_EDITOR]);

        $this->actingAs($editor)->get(route('admin.newsletter.subskrybenci.import'))->assertForbidden();
        $this->actingAs($editor)->post(route('admin.newsletter.subskrybenci.import.mailerlite'), ['token' => self::TOKEN])->assertForbidden();
    }
}
