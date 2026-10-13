<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'order', 'icon'];

    /** Ikona dobierana po nazwie, gdy nie wybrano własnej (fragment nazwy => ikona Bootstrap Icons). */
    private const ICON_GUESS = [
        'wcag' => 'bi-universal-access', 'audyt' => 'bi-clipboard-check', 'platform' => 'bi-display', 'szkole' => 'bi-easel', 'warsztat' => 'bi-easel', 'kurs' => 'bi-mortarboard', 'eduk' => 'bi-mortarboard',
        'dostępn' => 'bi-universal-access', 'dostepn' => 'bi-universal-access', 'wspar' => 'bi-heart', 'pomoc' => 'bi-heart',
        'konsult' => 'bi-chat-dots', 'doradz' => 'bi-chat-dots', 'badan' => 'bi-graph-up', 'analiz' => 'bi-graph-up',
        'projekt' => 'bi-kanban', 'wydarz' => 'bi-calendar-event', 'konfer' => 'bi-mic', 'wolont' => 'bi-people',
        'publik' => 'bi-book', 'materia' => 'bi-book', 'kultur' => 'bi-palette', 'sport' => 'bi-trophy', 'dziec' => 'bi-balloon',
        'tłumacz' => 'bi-translate', 'audio' => 'bi-soundwave', 'film' => 'bi-camera-reels',
    ];

    /** Ikona do wyświetlenia: wybrana w panelu albo dobrana po nazwie. */
    public function iconValue(): string
    {
        if (filled($this->icon ?? null)) {
            return $this->icon;
        }
        $name = mb_strtolower((string) $this->name);
        foreach (self::ICON_GUESS as $fragment => $icon) {
            if (str_contains($name, $fragment)) {
                return $icon;
            }
        }

        return 'bi-grid';
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function publishedProjects(): HasMany
    {
        return $this->projects()->where('is_published', true)->whereNull('parent_id')->forCurrentSite()->orderBy('order');
    }
}
