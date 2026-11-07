<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Moduł „Książka telefoniczna”: osoba z działem, rolą, numerem komórkowym, e-mailem, sprawą (w jakiej sprawie się
 * kontaktować) i godzinami kontaktu. Lista na stronie /nasze-numery-telefonow i w skrócie [ksiazka-telefoniczna].
 */
class PhoneBookEntry extends Model
{
    use BelongsToSite;

    protected $fillable = ['site_id', 'group', 'name', 'role', 'phone', 'email', 'topic', 'hours', 'is_active', 'order'];

    protected $casts = ['is_active' => 'boolean', 'order' => 'integer'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Osoby aktywnej witryny w postaci tablic dla widoku partials/phone-book/list.
     *
     * @return array<int, array<string, string>>
     */
    public static function listForCurrentSite(): array
    {
        return static::forCurrentSite()->active()->orderBy('order')->orderBy('id')->get()
            ->map(fn (self $e) => [
                'group' => (string) $e->group, 'name' => (string) $e->name, 'role' => (string) $e->role, 'phone' => (string) $e->phone,
                'email' => (string) $e->email, 'topic' => (string) $e->topic, 'hours' => (string) $e->hours,
            ])->all();
    }
}
