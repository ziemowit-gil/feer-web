<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Donation;
use App\Services\DonationService;
use App\Services\Przelewy24Client;
use App\Support\FundraisingTexts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

/**
 * Publiczne zbiórki: lista, strona zbiórki z formularzem wpłaty (Przelewy24)
 * albo wplacam.ngo.pl, strony podziękowań (sukces / w toku / odrzucona)
 * i ponowienie płatności. Wszystkie teksty z FundraisingTexts.
 *
 * Metody: index(), show(), donate(), thanks(), retry().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class CampaignController extends Controller
{
    public function index(): View
    {
        $campaigns = Campaign::published()
            // Przed migracją modułu (kod wdraża się pushem) kolumny is_featured jeszcze nie ma.
            ->when(Schema::hasColumn('campaigns', 'is_featured'), fn ($q) => $q->orderByDesc('is_featured'))
            ->orderBy('order')
            ->orderByDesc('created_at')
            ->get();

        [$open, $finished] = $campaigns->partition(fn (Campaign $c) => $c->statusValue() === 'active'
            && ! ($c->ends_at && $c->ends_at->isBefore(today())));

        return view('campaigns.index', [
            'open' => $open->values(),
            'finished' => $finished->values(),
            'texts' => FundraisingTexts::for(null),
        ]);
    }

    public function show(string $slug, Przelewy24Client $przelewy24): View
    {
        $campaign = Campaign::published()->where('slug', $slug)->firstOrFail();
        $texts = $campaign->fundraisingTexts();

        $recent = $campaign->show_recent_donations
            ? $campaign->donations()->paid()->latest('paid_at')->limit(6)->get()
            : collect();

        return view('campaigns.show', [
            'campaign' => $campaign,
            'texts' => $texts,
            'vars' => $texts->vars(),
            'recent' => $recent,
            'onlineEnabled' => $przelewy24->configured(),
            'amounts' => $campaign->amounts(),
        ]);
    }

    public function donate(Request $request, string $slug, DonationService $donations): RedirectResponse
    {
        $campaign = Campaign::published()->where('slug', $slug)->firstOrFail();
        abort_unless($campaign->acceptsOnline(), 404);

        $data = $this->validateDonation($request, $campaign);

        try {
            $url = $donations->initiate($data, $request->ip(), $campaign);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('donation_error', self::errorText($e, $campaign));
        }

        return redirect()->away($url);
    }

    /**
     * Powrót z Przelewy24. Status bywa jeszcze „pending” — webhook dociera
     * zwykle po kilku sekundach, więc strona mówi o tym wprost.
     */
    public function thanks(string $slug, Donation $donation): View
    {
        $campaign = Campaign::withTrashed()->where('slug', $slug)->firstOrFail();
        abort_unless($donation->campaign_id === $campaign->id, 404);

        $texts = $campaign->fundraisingTexts();

        return view('fundraising.thanks', [
            'donation' => $donation,
            'campaign' => $campaign,
            'texts' => $texts,
            'vars' => $texts->vars($donation),
            'retryUrl' => route('kampanie.retry', [$campaign->slug, $donation]),
            'refreshUrl' => route('kampanie.thanks', [$campaign->slug, $donation]),
            'shareUrl' => route('kampanie.show', $campaign->slug),
            'canRetry' => $campaign->acceptsOnline(),
        ]);
    }

    public function retry(Request $request, string $slug, Donation $donation, DonationService $donations): RedirectResponse
    {
        $campaign = Campaign::published()->where('slug', $slug)->firstOrFail();
        abort_unless($donation->campaign_id === $campaign->id && $campaign->acceptsOnline(), 404);

        if ($donation->isPaid()) {
            return redirect()->route('kampanie.thanks', [$campaign->slug, $donation]);
        }

        try {
            $url = $donations->retry($donation, $request->ip());
        } catch (RuntimeException $e) {
            return redirect()->route('kampanie.thanks', [$campaign->slug, $donation])->with('donation_error', self::errorText($e, $campaign));
        }

        // null = pierwotna wpłata jednak przeszła; nie pobieramy drugi raz.
        return $url ? redirect()->away($url) : redirect()->route('kampanie.thanks', [$campaign->slug, $donation]);
    }

    /** Walidacja formularza wpłaty; komunikaty błędów z panelu. */
    private function validateDonation(Request $request, Campaign $campaign): array
    {
        $texts = $campaign->fundraisingTexts();
        $min = Campaign::MIN_AMOUNT;
        $max = Campaign::MAX_AMOUNT;
        $rangeMessage = $texts->get('error_amount_range', ['min' => $min, 'max' => number_format($max, 0, ',', ' ')]);
        $isCompany = $request->boolean('is_company');

        $data = $request->validate([
            'amount' => ['required', 'string', 'regex:/^(other|\d{1,6})$/'],
            'amount_other' => ['nullable', 'required_if:amount,other', 'numeric', 'min:'.$min, 'max:'.$max],
            'type' => ['nullable', Rule::in(array_keys(Donation::TYPES))],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9 +()\-]{6,}$/'],
            'company_name' => [$isCompany ? 'required' : 'nullable', 'string', 'max:200'],
            'nip' => ['nullable', 'string', 'max:20', function ($attribute, $value, $fail) use ($texts) {
                if (filled($value) && ! self::validNip($value)) {
                    $fail($texts->get('error_nip'));
                }
            }],
            'dedication' => ['nullable', 'string', 'max:300'],
            'dedication_public' => ['nullable', 'boolean'],
            'visibility' => ['required', 'in:name,anonymous'],
            'consent_rodo' => ['accepted'],
            'consent_newsletter' => ['nullable', 'boolean'],
        ], [
            'amount_other.required_if' => $rangeMessage,
            'amount_other.min' => $rangeMessage,
            'amount_other.max' => $rangeMessage,
            'amount_other.numeric' => $rangeMessage,
            'consent_rodo.accepted' => $texts->get('error_consent'),
            'phone.regex' => $texts->get('error_phone'),
            'amount.regex' => $texts->get('error_amount'),
        ], [
            'first_name' => mb_strtolower($texts->get('first_name_label')),
            'last_name' => mb_strtolower($texts->get('last_name_label')),
            'email' => mb_strtolower($texts->get('email_label')),
            'phone' => mb_strtolower($texts->get('phone_label')),
            'company_name' => mb_strtolower($texts->get('company_label')),
            'dedication' => mb_strtolower($texts->get('dedication_label')),
            'visibility' => mb_strtolower($texts->get('visibility_legend')),
        ]);

        if ($data['amount'] === 'other') {
            if (! $campaign->allow_custom_amount) {
                throw \Illuminate\Validation\ValidationException::withMessages(['amount' => $texts->get('error_amount')]);
            }
            $amount = (float) $data['amount_other'];
        } else {
            $amount = (float) $data['amount'];
            if (! in_array((int) $amount, $campaign->amounts(), true)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['amount' => $texts->get('error_amount')]);
            }
        }

        return [
            'amount_grosze' => (int) round($amount * 100),
            'type' => $campaign->allow_recurring ? ($data['type'] ?? 'one_time') : 'one_time',
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'company_name' => $isCompany ? ($data['company_name'] ?? null) : null,
            'nip' => $isCompany && filled($data['nip'] ?? null) ? preg_replace('/\D+/', '', $data['nip']) : null,
            'dedication' => filled($data['dedication'] ?? null) ? trim($data['dedication']) : null,
            'dedication_public' => $request->boolean('dedication_public'),
            'is_anonymous' => $data['visibility'] === 'anonymous',
            'consent_newsletter' => $request->boolean('consent_newsletter'),
        ];
    }

    /** Komunikat wyjątku DonationService w brzmieniu z panelu. */
    public static function errorText(RuntimeException $e, ?Campaign $campaign): string
    {
        $texts = FundraisingTexts::for($campaign);
        $key = match ($e->getCode()) {
            DonationService::ERROR_UNAVAILABLE => 'unavailable',
            DonationService::ERROR_GATEWAY => 'error_gateway',
            DonationService::ERROR_PROCESSING => 'error_processing',
            default => null,
        };

        return $key ? $texts->get($key, $texts->vars()) : $e->getMessage();
    }

    /** NIP: 10 cyfr z poprawną sumą kontrolną. */
    public static function validNip(string $value): bool
    {
        $digits = preg_replace('/\D+/', '', $value);
        if (strlen($digits) !== 10) {
            return false;
        }

        $weights = [6, 5, 7, 2, 3, 4, 5, 6, 7];
        $sum = 0;
        foreach ($weights as $i => $w) {
            $sum += $w * (int) $digits[$i];
        }

        return $sum % 11 === (int) $digits[9];
    }
}
