<?php

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Model;

/** Oś czasu subskrybenta. */
class NewsletterSubscriberEvent extends Model
{
    protected $table = 'newsletter_subscriber_events';

    public $timestamps = false;

    protected $fillable = ['subscriber_id', 'type', 'campaign_id', 'actor_id', 'meta', 'created_at'];

    protected $casts = ['meta' => 'array', 'created_at' => 'datetime'];

    public const LABELS = [
        'subscribed' => 'Zapis', 'doi_sent' => 'Wysłano potwierdzenie', 'confirmed' => 'Potwierdził zapis',
        'preferences_changed' => 'Zmiana preferencji', 'unsubscribed' => 'Wypisany', 'resubscribed' => 'Ponowny zapis',
        'bounced' => 'Odbicie', 'complained' => 'Zgłoszenie spamu', 'imported' => 'Zaimportowany',
        'exported' => 'Wyeksportowany', 'anonymized' => 'Zanonimizowany', 'status_changed' => 'Zmiana statusu',
        'sent' => 'Wysłano wiadomość', 'opened' => 'Otworzył', 'clicked' => 'Kliknął', 'crm_synced' => 'Zsynchronizowano z CRM',
        'szo_synced' => 'Zsynchronizowano z SZO', 'list_added' => 'Dodano do listy', 'list_removed' => 'Usunięto z listy',
        'webpush_added' => 'Włączył powiadomienia push', 'data_exported' => 'Pobrał swoje dane',
    ];

    public function label(): string
    {
        return self::LABELS[$this->type] ?? $this->type;
    }
}
