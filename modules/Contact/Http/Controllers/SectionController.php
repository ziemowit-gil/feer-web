<?php

declare(strict_types=1);

namespace Modules\Contact\Http\Controllers;

use App\Http\Controllers\ContactController;
use App\Http\Controllers\Controller;

/**
 * Automatyczna podstrona kontaktu (/kontakt/{section}). Kontroler leży w module, żeby trasa wyprzedzała
 * catch-all stron (ModuleServiceProvider::prioritizeModuleRoutes); dane i widok składa ContactController.
 */
final class SectionController extends Controller
{
    public function show(string $section)
    {
        return app(ContactController::class)->section($section);
    }
}
