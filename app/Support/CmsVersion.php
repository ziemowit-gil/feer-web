<?php

namespace App\Support;

/**
 * Wersja główna CMS (config/cms.php). Gdy cache konfiguracji powstał przed dodaniem pliku
 * (auto-pull bez deployu), odczytujemy wersję prosto z pliku.
 */
class CmsVersion
{
    public static function get(): string
    {
        $v = config('cms.version');
        if (blank($v) && is_file(config_path('cms.php'))) {
            $v = (require config_path('cms.php'))['version'] ?? null;
        }

        return (string) ($v ?: '?');
    }
}
