<?php

namespace App\Http\Controllers;

use App\Http\Requests\SklepCheckoutRequest;
use App\Models\EducationalMaterial;
use App\Models\SklepDiscountCode;
use App\Models\SklepOrder;
use App\Services\SklepOrderService;
use App\Support\SklepCart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/**
 * Publiczny sklep — koszyk z wieloma materiałami edukacyjnymi, płatność
 * Przelewy24. Zakup jako gość: bez logowania, dostęp do materiałów wysyłany
 * mailem po zaksięgowaniu wpłaty (patrz Przelewy24WebhookController + SklepOrderService).
 */
class SklepController extends Controller
{
    public function __construct(
        private readonly SklepOrderService $orders,
        private readonly SklepCart $cart,
    ) {}

    public function index(): View
    {
        $materials = EducationalMaterial::where('is_published', true)
            ->where('is_archival', false)
            ->whereNotNull('price_grosze')
            ->orderBy('order')
            ->orderBy('title')
            ->get();

        return view('sklep.index', [
            'materials' => $materials,
            'cartIds' => $this->cart->ids(),
        ]);
    }

    public function show(EducationalMaterial $material): View
    {
        abort_unless($material->isPurchasable(), 404);

        return view('sklep.show', [
            'material' => $material,
            'inCart' => in_array($material->id, $this->cart->ids(), true),
        ]);
    }

    public function addToCart(EducationalMaterial $material): RedirectResponse
    {
        abort_unless($material->isPurchasable(), 404);

        $this->cart->add($material->id);

        return back()->with('status', "Dodano „{$material->title}” do koszyka.");
    }

    public function removeFromCart(EducationalMaterial $material): RedirectResponse
    {
        $this->cart->remove($material->id);

        return redirect()->route('sklep.cart')->with('status', 'Usunięto z koszyka.');
    }

    public function cart(): View
    {
        $items = $this->cart->items();
        $subtotal = (int) $items->sum('price_grosze');
        $discountCode = $this->cart->discountCode();
        $discountAmount = $discountCode?->calculateDiscount($subtotal) ?? 0;

        return view('sklep.cart', [
            'items' => $items,
            'subtotal' => $subtotal,
            'discountCode' => $discountCode,
            'discountAmount' => $discountAmount,
            'total' => max(0, $subtotal - $discountAmount),
        ]);
    }

    public function applyDiscount(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:50']]);

        $code = SklepDiscountCode::where('code', mb_strtoupper($request->string('code')->toString()))->first();

        if (! $code || ! $code->isValidNow()) {
            return redirect()->route('sklep.cart')->with('error', 'Kod rabatowy jest nieprawidłowy albo wygasł.');
        }

        $this->cart->setDiscountCode($code->code);

        return redirect()->route('sklep.cart')->with('status', 'Kod rabatowy zastosowany.');
    }

    public function checkout(SklepCheckoutRequest $request): RedirectResponse
    {
        $items = $this->cart->items();

        if ($items->isEmpty()) {
            return redirect()->route('sklep.cart')->with('error', 'Koszyk jest pusty.');
        }

        $discountCode = $this->cart->discountCode();

        try {
            $order = $this->orders->initiateFromCart(
                $items,
                $discountCode,
                $request->string('buyer_email')->toString(),
                $request->filled('buyer_name') ? $request->string('buyer_name')->toString() : null,
                auth()->id(),
            );
        } catch (RuntimeException $e) {
            return redirect()->route('sklep.cart')->with('error', $e->getMessage());
        }

        $this->cart->clear();

        return redirect()->away($this->orders->paymentUrl($order));
    }

    public function confirmation(SklepOrder $order): View
    {
        return view('sklep.confirmation', compact('order'));
    }

    public function download(string $token): View
    {
        $order = SklepOrder::with('items.material')->where('access_token', $token)->where('status', 'paid')->firstOrFail();

        return view('sklep.download', compact('order'));
    }
}
