<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Szkolenie lub cykl zajęć z oferty witryny (moduł „Szkolenia i zajęcia”). Konkretne terminy to {@see CourseEdition}.
 */
class Course extends Model
{
    use BelongsToSite;

    public const FORMATS = [
        'stationary' => 'Stacjonarnie',
        'online' => 'Online',
        'hybrid' => 'Hybrydowo',
    ];

    public const CATEGORIES = [
        'tyflo' => 'Tyfloinformatyka',
        'wcag' => 'Dostępność WCAG',
        'programming' => 'Programowanie',
        'm365' => 'Microsoft 365',
        'other' => 'Inne',
    ];

    public const STATUSES = [
        'draft' => 'Szkic',
        'active' => 'Aktywne',
        'finished' => 'Zakończone',
    ];

    protected $fillable = [
        'title', 'slug', 'lead', 'description', 'category', 'format', 'price', 'status', 'is_featured', 'order',
        'facilitator_id', 'duration', 'level', 'certificate', 'price_note', 'funding', 'audience', 'outcomes', 'program', 'prerequisites', 'terms', 'faq',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_featured' => 'boolean',
        'audience' => 'array',
        'outcomes' => 'array',
        'program' => 'array',
        'faq' => 'array',
    ];

    /** Trener prowadzący szkolenie (baza prowadzących: zdjęcie, rola, biogram, linki). */
    public function facilitator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Facilitator::class);
    }

    /** Lista tekstów (dla kogo, efekty) bez pustych wierszy. @return list<string> */
    public function listOf(string $field): array
    {
        return collect($this->{$field} ?? [])->map(fn ($r) => is_array($r) ? trim((string) ($r['text'] ?? '')) : trim((string) $r))->filter()->values()->all();
    }

    /** Moduły programu: tytuł, godziny, opis (bez wierszy bez tytułu). @return list<array{title: string, hours: string, text: string}> */
    public function programModules(): array
    {
        return collect($this->program ?? [])->map(fn ($r) => ['title' => trim((string) ($r['title'] ?? '')), 'hours' => trim((string) ($r['hours'] ?? '')), 'text' => trim((string) ($r['text'] ?? ''))])
            ->filter(fn ($r) => $r['title'] !== '')->values()->all();
    }

    /** Pytania i odpowiedzi (bez niepełnych wierszy). @return list<array{question: string, answer: string}> */
    public function faqItems(): array
    {
        return collect($this->faq ?? [])->map(fn ($r) => ['question' => trim((string) ($r['question'] ?? '')), 'answer' => trim((string) ($r['answer'] ?? ''))])
            ->filter(fn ($r) => $r['question'] !== '' && $r['answer'] !== '')->values()->all();
    }

    public function editions(): HasMany
    {
        return $this->hasMany(CourseEdition::class)->orderBy('starts_on');
    }

    /** Widoczne publicznie: aktywne szkolenia (szkice i zakończone są tylko w panelu). */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)->forCurrentSite()->first();
    }

    public function formatLabel(): string
    {
        return self::FORMATS[$this->format] ?? (string) $this->format;
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? (string) $this->category;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    /** Cena do wyświetlenia: „Bezpłatnie” przy braku ceny lub zerze. */
    public function priceLabel(): string
    {
        return (float) $this->price > 0
            ? number_format((float) $this->price, 2, ',', ' ').' zł'
            : 'Bezpłatnie';
    }

    /** Terminy, na które można jeszcze patrzeć na stronie: nie zakończone i z datą startu od dziś. */
    public function upcomingEditions()
    {
        return $this->editions->filter(fn (CourseEdition $e) => $e->isUpcoming());
    }

    /** Najbliższy termin z otwartą rekrutacją (do kafelka na liście). */
    public function nextEdition(): ?CourseEdition
    {
        return $this->upcomingEditions()->first();
    }
}
