<?php

declare(strict_types=1);

namespace Modules\Newsletter\Services;

use App\Models\SiteSetting;
use App\Models\Subscriber;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Newsletter ↔ Mautic (API REST, Basic Auth). CMS zbiera zapisy i zgody, Mautic wysyła.
 *  - sendConfirmation(): e-mail potwierdzający wysyła Mautic (szablon o ID z ustawień, token {confirm_url}
 *    = link do potwierdzenia w CMS — dowód zgody zostaje w CMS). Kontakt dostaje tag „newsletter-do-potwierdzenia”.
 *  - confirmed / preferences_changed: kontakt + tagi + segment z ustawień, zdjęcie tagu oczekiwania i blokady wysyłki;
 *  - unsubscribed: „nie kontaktuj” (DNC) na kanale e-mail; anonymized: usunięcie kontaktu;
 *  - handleWebhook(): Mautic zgłasza zmianę subskrypcji kanału — CMS NIE ufa treści webhooka, tylko pobiera
 *    kontakt z API Mautic i sprawdza jego blokady (DNC). Wypis/odbicie w Mautic = wypis/odbicie w CMS.
 *
 * Synchronizacja nic nie rzuca — błąd trafia do subscribers.mautic_error i logu; `newsletter:sync-crm` ponawia.
 */
class MauticSync
{
    public const TAG_PENDING = 'newsletter-do-potwierdzenia';

    public function enabled(?SiteSetting $site = null): bool
    {
        $site ??= SiteSetting::current();

        return Schema::hasColumn('site_settings', 'newsletter_mautic_sync')
            && Schema::hasColumn('subscribers', 'mautic_contact_id')
            && (bool) $site->newsletter_mautic_sync
            && filled($site->newsletter_mautic_url)
            && filled($site->newsletter_mautic_username)
            && filled($site->newsletter_mautic_password);
    }

    /** Mautic wysyła e-mail potwierdzający (gdy ustawiono ID szablonu). */
    public function confirmsSignups(?SiteSetting $site = null): bool
    {
        $site ??= SiteSetting::current();

        return $this->enabled($site)
            && Schema::hasColumn('site_settings', 'newsletter_mautic_confirm_email')
            && (int) $site->newsletter_mautic_confirm_email > 0;
    }

    /** Kampanie wysyłane w Mautic — CMS chowa tworzenie i wysyłkę kampanii. */
    public static function sendsCampaigns(?SiteSetting $site = null): bool
    {
        $site ??= SiteSetting::current();

        return Schema::hasColumn('site_settings', 'newsletter_mautic_campaigns')
            && (bool) $site->newsletter_mautic_campaigns
            && filled($site->newsletter_mautic_url);
    }

    public function sync(Subscriber $s, string $event): void
    {
        $site = SiteSetting::current();
        if (! $this->enabled($site)) {
            return;
        }

        try {
            match ($event) {
                'confirmed', 'preferences_changed' => $this->upsert($s, $site),
                'unsubscribed'                     => $this->doNotContact($s, $site),
                'anonymized'                       => $this->delete($s, $site),
                default                            => null,
            };
        } catch (Throwable $e) {
            $this->fail($s, $e->getMessage());
        }
    }

    /**
     * Zakłada kontakt „do potwierdzenia” i każe Mautic wysłać e-mail potwierdzający z linkiem do CMS.
     * false = nie wyszło (CMS wyśle wtedy własny e-mail, żeby zapis nie przepadł).
     */
    public function sendConfirmation(Subscriber $s): bool
    {
        $site = SiteSetting::current();
        if (! $this->confirmsSignups($site) || ! $s->isPending() || blank($s->token)) {
            return false;
        }

        try {
            $id = $this->saveContact($s, $site, [self::TAG_PENDING]);
            if (! $id) {
                return false;
            }
            $emailId = (int) $site->newsletter_mautic_confirm_email;
            $res = $this->client($site)->post("/api/emails/{$emailId}/contact/{$id}/send", [
                'tokens' => ['{confirm_url}' => route('newsletter.confirm', $s->token)],
            ]);
            if (! $res->successful() || ! $res->json('success')) {
                $this->fail($s, "Mautic: nie wysłano e-maila potwierdzającego (szablon {$emailId}, HTTP {$res->status()}).", $id);

                return false;
            }
            $s->forceFill(['mautic_contact_id' => $id, 'mautic_error' => null])->saveQuietly();

            return true;
        } catch (Throwable $e) {
            $this->fail($s, $e->getMessage());

            return false;
        }
    }

