<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Zgłoszenie „Wsparcie technologiczne dla NGO” (odpłatna działalność pożytku publicznego).
 */
class NgoSupportRequest extends Model
{
    /** @use HasFactory<\Database\Factories\NgoSupportRequestFactory> */
    use HasFactory;

    public const ROLES = ['Zarząd', 'Koordynator projektu', 'Pracownik', 'Wolontariusz', 'Inna'];

    public const SUPPORT_AREAS = [
        'M365/Google Workspace',
        'Strona WWW / CMS',
        'Bezpieczeństwo i kopie zapasowe',
        'Serwery i hosting',
        'Automatyzacja procesów',
        'Dostępność cyfrowa (WCAG)',
        'Inne',
    ];

    public const CONTACT_CHANNELS = [
        'email'          => 'E-mail',
        'phone'          => 'Telefon',
        'online_meeting' => 'Spotkanie online',
    ];

    public const STATUSES = [
        'new'         => 'Nowe',
        'in_progress' => 'W realizacji',
        'contacted'   => 'Skontaktowano się',
        'completed'   => 'Zakończone',
        'archived'    => 'Zarchiwizowane',
    ];

    protected $fillable = [
        'name',
        'organization_name',
        'role',
        'email',
        'phone',
        'website',
        'support_areas',
        'description',
        'preferred_contact_channel',
        'preferred_time',
        'status',
        'privacy_consent',
    ];

    protected $attributes = [
        'status' => 'new',
        'preferred_contact_channel' => 'email',
    ];

    protected $casts = [
        'support_areas'   => 'array',
        'privacy_consent' => 'boolean',
    ];

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function contactChannelLabel(): string
    {
        return self::CONTACT_CHANNELS[$this->preferred_contact_channel] ?? $this->preferred_contact_channel;
    }
}
