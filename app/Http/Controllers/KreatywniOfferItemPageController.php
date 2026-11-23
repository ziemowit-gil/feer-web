<?php

namespace App\Http\Controllers;

use App\Models\KreatywniOfferItem;
use App\Models\KreatywniWorkshop;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\KreatywniTexts;
use Illuminate\View\View;

/**
 * Podstrona warsztatu z oferty (moduł „Mikrooferta – Kreatywni”, szablon „kreatywni”): /warsztat/{slug} — zdjęcie, nazwa,
 * grupa, cena, opis, zamówienie i inne warsztaty z tej grupy. 404 poza szablonem „kreatywni”, bez tabeli kart,
 * dla ukrytego warsztatu, ukrytej oferty i oferty innej witryny.
 */
class KreatywniOfferItemPageController extends Controller
{
    public function show(string $slug): View
    {
        $site = SiteSetting::current();
        abort_unless($site->site_template === 'kreatywni' && KreatywniOfferItem::supported(), 404);

        $item = KreatywniOfferItem::query()->where('slug', $slug)->where('is_active', true)
            ->whereHas('offer', fn ($q) => $q->where('site_id', $site->getKey())->where('is_active', true))
            ->with('media', 'offer')->firstOrFail();
        $offer = $item->offer;

        // Strona „Oferta” (adres oferta / oferta-2…) — powrót i okruszki.
        $offerPage = Page::forCurrentSite()->where('is_published', true)->where('slug', 'like', 'oferta%')->orderBy('id')->get()
            ->first(fn (Page $p) => (bool) preg_match('/^oferta(-\d+)?$/', (string) $p->slug) && $p->isLiveNow());
        $contactUrl = $site->isModuleEnabled('contact') && \Illuminate\Support\Facades\Route::has('contact.show') ? site_route('contact.show') : null;
        $texts = KreatywniTexts::for($site);
        $orderUrl = $texts->ordersOnline() ? (KreatywniTexts::href((string) $offer->button_url) ?? $contactUrl) : $contactUrl;
        $calendarUrl = null;
        if ($site->isModuleEnabled('workshops') && KreatywniWorkshop::supported()) {
            $calendarUrl = Page::forCurrentSite()->where('type', 'kreatywni_workshops')->where('is_published', true)->orderBy('id')->get()
                ->first(fn (Page $p) => $p->isLiveNow())?->publicUrl();
        }
        $related = $offer->cards()->where('is_active', true)->whereKeyNot($item->getKey())
            ->when(filled($item->group), fn ($q) => $q->where('group', $item->group))->with('media')->limit(3)->get();

        return view('templates.kreatywni.offer-item', [
            'item' => $item,
            'offer' => $offer,
            'offerPage' => $offerPage,
            'orderUrl' => $orderUrl,
            'orderLabel' => $texts->ordersOnline() ? (trim((string) $offer->button_label) ?: 'Zamów warsztaty') : $texts->get('orders_off_button'),
            'calendarUrl' => $calendarUrl,
            'related' => $related,
            'price' => trim((string) $item->price) !== '' ? trim((string) $item->price) : trim(trim((string) $offer->price).' '.trim((string) $offer->price_unit)),
        ]);
    }
}
