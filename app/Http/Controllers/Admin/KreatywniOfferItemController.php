<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KreatywniOffer;
use App\Models\KreatywniOfferItem;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Warsztaty (karty) jednej oferty z modułu „Mikrooferta – Kreatywni”: nazwa, grupa, krótki opis (karta), dłuższy opis
 * (podstrona /warsztat/{slug}), cena, grafika z dysku albo z biblioteki Multimediów z opisem alternatywnym, widoczność
 * i kolejność (Wyżej/Niżej). Lista jest w edycji oferty (admin/kreatywni-offers/form, sekcja „Warsztaty w tej ofercie”).
 * Po każdej zmianie odświeżamy tekstowy zapas oferty (KreatywniOffer::syncItemsText()).
 */
class KreatywniOfferItemController extends Controller
{
    use \App\Http\Controllers\Concerns\AttachesLibraryMedia;

    public function create(Request $request, KreatywniOffer $offer): View
    {
        $this->guard($offer);
        $item = new KreatywniOfferItem(['is_active' => true, 'group' => $request->query('grupa')]);

        return view('admin.kreatywni-offers.item-form', ['offer' => $offer, 'item' => $item, 'groups' => $this->groups($offer)]);
    }

    public function store(Request $request, KreatywniOffer $offer): RedirectResponse
    {
        $this->guard($offer);
        $item = $offer->cards()->create($this->validated($request) + ['order' => (int) $offer->cards()->max('order') + 1]);
        $this->saveImage($request, $item);
        $offer->syncItemsText();

        return $this->back($offer, 'Dodano warsztat „'.$item->title.'”.');
    }

    public function edit(KreatywniOffer $offer, KreatywniOfferItem $item): View
    {
        $this->guard($offer, $item);

        return view('admin.kreatywni-offers.item-form', ['offer' => $offer, 'item' => $item, 'groups' => $this->groups($offer)]);
    }

    public function update(Request $request, KreatywniOffer $offer, KreatywniOfferItem $item): RedirectResponse
    {
        $this->guard($offer, $item);
        $item->update($this->validated($request));
        $this->saveImage($request, $item);
        $offer->syncItemsText();

        return $this->back($offer, 'Zapisano warsztat „'.$item->title.'”.');
    }

    public function destroy(KreatywniOffer $offer, KreatywniOfferItem $item): RedirectResponse
    {
        $this->guard($offer, $item);
        $title = $item->title;
        $item->delete();
        $offer->syncItemsText();

        return $this->back($offer, 'Usunięto warsztat „'.$title.'”.');
    }

    /** Wyżej / Niżej: zamiana miejscami z sąsiednim warsztatem tej oferty (kolejność porządkowana 1, 2, 3…). */
    public function move(Request $request, KreatywniOffer $offer, KreatywniOfferItem $item): RedirectResponse
    {
        $this->guard($offer, $item);
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        DB::transaction(function () use ($offer, $item, $direction) {
            $ids = $offer->cards()->pluck('id')->all();
            $at = array_search($item->getKey(), $ids, true);
            $to = $direction === 'up' ? $at - 1 : $at + 1;
            if ($at === false || ! isset($ids[$to])) {
                return;
            }
            [$ids[$at], $ids[$to]] = [$ids[$to], $ids[$at]];
            foreach ($ids as $i => $id) {
                KreatywniOfferItem::whereKey($id)->update(['order' => $i + 1]);
            }
        });
        $offer->syncItemsText();

        return $this->back($offer, 'Przesunięto warsztat „'.$item->title.'” '.($direction === 'up' ? 'wyżej' : 'niżej').'.');
    }

    private function back(KreatywniOffer $offer, string $status): RedirectResponse
    {
        return redirect()->to(route('admin.kreatywni-oferty.edit', $offer).'#warsztaty')->with('status', $status);
    }

    /** @return list<string> grupy użyte w tej ofercie (podpowiedzi w polu „Grupa”) */
    private function groups(KreatywniOffer $offer): array
    {
        return $offer->cards()->whereNotNull('group')->pluck('group')->map(fn ($g) => trim((string) $g))->filter()->unique()->values()->all();
    }

    private function guard(KreatywniOffer $offer, ?KreatywniOfferItem $item = null): void
    {
        $site = SiteSetting::current();
        abort_unless($site->site_template === 'kreatywni' && KreatywniOfferItem::supported(), 404);
        abort_unless((int) $offer->site_id === (int) $site->getKey(), 404);
        abort_if($item && (int) $item->kreatywni_offer_id !== (int) $offer->getKey(), 404);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'group' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:600'],
            'details' => ['nullable', 'string', 'max:8000'],
            'price' => ['nullable', 'string', 'max:100'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'image_library_id' => $this->libraryMediaRule(),
            'image_alt' => ['nullable', 'string', 'max:255'],
            'remove_image' => ['nullable', 'boolean'],
        ], [], [
            'title' => 'nazwa warsztatu', 'group' => 'grupa', 'description' => 'krótki opis', 'details' => 'opis na podstronie',
            'price' => 'cena', 'image' => 'grafika', 'image_alt' => 'opis grafiki',
        ]);

        $text = fn (?string $v) => filled($v) ? trim(str_replace("\r\n", "\n", $v)) : null;

        return [
            'title' => trim($data['title']),
            'group' => $text($data['group'] ?? null),
            'description' => $text($data['description'] ?? null),
            'details' => $text($data['details'] ?? null),
            'price' => $text($data['price'] ?? null),
            'image_alt' => $text($data['image_alt'] ?? null),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function saveImage(Request $request, KreatywniOfferItem $item): void
    {
        if ($request->hasFile('image')) {
            $item->addMediaFromRequest('image')->toMediaCollection('image');
        } elseif ($this->attachFromLibrary($request, $item, 'image', 'image')) {
            // obraz z biblioteki skopiowany do warsztatu
        } elseif ($request->boolean('remove_image')) {
            $item->clearMediaCollection('image');
        }
    }
}
