<?php

declare(strict_types=1);

namespace Modules\Newsletter\Services;

use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Builder;

/**
 * Tłumaczy reguły segmentu (JSON) na zapytanie Eloquent.
 *
 * {"match":"all|any","rules":[{"field":"status","op":"eq","value":"confirmed"}, …]}
 *
 * Pola: status, topics, channels, tags, site_id, source, created_at, confirmed_at,
 * last_open_at, last_click_at, last_sent_at, engagement_score, list_id, name, email_domain.
 * Operatory: eq, neq, in, not_in, contains, not_contains, within_days, older_than_days,
 * gte, lte, is_null, not_null, ends_with.
 */
class SegmentResolver
{
    public const FIELDS = [
        'status' => 'Status', 'topics' => 'Tematy', 'channels' => 'Kanały', 'tags' => 'Tagi', 'site_id' => 'Ośrodek',
        'source' => 'Źródło zapisu', 'created_at' => 'Data zapisu', 'confirmed_at' => 'Data potwierdzenia',
        'last_open_at' => 'Ostatnie otwarcie', 'last_click_at' => 'Ostatnie kliknięcie', 'last_sent_at' => 'Ostatnia wysyłka',
        'engagement_score' => 'Zaangażowanie (0–100)', 'list_id' => 'Lista', 'email_domain' => 'Domena e-mail',
    ];

    public const OPERATORS = [
        'eq' => 'równa się', 'neq' => 'różne od', 'in' => 'jedno z', 'not_in' => 'żadne z', 'contains' => 'zawiera',
        'not_contains' => 'nie zawiera', 'within_days' => 'w ciągu ostatnich N dni', 'older_than_days' => 'dawniej niż N dni temu',
        'gte' => 'większe lub równe', 'lte' => 'mniejsze lub równe', 'is_null' => 'puste', 'not_null' => 'niepuste',
        'ends_with' => 'kończy się na',
    ];

    public function query(array $rules): Builder
    {
        $q = Subscriber::query();
        $match = ($rules['match'] ?? 'all') === 'any' ? 'or' : 'and';
        $list = $rules['rules'] ?? [];

        if ($list === []) {
            return $q;
        }

        $q->where(function (Builder $w) use ($list, $match) {
            foreach ($list as $rule) {
                $this->apply($w, $rule, $match);
            }
        });

        return $q;
    }

    private function apply(Builder $q, array $rule, string $boolean): void
    {
        $field = (string) ($rule['field'] ?? '');
        $op    = (string) ($rule['op'] ?? 'eq');
        $value = $rule['value'] ?? null;

        if (! array_key_exists($field, self::FIELDS)) {
            return;
        }

        $jsonFields = ['topics', 'channels', 'tags'];
        $dateFields = ['created_at', 'confirmed_at', 'last_open_at', 'last_click_at', 'last_sent_at'];

        $closure = function (Builder $w) use ($field, $op, $value, $jsonFields, $dateFields) {
            if ($field === 'list_id') {
                $ids = (array) $value;
                $sub = fn ($s) => $s->whereIn('list_id', $ids);
                in_array($op, ['neq', 'not_in', 'not_contains'], true)
                    ? $w->whereDoesntHave('lists', fn ($s) => $s->whereIn('newsletter_lists.id', $ids))
                    : $w->whereHas('lists', fn ($s) => $s->whereIn('newsletter_lists.id', $ids));

                return;
            }

            if ($field === 'email_domain') {
                $domains = array_map(fn ($d) => '%@' . ltrim(strtolower((string) $d), '@'), (array) $value);
                $w->where(function ($x) use ($domains, $op) {
                    foreach ($domains as $d) {
                        $op === 'not_in' || $op === 'neq' ? $x->where('email', 'not like', $d) : $x->orWhere('email', 'like', $d);
                    }
                });

                return;
            }

            if (in_array($field, $jsonFields, true)) {
                $vals = (array) $value;
                if ($op === 'not_contains') {
                    $w->where(function ($x) use ($field, $vals) {
                        foreach ($vals as $v) {
                            $x->whereJsonDoesntContain($field, $v);
                        }
                    });
                } else {
                    $w->where(function ($x) use ($field, $vals) {
                        foreach ($vals as $v) {
                            $x->orWhereJsonContains($field, $v);
                        }
                    });
                }

                return;
            }

            if (in_array($field, $dateFields, true)) {
                match ($op) {
                    'within_days'     => $w->where($field, '>=', now()->subDays((int) $value)),
                    'older_than_days' => $w->where($field, '<', now()->subDays((int) $value)),
                    'is_null'         => $w->whereNull($field),
                    'not_null'        => $w->whereNotNull($field),
                    'gte'             => $w->where($field, '>=', $value),
                    'lte'             => $w->where($field, '<=', $value),
                    default           => null,
                };

                return;
            }

            match ($op) {
                'eq'       => $w->where($field, $value),
                'neq'      => $w->where($field, '!=', $value),
                'in'       => $w->whereIn($field, (array) $value),
                'not_in'   => $w->whereNotIn($field, (array) $value),
                'gte'      => $w->where($field, '>=', $value),
                'lte'      => $w->where($field, '<=', $value),
                'is_null'  => $w->whereNull($field),
                'not_null' => $w->whereNotNull($field),
                default    => $w->where($field, $value),
            };
        };

        $boolean === 'or' ? $q->orWhere($closure) : $q->where($closure);
    }
}
