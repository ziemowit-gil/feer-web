<?php

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Model;

/**
 * Dedykowany, edytowalny w panelu formularz zapisu (treści, pola, tematy,
 * klauzula zgody, podpięcie do list i do formularza SZO).
 */
class NewsletterForm extends Model
{
    protected $table = 'newsletter_forms';

    protected $fillable = [
        'site_id', 'name', 'slug', 'eyebrow', 'heading', 'lead', 'ask_name', 'ask_phone', 'show_topics', 'topics',
        'default_topics', 'offer_webpush', 'offer_sms', 'consent_text', 'clause_id', 'privacy_url', 'button_label',
        'success_message', 'style', 'accent_color', 'list_ids', 'szo_form_slug', 'is_default', 'is_active', 'submissions_count',
    ];

    protected $casts = [
        'ask_name' => 'boolean', 'ask_phone' => 'boolean', 'show_topics' => 'boolean', 'offer_webpush' => 'boolean',
        'offer_sms' => 'boolean', 'is_default' => 'boolean', 'is_active' => 'boolean',
        'topics' => 'array', 'default_topics' => 'array', 'list_ids' => 'array',
    ];

    public const STYLES = ['band' => 'Pasek (szeroki, dwie kolumny)', 'card' => 'Karta', 'inline' => 'Jedna linia (stopka)'];

    public static function defaultFor(?int $siteId = null): ?static
    {
        if (! static::query()->exists()) {
            // Pierwsze uruchomienie: domyślny formularz, żeby /newsletter działało od razu.
            $form = static::makeDefault();
            $form->save();

            return $form;
        }

        return static::query()
            ->where('is_active', true)
            ->when($siteId, fn ($q) => $q->where(fn ($w) => $w->where('site_id', $siteId)->orWhereNull('site_id')))
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    /** Tematy pokazywane w formularzu: wybrane albo wszystkie dostępne. */
    public function topicOptions(): array
    {
        $all = Subscriber::$availableTopics;
        $keys = $this->topics ?: array_keys($all);

        return array_intersect_key($all, array_flip($keys));
    }

    public static function makeDefault(): static
    {
        return new static([
            'name'            => 'Formularz główny',
            'slug'            => 'glowny',
            'eyebrow'         => 'Newsletter FEER',
            'heading'         => 'Bądź na bieżąco z tym, co robimy',
            'lead'            => 'Raz w miesiącu: aktualności, szkolenia, materiały edukacyjne. Bez spamu, wypis jednym kliknięciem.',
            'ask_name'        => true,
            'ask_phone'       => false,
            'show_topics'     => true,
            'topics'          => ['news', 'events', 'materials', 'etr'],
            'default_topics'  => ['news'],
            'consent_text'    => 'Chcę otrzymywać newsletter na podany adres e-mail. Wiem, że mogę się wypisać w każdej chwili.',
            'privacy_url'     => '/polityka-prywatnosci',
            'button_label'    => 'Zapisz się',
            'success_message' => 'Dziękujemy! Sprawdź skrzynkę i kliknij link potwierdzający. Jeśli nie widzisz wiadomości, zajrzyj do folderu spam.',
            'style'           => 'band',
            'is_default'      => true,
            'is_active'       => true,
        ]);
    }
}
