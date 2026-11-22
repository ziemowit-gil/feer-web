<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KreatywniOffer;
use App\Models\SiteSetting;
use App\Support\KreatywniTexts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Mikro moduł „Mikrooferta – Kreatywni” (szablon „kreatywni”): oferty z bloku sprzedażowego (karta cenowa
 * i warsztaty w grupach) — dodawanie, edycja, widoczność, kolejność i jednorazowy import z tekstów szablonu.
 * Tylko dla witryny w szablonie „kreatywni”.
 */
class KreatywniOfferController extends Controller
{
    public function index(): View
    {
        $texts = KreatywniTexts::for($this->site());
        $offers = KreatywniOffer::forCurrentSite()->orderBy('order')->orderBy('id')->get();
        $importable = $offers->isEmpty() && $this->hasTextOffer($texts);

        return view('admin.kreatywni-offers.index', compact('offers', 'importable'));
    }

    public function create(): View
    {
        $this->site();

        return view('admin.kreatywni-offers.form', ['offer' => new KreatywniOffer([
            'is_active' => true, 'items_style' => array_key_first(KreatywniTexts::OFFER_STYLES), 'button_label' => 'Zamów warsztaty',
        ])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->site();
        $offer = KreatywniOffer::create($this->validated($request) + ['order' => (int) KreatywniOffer::forCurrentSite()->max('order') + 1]);

        return redirect()->route('admin.kreatywni-oferty.index')->with('status', 'Dodano ofertę „'.$offer->name.'”.');
    }

    public function edit(KreatywniOffer $offer): View
    {
        $this->guard($offer);

        return view('admin.kreatywni-offers.form', compact('offer'));
    }

    public function update(Request $request, KreatywniOffer $offer): RedirectResponse
    {
        $this->guard($offer);
        $offer->update($this->validated($request));

        return redirect()->route('admin.kreatywni-oferty.index')->with('status', 'Zapisano ofertę „'.$offer->name.'”.');
    }

    public function destroy(KreatywniOffer $offer): RedirectResponse
    {
        $this->guard($offer);
        $name = $offer->name;
        $offer->delete();

        return redirect()->route('admin.kreatywni-oferty.index')->with('status', 'Usunięto ofertę „'.$name.'”.');
    }

    /** Wyżej / Niżej: zamiana miejscami z sąsiednią ofertą (kolejność najpierw porządkowana 1, 2, 3…). */
    public function move(Request $request, KreatywniOffer $offer): RedirectResponse
    {
        $this->guard($offer);
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        DB::transaction(function () use ($offer, $direction) {
            $ids = KreatywniOffer::forCurrentSite()->orderBy('order')->orderBy('id')->pluck('id')->all();
            $at = array_search($offer->getKey(), $ids, true);
            $to = $direction === 'up' ? $at - 1 : $at + 1;
            if ($at === false || ! isset($ids[$to])) {
                return;
            }
            [$ids[$at], $ids[$to]] = [$ids[$to], $ids[$at]];
            foreach ($ids as $i => $id) {
                KreatywniOffer::whereKey($id)->update(['order' => $i + 1]);
            }
        });

        return redirect()->route('admin.kreatywni-oferty.index')->with('status', 'Przesunięto ofertę „'.$offer->name.'” '.($direction === 'up' ? 'wyżej' : 'niżej').'.');
    }

    /** Przenosi ofertę z tekstów szablonu (Ustawienia → Szablon → teksty oferty) do modułu; tylko przy pustym module. */
    public function import(): RedirectResponse
    {
        $texts = KreatywniTexts::for($this->site());
        if (KreatywniOffer::forCurrentSite()->exists()) {
            return redirect()->route('admin.kreatywni-oferty.index')->with('status', 'Moduł ma już oferty — import pominięty.');
        }
        if (! $this->hasTextOffer($texts)) {
            return redirect()->route('admin.kreatywni-oferty.index')->with('status', 'W tekstach szablonu nie ma oferty do przeniesienia.');
        }

        $offer = KreatywniOffer::create(self::fromTexts($texts) + ['order' => 1, 'is_active' => true]);

        return redirect()->route('admin.kreatywni-oferty.index')->with('status', 'Przeniesiono ofertę „'.$offer->name.'” z tekstów szablonu. Teraz edytujesz ją tutaj.');
    }

    /**
     * Pola oferty z tekstów szablonu (import w panelu i seeder).
     *
     * @return array<string, mixed>
     */
    public static function fromTexts(KreatywniTexts $texts): array
    {
        $own = trim($texts->ownValue('offer_button_url'));

        return [
            'name' => $texts->get('offer_card_title') ?: 'Oferta',
            'price' => $texts->get('offer_price') ?: null,
            'price_unit' => $texts->get('offer_price_unit') ?: null,
            'price_note' => $texts->get('offer_price_note') ?: null,
            'features' => $texts->get('offer_features') ?: null,
            'items' => $texts->get('offer_items') ?: null,
            'items_style' => $texts->offerStyle(),
            'button_label' => $texts->get('offer_button') ?: null,
            'button_url' => $own !== '' ? $own : null,
        ];
    }

    private function hasTextOffer(KreatywniTexts $texts): bool
    {
        return $texts->rows('offer_items') !== [] || $texts->get('offer_price') !== '';
    }

    private function site(): SiteSetting
    {
        $site = SiteSetting::current();
        abort_unless($site->site_template === 'kreatywni' && KreatywniOffer::supported(), 404);

        return $site;
    }

    private function guard(KreatywniOffer $offer): void
    {
        abort_unless((int) $offer->site_id === (int) $this->site()->getKey(), 404);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['nullable', 'string', 'max:100'],
            'price_unit' => ['nullable', 'string', 'max:100'],
            'price_note' => ['nullable', 'string', 'max:255'],
            'features' => ['nullable', 'string', 'max:4000'],
            'items' => ['nullable', 'string', 'max:8000'],
            'items_style' => ['required', Rule::in(array_keys(KreatywniTexts::OFFER_STYLES))],
            'button_label' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'string', 'max:500', function (string $attribute, mixed $value, \Closure $fail) {
                if (filled($value) && KreatywniTexts::href((string) $value) === null) {
                    $fail('Adres przycisku musi zaczynać się od https://, http://, „/” (strona w witrynie), „#”, mailto: albo tel:.');
                }
            }],
        ], [], [
            'name' => 'nazwa oferty', 'price' => 'cena', 'price_unit' => 'jednostka', 'price_note' => 'dopisek przy cenie',
            'features' => 'korzyści', 'items' => 'warsztaty', 'items_style' => 'wygląd warsztatów',
            'button_label' => 'napis na przycisku', 'button_url' => 'adres przycisku',
        ]);

        $text = fn (?string $v) => filled($v) ? trim(str_replace("\r\n", "\n", $v)) : null;

        return [
            'name' => trim($data['name']),
            'price' => $text($data['price'] ?? null),
            'price_unit' => $text($data['price_unit'] ?? null),
            'price_note' => $text($data['price_note'] ?? null),
            'features' => $text($data['features'] ?? null),
            'items' => $text($data['items'] ?? null),
            'items_style' => $data['items_style'],
            'button_label' => $text($data['button_label'] ?? null),
            'button_url' => $text($data['button_url'] ?? null),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
