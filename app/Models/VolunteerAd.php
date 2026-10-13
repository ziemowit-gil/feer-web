<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Route;

class VolunteerAd extends Model
{
    use \App\Models\Concerns\LogsActivity;

    /** Tryb działań wolontariatu (pytanie 3: gdzie/jak). */
    public const MODES = [
        'stacjonarnie' => 'Stacjonarnie',
        'zdalnie' => 'Zdalnie',
        'hybrydowo' => 'Hybrydowo',
    ];

    protected $fillable = [
        'title', 'slug', 'lead',
        'q_beneficiaries', 'q_tasks', 'q_mode', 'q_location', 'q_schedule',
        'q_time_commitment', 'q_benefits', 'q_how_to_apply',
        'application_url', 'application_cta_label', 'szo_form_slug', 'form_definition_id', 'meeting_url', 'meeting_cta_label', 'contact_name', 'contact_email',
        'audience', 'is_published', 'closes_at', 'order', 'archived_at',
    ];

    protected $casts = [
        'q_tasks' => 'array',
        'q_benefits' => 'array',
        'is_published' => 'boolean',
        'closes_at' => 'date',
        'archived_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Opublikowane i nieprzeterminowane — widoczne dla odwiedzających. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(function (Builder $q) {
                $q->whereNull('closes_at')->orWhereDate('closes_at', '>=', now());
            })
            ->orderBy('order')
            ->latest('id');
    }

    /** Czytelna etykieta trybu. */
    public function modeLabel(): string
    {
        return self::MODES[$this->q_mode] ?? $this->q_mode;
    }

    /** Lustrzany formularz CMS formularza SZO wybranego w ogłoszeniu. */
    public function formDefinition(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class);
    }

    /** Formularz zgłoszeniowy z SZO do osadzenia na stronie ogłoszenia (aktywny) albo null. */
    public function applicationForm(): ?FormDefinition
    {
        if (blank($this->szo_form_slug) || ! Route::has('formularz.store')) {
            return null;
        }

        $form = $this->formDefinition;

        return $form?->is_active ? $form : null;
    }

    /**
     * Dokąd prowadzi przycisk „Zgłoś się": formularz z SZO na tej stronie,
     * zewnętrzny formularz, a gdy go nie podano — e-mail kontaktowy oferty.
     */
    public function applyHref(): ?string
    {
        if ($this->applicationForm()) {
            return '#zgloszenie';
        }

        if (filled($this->application_url)) {
            return $this->application_url;
        }

        return filled($this->contact_email) ? 'mailto:'.$this->contact_email : null;
    }

    /** Czy termin zgłoszeń już minął (do oznaczenia w panelu). */
    public function isClosed(): bool
    {
        return $this->closes_at !== null && $this->closes_at->isPast();
    }
}
