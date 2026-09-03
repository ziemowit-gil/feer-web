<?php

namespace App\Http\Controllers;

use App\Http\Requests\SklepCheckoutRequest;
use App\Models\EducationalMaterial;
use App\Models\SklepOrder;
use App\Services\SklepOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Publiczny sklep — pojedynczy zakup materiałów edukacyjnych przez Przelewy24.
 * Zakup jako gość: bez logowania, dostęp do materiału wysyłany mailem po
 * zaksięgowaniu wpłaty (patrz Przelewy24WebhookController + SklepOrderService).
 */
class SklepController extends Controller
{
    public function __construct(private readonly SklepOrderService $orders) {}

    public function index(): View
    {
        $materials = EducationalMaterial::where('is_published', true)
            ->where('is_archival', false)
            ->whereNotNull('price_grosze')
            ->orderBy('order')
            ->orderBy('title')
            ->get();

        return view('sklep.index', compact('materials'));
    }

    public function show(EducationalMaterial $material): View
    {
        abort_unless($material->isPurchasable(), 404);

        return view('sklep.show', compact('material'));
    }

    public function checkout(SklepCheckoutRequest $request, EducationalMaterial $material): RedirectResponse
    {
        abort_unless($material->isPurchasable(), 404);

        try {
            $order = $this->orders->initiate(
                $material,
                $request->string('buyer_email')->toString(),
                $request->filled('buyer_name') ? $request->string('buyer_name')->toString() : null,
                auth()->id(),
            );
        } catch (\RuntimeException $e) {
            return redirect()->route('sklep.show', $material)->with('error', $e->getMessage());
        }

        return redirect()->away($this->orders->paymentUrl($order));
    }

    public function confirmation(SklepOrder $order): View
    {
        return view('sklep.confirmation', compact('order'));
    }

    public function download(string $token): View|RedirectResponse
    {
        $order = SklepOrder::where('access_token', $token)->where('status', 'paid')->firstOrFail();
        $material = $order->material;

        if ($material->isVideo()) {
            return view('sklep.download', compact('order', 'material'));
        }

        return redirect()->away($material->fileUrl);
    }
}
