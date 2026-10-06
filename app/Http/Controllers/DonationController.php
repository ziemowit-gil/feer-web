<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\SiteSetting;
use App\Services\DonationService;
use App\Services\Przelewy24Client;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/**
 * Strona „Darowizna jednorazowa" (/wsparcie/darowizna): formularz wpłaty
 * online przez Przelewy24, dane do przelewu tradycyjnego, druk przelewu (PDF)
 * i lista ostatnich wpłat.
 *
 * Metody: show(), store(), thanks(), slip().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class DonationController extends Controller
{
    /** Najniższa i najwyższa kwota wpłaty online (PLN). */
    public const MIN_AMOUNT = 5;
    public const MAX_AMOUNT = 50000;

    public function show(Przelewy24Client $przelewy24): View
    {
        $recent = Donation::forCurrentSite()->paid()->latest('paid_at')->limit(6)->get();

        return view('donation.show', [
            'amounts' => SiteSetting::current()->donationAmounts(),
            'impacts' => SiteSetting::current()->donationImpacts(),
            'recent' => $recent,
            'onlineEnabled' => $przelewy24->configured(),
            'minAmount' => self::MIN_AMOUNT,
            'maxAmount' => self::MAX_AMOUNT,
        ]);
    }

    public function store(Request $request, DonationService $donations): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required'],
            'amount_other' => ['nullable', 'required_if:amount,other', 'numeric', 'min:' . self::MIN_AMOUNT, 'max:' . self::MAX_AMOUNT],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9 +()\-]{6,}$/'],
            'visibility' => ['required', 'in:name,anonymous'],
            'consent_rodo' => ['accepted'],
            'consent_newsletter' => ['nullable', 'boolean'],
        ], [
            'amount_other.required_if' => 'Wpisz kwotę darowizny.',
            'amount_other.min' => 'Najniższa kwota to :min zł.',
            'amount_other.max' => 'Najwyższa kwota online to :max zł — większą darowiznę prosimy przekazać przelewem.',
            'consent_rodo.accepted' => 'Zaznacz zgodę na przetwarzanie danych osobowych.',
            'phone.regex' => 'Numer telefonu może zawierać tylko cyfry, spacje i znaki + ( ) -.',
            'visibility.required' => 'Wybierz, czy pokazać Twoje imię na liście wpłat.',
        ], [
            'first_name' => 'imię',
            'last_name' => 'nazwisko',
            'email' => 'e-mail',
            'phone' => 'numer telefonu',
        ]);

        $amount = $data['amount'] === 'other'
            ? (float) $data['amount_other']
            : (float) $data['amount'];

        if ($data['amount'] !== 'other' && ! in_array((int) $amount, SiteSetting::current()->donationAmounts(), true)) {
            return back()->withInput()->withErrors(['amount' => 'Wybierz kwotę z listy albo wpisz własną.']);
        }

        try {
            $url = $donations->initiate([
                'amount_grosze' => (int) round($amount * 100),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'is_anonymous' => $data['visibility'] === 'anonymous',
                'consent_newsletter' => $request->boolean('consent_newsletter'),
            ], $request->ip());
        } catch (RuntimeException $e) {
            return back()->withInput()->with('donation_error', $e->getMessage());
        }

        return redirect()->away($url);
    }

    /**
     * Powrót z Przelewy24. Status bywa jeszcze „pending" — webhook dociera
     * zwykle po kilku sekundach, więc strona mówi o tym wprost zamiast
     * udawać, że płatność się nie udała.
     */
    public function thanks(Donation $donation): View
    {
        abort_unless($donation->site_id === SiteSetting::current()->id, 404);

        return view('donation.thanks', compact('donation'));
    }

    /** Wypełniony druk przelewu tradycyjnego (PDF) z danymi organizacji. */
    public function slip(Request $request)
    {
        $settings = SiteSetting::current();
        abort_unless(filled($settings->bank_account_number), 404);

        $amount = (float) str_replace(',', '.', (string) $request->query('kwota', ''));
        $amount = ($amount >= 1 && $amount <= 1000000) ? $amount : null;

        return Pdf::loadView('donation.slip', [
            'siteSettings' => $settings,
            'amount' => $amount,
            'title' => config('szo.donation_purpose', 'Darowizna na cele statutowe'),
        ])->setPaper('a4')->download('druk-przelewu.pdf');
    }
}
