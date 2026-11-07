<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Contact\Http\Controllers\SectionController;
use Modules\Contact\Support\ContactSections;

Route::group(['middleware' => 'web', 'module' => 'contact'], function (): void {
    // Automatyczne podstrony kontaktu: istnieją tylko dla sekcji, które mają dane (inaczej 404 z kontrolera).
    Route::get('/kontakt/{section}', [SectionController::class, 'show'])
        ->name('contact.section')
        ->whereIn('section', ContactSections::SLUGS);
});
