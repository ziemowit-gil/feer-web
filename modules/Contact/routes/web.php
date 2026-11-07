<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Contact\Http\Controllers\Admin\ContactController as AdminContactController;
use Modules\Contact\Http\Controllers\SectionController;
use Modules\Contact\Http\Controllers\VcardController;
use Modules\Contact\Support\ContactSections;

Route::group(['middleware' => 'web', 'module' => 'contact'], function (): void {
    // Wizytówka organizacji (.vcf) — przed podstronami, bo też jest pod /kontakt/.
    Route::get('/kontakt/kontakt.vcf', [VcardController::class, 'show'])->name('contact.vcard');

    // Automatyczne podstrony kontaktu: istnieją tylko dla sekcji, które mają dane (inaczej 404 z kontrolera).
    Route::get('/kontakt/{section}', [SectionController::class, 'show'])
        ->name('contact.section')
        ->whereIn('section', ContactSections::SLUGS);
});

// ── Panel: Komunikacja → Kontakt (opcje modułu, formularz, podstrony, „Gdzie działamy”) ──────────────────
Route::middleware(['web', 'auth', 'verified', '2fa', 'admin-site', 'module:contact', 'module-access:contact'])
    ->prefix(config('app.admin_prefix', 'admin').'/kontakt')
    ->name('admin.kontakt.')
    ->group(function (): void {
        Route::get('/', [AdminContactController::class, 'index'])->name('index');
        Route::put('/', [AdminContactController::class, 'update'])->name('update');
    });
