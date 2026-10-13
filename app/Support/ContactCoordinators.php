<?php

namespace App\Support;

use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Support\Collection;

/**
 * Koordynatorzy aktywnych działań, do których można napisać z formularza kontaktowego
 * (pole „Osoba kontaktowa” w kreatorze formularzy i dawny formularz kontaktowy).
 */
class ContactCoordinators
{
    /**
     * @return Collection<int, array{name: string, email: string, project: string}>
     */
    public static function all(): Collection
    {
        // once(): jedno zapytanie na żądanie, choć lista jest potrzebna w kilku miejscach formularza.
        return once(fn () => self::query());
    }

    private static function query(): Collection
    {
        if (! SiteSetting::current()->isModuleEnabled('projects')) {
            return collect();
        }

        return Project::forCurrentSite()->where('is_published', true)
            ->where('is_completed', false)
            ->where('show_coordinator', true)
            ->orderByDesc('is_featured_contact')
            ->orderBy('title')
            ->get()
            ->map(fn ($p) => [
                'name' => $p->coordinator_name ?: $p->title,
                'email' => (string) $p->contactEmail(),
                'project' => $p->title,
            ])
            ->filter(fn ($c) => filled($c['email']))
            ->unique('email')
            ->values();
    }

    /** Etykieta koordynatora po adresie e-mail („Anna Kowalska — Klub Cyfrowy”) albo sam adres. */
    public static function label(?string $email): string
    {
        $c = self::all()->firstWhere('email', $email);

        return $c ? $c['name'].($c['project'] !== $c['name'] ? ' — '.$c['project'] : '') : (string) $email;
    }
}
