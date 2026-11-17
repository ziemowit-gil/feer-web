<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\CampaignController as PublicCampaignController;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Donation;
use App\Services\DonationService;
use App\Support\SimpleXlsx;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Panel „Wpłaty”: lista darowizn bieżącej witryny z filtrami, ręczne
 * dodawanie wpłat (przelew tradycyjny itp.), zmiana statusu (zwrot),
 * ponowna wysyłka podziękowania i eksport CSV/XLSX dla księgowości
 * (z tymi samymi filtrami co lista).
 *
 * Metody: index(), create(), store(), edit(), update(), resendThanks(), export().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class DonationController extends Controller
{
    public function index(Request $request): View
    {
        $query = $this->filtered($request);

        $totals = (clone $query)->toBase()
            ->selectRaw("COUNT(*) as cnt, COALESCE(SUM(CASE WHEN status = 'paid' THEN amount_grosze ELSE 0 END), 0) as paid_sum")
            ->first();

        return view('admin.donations.index', [
            'donations' => $query->with('campaign')->latest('created_at')->paginate(50)->withQueryString(),
            'campaigns' => Campaign::withTrashed()->orderBy('title')->get(['id', 'title']),
            'filters' => $this->filters($request),
            'totalCount' => (int) ($totals->cnt ?? 0),
            'paidSum' => (int) ($totals->paid_sum ?? 0),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.donations.form', [
            'donation' => new Donation([
                'campaign_id' => $request->integer('kampania') ?: null,
                'type' => 'one_time',
                'is_anonymous' => true,
            ]),
            'campaigns' => Campaign::orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function store(Request $request, DonationService $service): RedirectResponse
    {
        $data = $request->validate($this->rules(true));

        $donation = $service->recordManual([
            'campaign_id' => $data['campaign_id'] ?? null,
            'amount_grosze' => (int) round(((float) str_replace(',', '.', $data['amount'])) * 100),
            'type' => $data['type'],
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? null,
            'company_name' => $data['company_name'] ?? null,
            'nip' => filled($data['nip'] ?? null) ? preg_replace('/\D+/', '', $data['nip']) : null,
            'dedication' => $data['dedication'] ?? null,
            'dedication_public' => $request->boolean('dedication_public'),
            'is_anonymous' => $request->boolean('is_anonymous'),
            'admin_note' => $data['admin_note'] ?? null,
            'paid_at' => Carbon::parse($data['paid_at'])->setTimeFrom(now()),
        ], $request->user(), $request->boolean('send_thanks') && filled($data['email'] ?? null));

        return redirect()->route('admin.darowizny.index', $donation->campaign_id ? ['kampania' => $donation->campaign_id] : [])
            ->with('status', 'Wpłata '.$donation->amountLabel().' została dodana.');
    }

    public function edit(Donation $donation): View
    {
        $this->authorizeSite($donation);

        return view('admin.donations.edit', ['donation' => $donation->load('campaign', 'creator')]);
    }

    /** Zmiana statusu (np. zwrot) i notatki. */
    public function update(Request $request, Donation $donation, DonationService $service): RedirectResponse
    {
        $this->authorizeSite($donation);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Donation::STATUSES))],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        // Opłaconą wpłatę online można tylko zwrócić — „failed”/„pending” cofnąłby i tak donations:sync.
        if (! $donation->isManual() && $donation->isPaid() && ! in_array($data['status'], ['paid', 'refunded'], true)) {
            return back()->withErrors(['status' => 'Opłaconą wpłatę online można oznaczyć tylko jako zwróconą.'])->withInput();
        }

        $donation->update(['admin_note' => $data['admin_note'] ?? null]);
        $service->changeStatus($donation, $data['status'], $request->boolean('send_thanks'));

        return redirect()->route('admin.darowizny.edit', $donation)->with('status', 'Wpłata została zapisana.');
    }

    /** Ponowna wysyłka podziękowania (np. po błędzie poczty). */
    public function resendThanks(Donation $donation): RedirectResponse
    {
        $this->authorizeSite($donation);
        abort_unless($donation->isPaid() && filled($donation->email), 422);

        $donation->forceFill(['thanked_at' => null])->save();
        (new \App\Listeners\SendDonationThankYou)->handle(new \App\Events\DonationPaid($donation));

        return back()->with('status', $donation->fresh()->thanked_at
            ? 'Podziękowanie wysłane na '.$donation->email.'.'
            : 'Nie udało się wysłać podziękowania — sprawdź ustawienia poczty.');
    }

    /** Eksport z bieżącymi filtrami: ?format=csv (domyślnie) albo xlsx. */
    public function export(Request $request): StreamedResponse|BinaryFileResponse
    {
        $header = ['ID', 'Data wpłaty', 'Data utworzenia', 'Status', 'Kwota', 'Waluta', 'Rodzaj', 'Źródło', 'Zbiórka',
            'Imię', 'Nazwisko', 'Firma', 'NIP', 'E-mail', 'Telefon', 'Anonimowa', 'Dedykacja', 'Nr transakcji P24', 'Zgoda newsletter', 'Notatka'];

        $rows = function () use ($request) {
            foreach ($this->filtered($request)->with('campaign')->orderBy('created_at')->cursor() as $d) {
                yield [
                    $d->id,
                    $d->paid_at?->format('Y-m-d H:i') ?? '',
                    $d->created_at?->format('Y-m-d H:i') ?? '',
                    Donation::STATUSES[$d->status] ?? $d->status,
                    round($d->amount_grosze / 100, 2),
                    $d->currency,
                    Donation::TYPES[$d->type ?? 'one_time'] ?? $d->type,
                    Donation::SOURCES[$d->source ?? 'online'] ?? $d->source,
                    $d->campaign?->title ?? 'Cele statutowe',
                    $d->first_name, $d->last_name, $d->company_name ?? '', $d->nip ?? '',
                    $d->email, $d->phone ?? '',
                    $d->is_anonymous ? 'tak' : 'nie',
                    $d->dedication ?? '',
                    $d->p24_order_id ? (string) $d->p24_order_id : '',
                    $d->consent_newsletter ? 'tak' : 'nie',
                    $d->admin_note ?? '',
                ];
            }
        };

        $name = 'wplaty-'.now()->format('Y-m-d');

        if ($request->query('format') === 'xlsx') {
            return response()->download(SimpleXlsx::write($header, $rows(), 'Wpłaty'), $name.'.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend();
        }

        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM — Excel poprawnie czyta polskie znaki
            fputcsv($out, $header, ';');
            foreach ($rows() as $row) {
                fputcsv($out, array_map(fn ($v) => is_string($v) ? self::csvSafe($v) : str_replace('.', ',', (string) $v), $row), ';');
            }
            fclose($out);
        }, $name.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Lista filtrów z adresu (do formularza i linków eksportu). */
    private function filters(Request $request): array
    {
        return [
            'kampania' => $request->query('kampania', ''),
            'status' => $request->query('status', ''),
            'typ' => $request->query('typ', ''),
            'zrodlo' => $request->query('zrodlo', ''),
            'od' => $request->query('od', ''),
            'do' => $request->query('do', ''),
            'q' => trim((string) $request->query('q', '')),
        ];
    }

    private function filtered(Request $request): Builder
    {
        $f = $this->filters($request);

        return Donation::forCurrentSite()
            ->when($f['kampania'] === 'ogolne', fn ($q) => $q->whereNull('campaign_id'))
            ->when(ctype_digit((string) $f['kampania']), fn ($q) => $q->where('campaign_id', (int) $f['kampania']))
            ->when(isset(Donation::STATUSES[$f['status']]), fn ($q) => $q->where('status', $f['status']))
            ->when(isset(Donation::TYPES[$f['typ']]), fn ($q) => $q->where('type', $f['typ']))
            ->when(isset(Donation::SOURCES[$f['zrodlo']]), fn ($q) => $q->where('source', $f['zrodlo']))
            ->when($this->date($f['od']), fn ($q, $d) => $q->where(fn ($w) => $w->where('paid_at', '>=', $d->startOfDay())->orWhere(fn ($x) => $x->whereNull('paid_at')->where('created_at', '>=', $d->startOfDay()))))
            ->when($this->date($f['do']), fn ($q, $d) => $q->where(fn ($w) => $w->where('paid_at', '<=', $d->endOfDay())->orWhere(fn ($x) => $x->whereNull('paid_at')->where('created_at', '<=', $d->endOfDay()))))
            ->when($f['q'] !== '', function ($q) use ($f) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $f['q']).'%';
                $q->where(fn ($w) => $w->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like)->orWhere('company_name', 'like', $like)->orWhere('nip', 'like', $like)
                    ->orWhere('p24_order_id', 'like', $like));
            });
    }

    private function date(string $value): ?Carbon
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function rules(bool $create): array
    {
        return [
            'campaign_id' => ['nullable', 'integer', 'exists:campaigns,id'],
            'amount' => ['required', 'regex:/^\d{1,8}([.,]\d{1,2})?$/', function ($attribute, $value, $fail) {
                if ((float) str_replace(',', '.', $value) <= 0) {
                    $fail('Kwota musi być większa od zera.');
                }
            }],
            'type' => ['required', Rule::in(array_keys(Donation::TYPES))],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'first_name' => ['nullable', 'required_without:company_name', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'company_name' => ['nullable', 'string', 'max:200'],
            'nip' => ['nullable', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (filled($value) && ! PublicCampaignController::validNip($value)) {
                    $fail('Wpisz poprawny NIP (10 cyfr).');
                }
            }],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'dedication' => ['nullable', 'string', 'max:300'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function authorizeSite(Donation $donation): void
    {
        abort_unless($donation->site_id === \App\Models\SiteSetting::current()->id, 404);
    }

    /** Neutralizuje formuły w CSV (=, +, -, @ na początku komórki). */
    private static function csvSafe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
