<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Newsletter\Jobs\SyncSubscriberToCrm;
use Modules\Newsletter\Models\NewsletterList;

/**
 * Samoobsługa subskrybenta: wypis (także one-click, RFC 8058), preferencje,
 * eksport danych, usunięcie konta, powiązanie push.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class PreferencesController extends Controller
{
    public function unsubscribe(string $token)
    {
        $subscriber = Subscriber::where('token', $token)->firstOrFail();

        return view('newsletter::public.unsubscribe', ['subscriber' => $subscriber, 'done' => false]);
    }

    /** POST z formularza albo one-click z klienta poczty (List-Unsubscribe=One-Click). */
    public function doUnsubscribe(Request $request, string $token)
    {
        $subscriber = Subscriber::where('token', $token)->firstOrFail();
        $via = $request->input('List-Unsubscribe') === 'One-Click' ? 'one_click' : 'link';
        $campaignId = $request->filled('c') ? (int) $request->input('c') : null;

        $subscriber->unsubscribe($via, $campaignId, $request->input('reason') ? mb_substr((string) $request->input('reason'), 0, 255) : null);
        SyncSubscriberToCrm::dispatch($subscriber->id, 'unsubscribed');

        if ($via === 'one_click' || $request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return view('newsletter::public.unsubscribe', ['subscriber' => $subscriber, 'done' => true]);
    }

    public function edit(string $token)
    {
        $subscriber = Subscriber::where('token', $token)->firstOrFail();

        return view('newsletter::public.preferences', [
            'subscriber' => $subscriber,
            'topics'     => Subscriber::availableTopics(),
            'lists'      => NewsletterList::where('is_public', true)->orderBy('name')->get(),
            'pushEnabled'=> (bool) \App\Models\SiteSetting::current()->newsletter_webpush_enabled && filled(config('webpush.vapid.public_key')),
        ]);
    }

    public function update(Request $request, string $token)
    {
        $subscriber = Subscriber::where('token', $token)->firstOrFail();

        $data = $request->validate([
            'name'       => ['nullable', 'string', 'max:100'],
            'topics'     => ['required', 'array', 'min:1'],
            'topics.*'   => ['string', Rule::in(array_keys(Subscriber::availableTopics()))],
            'channels'   => ['nullable', 'array'],
            'channels.*' => ['string', Rule::in(array_keys(Subscriber::CHANNELS))],
            'lists'      => ['nullable', 'array'],
            'lists.*'    => ['integer'],
        ], ['topics.required' => 'Wybierz przynajmniej jeden temat — albo wypisz się całkowicie.']);

        $channels = array_values(array_unique(array_merge(['email'], $data['channels'] ?? [])));

        $subscriber->forceFill([
            'name'     => $data['name'] ?? $subscriber->name,
            'topics'   => $data['topics'],
            'channels' => $channels,
        ]);
        if ($subscriber->status === Subscriber::STATUS_UNSUBSCRIBED) {
            // Ponowny zapis z poziomu preferencji wymaga DOI.
            $subscriber->status = Subscriber::STATUS_PENDING;
            $subscriber->token  = Subscriber::generateToken();
            \Modules\Newsletter\Jobs\SendDoubleOptIn::dispatch($subscriber->id);
        }
        $subscriber->save();

        $public = NewsletterList::where('is_public', true)->pluck('id')->all();
        $chosen = array_values(array_intersect($public, array_map('intval', $data['lists'] ?? [])));
        $subscriber->lists()->detach(array_diff($public, $chosen));
        $subscriber->lists()->syncWithoutDetaching(array_fill_keys($chosen, ['added_at' => now()]));

        $subscriber->logEvent('preferences_changed', ['topics' => $data['topics'], 'channels' => $channels]);
        SyncSubscriberToCrm::dispatch($subscriber->id, 'preferences_changed');

        return redirect()->route('newsletter.preferences', ['token' => $subscriber->token])->with('status', 'Preferencje zostały zapisane.');
    }

    /** Prawo dostępu / przenoszenia (JSON). */
    public function export(string $token)
    {
        $subscriber = Subscriber::where('token', $token)->firstOrFail();
        $subscriber->logEvent('data_exported');

        return response()->json($subscriber->exportPersonalData(), 200, [
            'Content-Disposition' => 'attachment; filename="moje-dane-newsletter.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /** Prawo do bycia zapomnianym. */
    public function destroy(string $token)
    {
        $subscriber = Subscriber::where('token', $token)->firstOrFail();
        SyncSubscriberToCrm::dispatchSync($subscriber->id, 'anonymized');
        $subscriber->anonymize();

        return view('newsletter::public.deleted');
    }

    /** Łączy subskrypcję Web Push przeglądarki z subskrybentem (po zgodzie w preferencjach). */
    public function linkPush(Request $request, string $token)
    {
        $subscriber = Subscriber::where('token', $token)->firstOrFail();
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        PushSubscription::updateOrCreate(['endpoint' => $data['endpoint']], [
            'p256dh_key' => $data['keys']['p256dh'], 'auth_token' => $data['keys']['auth'], 'subscriber_id' => $subscriber->id,
        ]);
        $subscriber->forceFill(['channels' => array_values(array_unique(array_merge($subscriber->channels ?? ['email'], ['webpush'])))])->save();
        $subscriber->consents()->create([
            'channel' => 'webpush', 'clause_text' => 'Zgoda na powiadomienia push w przeglądarce (preferencje subskrybenta).',
            'source' => 'preferences', 'method' => 'preferences', 'ip_hash' => Subscriber::hashIp($request->ip()),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255), 'granted_at' => now(), 'confirmed_at' => now(),
        ]);
        $subscriber->logEvent('webpush_added');

        return response()->json(['ok' => true]);
    }
}
