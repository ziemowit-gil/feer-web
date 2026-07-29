<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'user_name', 'event', 'subject_type', 'subject_id', 'subject_label',
    ];

    /** Czytelne etykiety zdarzeń. */
    public const EVENTS = [
        'created' => 'Utworzenie',
        'updated' => 'Edycja',
        'deleted' => 'Usunięcie',
    ];

    /** Czytelne nazwy typów treści. */
    public const SUBJECTS = [
        'News' => 'Aktualność',
        'Page' => 'Strona',
        'Project' => 'Projekt',
        'LandingPage' => 'Landing page',
        'AnnualReport' => 'Sprawozdanie',
        'User' => 'Użytkownik',
        'UserGroup' => 'Grupa użytkowników',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function eventLabel(): string
    {
        return self::EVENTS[$this->event] ?? $this->event;
    }

    public function subjectLabel(): string
    {
        return self::SUBJECTS[$this->subject_type] ?? $this->subject_type;
    }
}