    /**
     * Webhook z Mautic (zmiana subskrypcji kanału). Zwraca liczbę zmienionych subskrybentów.
     * Treść służy tylko do wskazania ID kontaktów — stan bierzemy z API Mautic.
     */
    public function handleWebhook(array $payload): int
    {
        $site = SiteSetting::current();
        if (! $this->enabled($site)) {
            return 0;
        }

        // contact.id / lead.id w zdarzeniach mautic.lead_channel_subscription_changed i pokrewnych.
        $ids = [];
        foreach ($payload as $event => $items) {
            if (! is_string($event) || ! str_starts_with($event, 'mautic.')) {
                continue;
            }
            foreach ((array) $items as $item) {
                $id = $item['contact']['id'] ?? $item['lead']['id'] ?? null;
                if (is_numeric($id)) {
                    $ids[(int) $id] = true;
                }
            }
        }

        $changed = 0;
        foreach (array_slice(array_keys($ids), 0, 50) as $id) {
            try {
                $changed += $this->applyContactState($id, $site) ? 1 : 0;
            } catch (Throwable $e) {
                Log::warning('Mautic webhook: ' . $e->getMessage(), ['contact' => $id]);
            }
        }

        return $changed;
    }

    private function applyContactState(int $id, SiteSetting $site): bool
    {
        $res = $this->client($site)->get("/api/contacts/{$id}");
        if (! $res->successful()) {
            return false;
        }
        $email = (string) ($res->json('contact.fields.all.email') ?? '');
        $s = $email !== '' ? Subscriber::findByEmail($email) : null;
        if (! $s || $s->anonymized_at || ! in_array($s->status, [Subscriber::STATUS_CONFIRMED, Subscriber::STATUS_PENDING], true)) {
            return false;
        }

        $dnc = collect((array) $res->json('contact.doNotContact', []))->firstWhere('channel', 'email');
        if (! $dnc) {
            return false;
        }

        // Mautic: reason 1 = wypisał się, 2 = odbicie, 3 = ręcznie (m.in. nasz wypis z CMS).
        $reason = (int) ($dnc['reason'] ?? 1);
        if ($reason === 2) {
            $s->forceFill(['status' => Subscriber::STATUS_BOUNCED, 'bounced_at' => now(), 'mautic_contact_id' => $id])->saveQuietly();
            $s->logEvent('bounced', ['via' => 'mautic']);
        } else {
            $s->unsubscribe('mautic', null, mb_substr((string) ($dnc['comments'] ?? 'Wypis w Mautic'), 0, 255));
            $s->forceFill(['mautic_contact_id' => $id, 'mautic_synced_at' => now()])->saveQuietly();
        }

        return true;
    }

    private function upsert(Subscriber $s, SiteSetting $site): void
    {
        if (! $s->isConfirmed()) {
            return;
        }

        $id = $this->saveContact($s, $site, ['-' . self::TAG_PENDING]);
        if (! $id) {
            return;
        }

        if ($segment = (int) $site->newsletter_mautic_segment) {
            $seg = $this->client($site)->post("/api/segments/{$segment}/contact/{$id}/add");
            if (! $seg->successful()) {
                $this->fail($s, "Mautic: kontakt zapisany, ale nie dodany do segmentu {$segment} (HTTP {$seg->status()}).", $id);

                return;
            }
        }

        // Ponowny zapis po wcześniejszym wypisaniu zdejmuje blokadę wysyłki.
        $this->client($site)->post("/api/contacts/{$id}/dnc/email/remove");

        $s->forceFill(['mautic_contact_id' => $id, 'mautic_synced_at' => now(), 'mautic_error' => null])->saveQuietly();
        $s->logEvent('mautic_synced', ['contact_id' => $id]);
    }

