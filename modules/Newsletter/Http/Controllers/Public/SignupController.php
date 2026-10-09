<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\Subscriber;
use App\Support\SpamGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Modules\Newsletter\Jobs\SendDoubleOptIn;
use Modules\Newsletter\Jobs\SyncSubscriberToCrm;
use Modules\Newsletter\Models\NewsletterConsent;
use Modules\Newsletter\Models\NewsletterForm;
use Modules\Newsletter\Models\NewsletterList;

/**
 * Publiczny zapis na newsletter: strona systemowa /newsletter, endpoint widgetu,
 * ekran „sprawdź skrzynkę" i potwierdzenie double opt-in.
 *
 * Metody: show(), showForm(), store(), pending(), confirm().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class SignupController extends Controller
{
    /** Strona systemowa /newsletter z domyślnym formularzem. */
    public function show()
    {
        $site = SiteSetting::current();
        $form = NewsletterForm::defaultFor($site->id);

        return view('newsletter::public.show', ['form' => $form, 'embedCode' => $form ? null : $site->newsletter_code]);
    }

    public function showForm(NewsletterForm $form)
    {
        abort_unless($form->is_active, 404);

        return view('newsletter::public.show', ['form' => $form, 'embedCode' => null]);
    }

    /** Rejestruje subskrybenta i wysyła e-mail potwierdzający (double opt-in). */
    public function store(Request $request)
    {
        $site = SiteSetting::current();
        $form = $request->filled('form_id') ? NewsletterForm::find($request->integer('form_id')) : NewsletterForm::defaultFor($site->id);
        $form ??= NewsletterForm::makeDefault();

        $topicKeys = array_keys($form->topicOptions());

        $data = $request->validate([
            'email'    => ['required', 'email:rfc', 'max:255'],
            'name'     => ['nullable', 'string', 'max:100'],
            'phone'    => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9 \-]{7,20}$/'],
            'topics'   => [$form->show_topics ? 'required' : 'nullable', 'array', 'min:1'],
            'topics.*' => ['string', Rule::in($topicKeys)],
            'consent'  => ['accepted'],
            'consent_sms' => ['nullable', 'boolean'],
            'source'   => ['nullable', 'string', 'max:64'],
        ], [
            'email.required' => 'Podaj adres e-mail.',
            'email.email'    => 'Ten adres wygląda na niepoprawny. Sprawdź, czy zawiera znak @ i domenę.',
            'topics.required'=> 'Wybierz przynajmniej jeden temat.',
            'consent.accepted' => 'Zaznacz zgodę, aby się zapisać.',
            'phone.regex'    => 'Numer telefonu może zawierać tylko cyfry, spacje, myślniki i znak +.',
        ]);

        // Antyspam: honeypot + żeton czasowy + zadanie tekstowe. Bot dostaje zwykłe potwierdzenie.
        $spam = SpamGuard::inspect($request, [$data['email'], $data['name'] ?? ''], 'newsletter');
        if ($spam !== null) {
            if ($spam['silent']) {
                return $this->respondPending($request, $form);
            }

            return $this->fail($request, ['form_answer' => $spam['message'] ?? 'Odpowiedź na zadanie antyspamowe jest nieprawidłowa.']);
        }

        // Limit: 3 wiadomości potwierdzające na adres w ciągu doby.
        $key = 'newsletter-doi:' . Subscriber::hashEmail($data['email']);
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return $this->respondPending($request, $form);
        }

        $topics = $data['topics'] ?? ($form->default_topics ?: ['news']);
        $source = $data['source'] ?? ('form_' . $form->slug);

        DB::transaction(function () use ($data, $topics, $source, $form, $request, $site, $key) {
            $subscriber = Subscriber::findByEmail($data['email']);
            $wasConfirmed = $subscriber?->isConfirmed() ?? false;

            if (! $subscriber) {
                $subscriber = new Subscriber(['email' => $data['email'], 'site_id' => $site->id, 'source' => $source]);
            }

            $subscriber->name   = $data['name'] ?? $subscriber->name;
            $subscriber->phone  = ($form->ask_phone && ! empty($data['phone'])) ? preg_replace('/[^0-9+]/', '', $data['phone']) : $subscriber->phone;
            $subscriber->topics = $wasConfirmed ? array_values(array_unique(array_merge($subscriber->topics ?? [], $topics))) : $topics;

            $channels = $subscriber->channels ?? ['email'];
            if ($form->offer_sms && ! empty($data['consent_sms']) && $subscriber->phone) {
                $channels[] = 'sms';
            }
            $subscriber->channels = array_values(array_unique($channels));

            if (! $wasConfirmed) {
                $subscriber->status = Subscriber::STATUS_PENDING;
                $subscriber->token  = Subscriber::generateToken();
                $subscriber->confirmed_at = null;
                $subscriber->unsubscribed_at = null;
            }
            $subscriber->save();

            $this->recordConsent($subscriber, 'email', $form, $request, $source, $wasConfirmed);
            if (in_array('sms', $subscriber->channels, true) && ! empty($data['consent_sms'])) {
                $this->recordConsent($subscriber, 'sms', $form, $request, $source, $wasConfirmed, 'Zgoda na SMS: ' . $form->consent_text);
            }

            foreach (NewsletterList::whereIn('id', (array) ($form->list_ids ?? []))->get() as $list) {
                $list->subscribers()->syncWithoutDetaching([$subscriber->id => ['added_at' => now()]]);
            }

            $form->increment('submissions_count');

            if ($wasConfirmed) {
                $subscriber->logEvent('preferences_changed', ['via' => 'signup_form', 'topics' => $topics]);
                SyncSubscriberToCrm::dispatch($subscriber->id, 'preferences_changed', $form->szo_form_slug);
            } else {
                $subscriber->logEvent($subscriber->wasRecentlyCreated ? 'subscribed' : 'resubscribed', ['source' => $source, 'topics' => $topics]);
                RateLimiter::hit($key, 86400);
                SendDoubleOptIn::dispatch($subscriber->id);
            }
        });

        return $this->respondPending($request, $form);
    }

    public function pending(Request $request)
    {
        return view('newsletter::public.pending', ['form' => NewsletterForm::defaultFor(SiteSetting::current()->id)]);
    }

    /** Potwierdza subskrypcję przez token z e-maila. */
    public function confirm(string $token)
    {
        $subscriber = Subscriber::where('token', $token)->firstOrFail();
        $ttl = (int) (SiteSetting::current()->newsletter_doi_ttl_days ?: 7);

        if ($subscriber->isPending() || $subscriber->status === Subscriber::STATUS_EXPIRED) {
            if ($subscriber->status === Subscriber::STATUS_EXPIRED || ($subscriber->confirmation_sent_at && $subscriber->confirmation_sent_at->lt(now()->subDays($ttl)))) {
                return view('newsletter::public.confirmed', ['subscriber' => $subscriber, 'expired' => true]);
            }

            $subscriber->forceFill(['status' => Subscriber::STATUS_CONFIRMED, 'confirmed_at' => now()])->save();
            $subscriber->consents()->whereNull('confirmed_at')->whereNull('revoked_at')->update(['confirmed_at' => now()]);
            $subscriber->logEvent('confirmed');

            $form = NewsletterForm::where('slug', str_replace('form_', '', (string) $subscriber->source))->first();
            SyncSubscriberToCrm::dispatch($subscriber->id, 'confirmed', $form?->szo_form_slug);
        }

        return view('newsletter::public.confirmed', ['subscriber' => $subscriber, 'expired' => false]);
    }

    private function recordConsent(Subscriber $s, string $channel, NewsletterForm $form, Request $request, string $source, bool $confirmed, ?string $text = null): void
    {
        NewsletterConsent::create([
            'subscriber_id'  => $s->id,
            'channel'        => $channel,
            'clause_id'      => $form->clause_id,
            'clause_version' => (string) ($form->updated_at?->format('YmdHi') ?: '1'),
            'clause_text'    => $text ?: $form->consent_text,
            'source'         => $source,
            'method'         => $confirmed ? 'preferences' : 'doi_pending',
            'ip_hash'        => Subscriber::hashIp($request->ip()),
            'user_agent'     => mb_substr((string) $request->userAgent(), 0, 255),
            'granted_at'     => now(),
            'confirmed_at'   => $confirmed ? now() : null,
        ]);
    }

    private function respondPending(Request $request, NewsletterForm $form)
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $form->success_message]);
        }

        return redirect()->route('newsletter.pending');
    }

    private function fail(Request $request, array $errors)
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'errors' => array_map(fn ($m) => (array) $m, $errors)], 422);
        }

        return back()->withErrors($errors)->withInput();
    }
}
