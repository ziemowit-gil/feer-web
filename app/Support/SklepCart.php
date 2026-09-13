<?php

namespace App\Support;

use App\Models\EducationalMaterial;
use App\Models\SklepDiscountCode;
use Illuminate\Database\Eloquent\Collection;

/**
 * Koszyk gościa trzymany w sesji — bez konta, bez nowej tabeli. Zestaw ID
 * materiałów (nie wielozbiór — materiał cyfrowy kupuje się raz), znika przy
 * wygaśnięciu sesji, co jest tu akceptowalne (nie ma potrzeby, by przeżywał
 * dłużej niż jedna wizyta).
 */
class SklepCart
{
    private const SESSION_KEY = 'sklep_cart';

    private const DISCOUNT_SESSION_KEY = 'sklep_discount_code';

    /** @return int[] */
    public function ids(): array
    {
        return array_values(array_unique(session(self::SESSION_KEY, [])));
    }

    /**
     * Materiały w koszyku — pomija te, które przestały być kupowalne
     * (zniknęły albo zmieniła się ich dostępność) od czasu dodania.
     */
    public function items(): Collection
    {
        $ids = $this->ids();

        if (empty($ids)) {
            return new Collection();
        }

        return EducationalMaterial::whereIn('id', $ids)->get()->filter->isPurchasable()->values();
    }

    public function add(int $materialId): void
    {
        $ids = $this->ids();
        $ids[] = $materialId;
        session([self::SESSION_KEY => array_values(array_unique($ids))]);
    }

    public function remove(int $materialId): void
    {
        session([self::SESSION_KEY => array_values(array_diff($this->ids(), [$materialId]))]);
    }

    public function isEmpty(): bool
    {
        return $this->items()->isEmpty();
    }

    public function subtotalGrosze(): int
    {
        return (int) $this->items()->sum('price_grosze');
    }

    public function discountCode(): ?SklepDiscountCode
    {
        $code = session(self::DISCOUNT_SESSION_KEY);

        if (! $code) {
            return null;
        }

        $discount = SklepDiscountCode::where('code', $code)->first();

        return $discount?->isValidNow() ? $discount : null;
    }

    public function setDiscountCode(string $code): void
    {
        session([self::DISCOUNT_SESSION_KEY => mb_strtoupper($code)]);
    }

    public function clear(): void
    {
        session()->forget([self::SESSION_KEY, self::DISCOUNT_SESSION_KEY]);
    }
}