    /** Tworzy lub aktualizuje kontakt (Mautic łączy po e-mailu). Zwraca ID albo null (błąd zapisany). */
    private function saveContact(Subscriber $s, SiteSetting $site, array $extraTags = []): ?int
    {
        $tags = array_merge(['newsletter', 'zapis-ze-strony'], array_map(fn ($t) => 'temat-' . $t, $s->topics ?? []), $extraTags);
        $res = $this->client($site)->post('/api/contacts/new', array_filter([
            'email'              => $s->email,
            'firstname'          => $s->name,
            'phone'              => $s->phone,
            'tags'               => $tags,
            'overwriteWithBlank' => false,
        ], fn ($v) => $v !== null));

        $id = (int) $res->json('contact.id');
        if (! $res->successful() || $id <= 0) {
            $this->fail($s, 'Mautic: zapis kontaktu nie powiódł się (HTTP ' . $res->status() . ').');

            return null;
        }

        return $id;
    }

    private function doNotContact(Subscriber $s, SiteSetting $site): void
    {
        $id = $s->mautic_contact_id ?: $this->findId($s, $site);
        if (! $id) {
            return;
        }
        $res = $this->client($site)->post("/api/contacts/{$id}/dnc/email/add", [
            'reason'   => 3, // ręcznie / z zewnątrz
            'comments' => 'Wypisanie na feer.org.pl',
        ]);
        if (! $res->successful()) {
            $this->fail($s, 'Mautic: nie udało się zablokować wysyłki (HTTP ' . $res->status() . ').', $id);

            return;
        }
        $s->forceFill(['mautic_contact_id' => $id, 'mautic_synced_at' => now(), 'mautic_error' => null])->saveQuietly();
        $s->logEvent('mautic_unsubscribed', ['contact_id' => $id]);
    }

    private function delete(Subscriber $s, SiteSetting $site): void
    {
        $id = $s->mautic_contact_id;
        if (! $id) {
            return;
        }
        $res = $this->client($site)->delete("/api/contacts/{$id}/delete");
        if (! $res->successful() && $res->status() !== 404) {
            $this->fail($s, 'Mautic: nie udało się usunąć kontaktu (HTTP ' . $res->status() . ').', $id);

            return;
        }
        $s->forceFill(['mautic_contact_id' => null, 'mautic_synced_at' => now(), 'mautic_error' => null])->saveQuietly();
    }

    private function findId(Subscriber $s, SiteSetting $site): ?int
    {
        $res = $this->client($site)->get('/api/contacts', ['search' => 'email:' . $s->email, 'limit' => 1, 'minimal' => 1]);
        $contacts = (array) $res->json('contacts', []);
        $first = reset($contacts);

        return is_array($first) && isset($first['id']) ? (int) $first['id'] : null;
    }

    private function client(SiteSetting $site): PendingRequest
    {
        return Http::baseUrl(rtrim((string) $site->newsletter_mautic_url, '/'))
            ->withBasicAuth((string) $site->newsletter_mautic_username, (string) $site->newsletter_mautic_password)
            ->acceptJson()->asJson()
            ->timeout(10);
    }

    private function fail(Subscriber $s, string $message, ?int $id = null): void
    {
        Log::warning('Newsletter → Mautic: ' . $message, ['subscriber' => $s->id]);
        $s->forceFill(array_filter(['mautic_error' => mb_substr($message, 0, 500), 'mautic_contact_id' => $id]))->saveQuietly();
    }
}
