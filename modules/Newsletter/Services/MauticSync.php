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
 * Przekazywanie subskrybentów newslettera do Mautic (API REST, Basic Auth):
 *  - confirmed / preferences_changed: tworzy lub aktualizuje kontakt (po e-mailu), taguje
 *    „newsletter” i „zapis-ze-strony” oraz tematami, dopisuje do segmentu z ustawień;
 *  - unsubscribed: „nie kontaktuj” (DNC) na kanale e-mail w Mautic;
 *  - anonymized: usuwa kontakt z Mautic (prawo do bycia zapomnianym).
 *
 * Nic nie rzuca — błąd trafia do subscribers.mautic_error i logu; `newsletter:sync-crm` ponawia.
 */
class MauticSync
{
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

    private function upsert(Subscriber $s, SiteSetting $site): void
    {
        if (! $s->isConfirmed()) {
            return;
        }

        $tags = array_merge(['newsletter', 'zapis-ze-strony'], array_map(fn ($t) => 'temat-' . $t, $s->topics ?? []));
        $res = $this->client($site)->post('/api/contacts/new', array_filter([
            'email'             => $s->email,
            'firstname'         => $s->name,
            'phone'             => $s->phone,
            'tags'              => $tags,
            'ipAddress'         => null,
            'overwriteWithBlank'=> false,
        ], fn ($v) => $v !== null));

        $id = (int) $res->json('contact.id');
        if (! $res->successful() || $id <= 0) {
            $this->fail($s, 'Mautic: zapis kontaktu nie powiódł się (HTTP ' . $res->status() . ').');

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
