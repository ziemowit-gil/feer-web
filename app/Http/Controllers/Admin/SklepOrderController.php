<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SklepOrder;
use App\Services\SklepOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Panel admin: podgląd zamówień sklepu (Przelewy24) — dane finansowe/PII,
 * dostępne wyłącznie dla administratorów (patrz middleware trasy: 'admin').
 */
class SklepOrderController extends Controller
{
    public function __construct(private readonly SklepOrderService $orders) {}

    public function index(Request $request)
    {
        $status = $request->string('status')->toString();
        $q = $request->string('q')->toString();

        $orders = SklepOrder::with('material')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($q, fn ($query) => $query->where('buyer_email', 'like', "%{$q}%"))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.sklep.orders.index', compact('orders', 'status', 'q'));
    }

    public function show(SklepOrder $order)
    {
        $order->load('material', 'user');

        return view('admin.sklep.orders.show', compact('order'));
    }

    /** Ręcznie wysyła ponownie e-mail z dostępem (np. klient zgubił wiadomość). */
    public function resend(SklepOrder $order): RedirectResponse
    {
        if (! $order->isPaid()) {
            return back()->with('error', 'Można wysłać ponownie tylko opłacone zamówienia.');
        }

        try {
            $this->orders->resend($order);
        } catch (Throwable $e) {
            Log::warning('[Sklep] Ponowna wysyłka dostępu nie powiodła się: '.$e->getMessage());

            return back()->with('error', 'Wysyłka e-maila nie powiodła się.');
        }

        return back()->with('status', 'E-mail z dostępem został wysłany ponownie.');
    }
}
