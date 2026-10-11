<?php

declare(strict_types=1);

namespace App\Support;

use App\Modules\ModuleManager;
use App\Modules\ModuleManifest;
use Illuminate\Support\Collection;

/**
 * Dane modułów instalowanych (rozszerzeń z katalogu modules/) do widoku kart —
 * wspólne dla strony „Moduły systemu" i zakładki Ustawienia → Moduły.
 */
final class PluginModules
{
    /** Ikony Font Awesome dla znanych rozszerzeń; reszta dostaje puzzel. */
    private const ICONS = [
        'blog' => 'fa-lightbulb',
        'newsletter' => 'fa-envelope-open-text',
        'sklep' => 'fa-cart-shopping',
        'events' => 'fa-calendar-days',
        'faq' => 'fa-circle-question',
        'bip' => 'fa-landmark',
        'jobs' => 'fa-briefcase',
        'landing' => 'fa-rocket',
        'materials' => 'fa-book-open',
        'reports' => 'fa-file-contract',
        'support' => 'fa-hand-holding-heart',
        'volunteering' => 'fa-hands-holding-child',
        'cooperation' => 'fa-handshake',
    ];

    public const STATUS_LABELS = [
        'active' => 'Aktywny',
        'inactive' => 'Nieaktywny',
        'missing' => 'Niezainstalowany',
    ];

    public function __construct(private readonly ModuleManager $modules) {}

    /**
     * @return Collection<string, array{id: string, manifest: ModuleManifest, status: string, status_label: string, icon: string}>
     */
    public function cards(): Collection
    {
        return $this->modules->all()->map(function (ModuleManifest $manifest, string $id) {
            $status = $this->modules->status($id) ?? 'missing';

            return [
                'id' => $id,
                'manifest' => $manifest,
                'status' => $status,
                'status_label' => self::STATUS_LABELS[$status] ?? self::STATUS_LABELS['missing'],
                'icon' => self::ICONS[$id] ?? 'fa-puzzle-piece',
            ];
        });
    }
}
