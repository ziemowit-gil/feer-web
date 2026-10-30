<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Newsletter\Http\Controllers\Admin\CampaignController as AdminCampaignController;
use Modules\Newsletter\Http\Controllers\Admin\ContentController as AdminContentController;
use Modules\Newsletter\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use Modules\Newsletter\Http\Controllers\Admin\FormController as AdminFormController;
use Modules\Newsletter\Http\Controllers\Admin\ListController as AdminListController;
use Modules\Newsletter\Http\Controllers\Admin\MosaicoController as AdminMosaicoController;
use Modules\Newsletter\Http\Controllers\Admin\ReportController as AdminReportController;
use Modules\Newsletter\Http\Controllers\Admin\SegmentController as AdminSegmentController;
use Modules\Newsletter\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use Modules\Newsletter\Http\Controllers\Admin\SubscriberController as AdminSubscriberController;
use Modules\Newsletter\Http\Controllers\Admin\TemplateController as AdminTemplateController;
use Modules\Newsletter\Http\Controllers\Admin\TopicController as AdminTopicController;
use Modules\Newsletter\Http\Controllers\Public\PreferencesController;
use Modules\Newsletter\Http\Controllers\Public\SignupController;
use Modules\Newsletter\Http\Controllers\Public\TrackingController;
use Modules\Newsletter\Http\Controllers\Public\WebhookController;

Route::group(['middleware' => 'web', 'module' => 'newsletter'], function (): void {

// ── Publiczne ─────────────────────────────────────────────────────────────────
Route::get('/newsletter', [SignupController::class, 'show'])->name('newsletter.show');
Route::post('/newsletter/zapis', [SignupController::class, 'store'])->name('newsletter.subscribe')->middleware('throttle:5,1');
Route::get('/newsletter/oczekiwanie', [SignupController::class, 'pending'])->name('newsletter.pending');
Route::get('/newsletter/{form:slug}/dolacz', [SignupController::class, 'landing'])->name('newsletter.landing');
Route::get('/newsletter/{form:slug}', [SignupController::class, 'showForm'])->name('newsletter.form'); // musi być ostatnia

Route::prefix('n')->name('newsletter.')->group(function (): void {
    Route::get('potwierdz/{token}', [SignupController::class, 'confirm'])->name('confirm');
    Route::get('u/{token}', [PreferencesController::class, 'unsubscribe'])->name('unsubscribe');
    Route::post('u/{token}', [PreferencesController::class, 'doUnsubscribe'])->name('unsubscribe.post');
    Route::get('p/{token}', [PreferencesController::class, 'edit'])->name('preferences');
    Route::put('p/{token}', [PreferencesController::class, 'update'])->name('preferences.update');
    Route::get('p/{token}/dane', [PreferencesController::class, 'export'])->name('preferences.export');
    Route::delete('p/{token}', [PreferencesController::class, 'destroy'])->name('preferences.destroy');
    Route::post('p/{token}/push', [PreferencesController::class, 'linkPush'])->name('preferences.push');
    Route::get('o/{uuid}.gif', [TrackingController::class, 'open'])->name('open');
    Route::get('c/{uuid}/{hash}', [TrackingController::class, 'click'])->name('click');
    Route::get('w/{uuid}', [TrackingController::class, 'web'])->name('web');
    Route::get('wp/{uuid}', [TrackingController::class, 'webPreview'])->name('web.preview');
    Route::post('webhook/{provider}', [WebhookController::class, 'handle'])->name('webhook')
        ;
});

// Zgodność ze starymi linkami w wysłanych już mailach.
Route::get('/subskrypcje', fn () => redirect()->route('newsletter.show', [], 301));
Route::get('/subskrypcje/potwierdz/{token}', [SignupController::class, 'confirm']);
Route::get('/subskrypcje/wypisz/{token}', [PreferencesController::class, 'unsubscribe']);

// ── Adminowe ──────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'verified', '2fa', 'admin-site', 'module:newsletter', 'module-access:newsletter'])
    ->prefix(config('app.admin_prefix', 'admin') . '/newsletter')
    ->name('admin.newsletter.')
    ->group(function (): void {
        // Każda trasa ma własne uprawnienie (App\Support\NewsletterPermissions); wejście do modułu daje `module-access:newsletter`.
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard')->middleware('can:newsletter.view');

        // Subskrybenci, listy, segmenty, tematy i formularze zapisu — dane osobowe (w tym import i eksport).
        Route::middleware('can:newsletter.subscribers')->group(function (): void {
            Route::get('subskrybenci/import', [AdminSubscriberController::class, 'importForm'])->name('subskrybenci.import');
            Route::post('subskrybenci/import', [AdminSubscriberController::class, 'import'])->name('subskrybenci.import.store');
            Route::post('subskrybenci/import/mailerlite', [AdminSubscriberController::class, 'importMailerLite'])->name('subskrybenci.import.mailerlite');
            Route::post('subskrybenci/import/getresponse', [AdminSubscriberController::class, 'importGetResponse'])->name('subskrybenci.import.getresponse');
            Route::get('subskrybenci/import/{import}', [AdminSubscriberController::class, 'importShow'])->name('subskrybenci.import.show');
            Route::put('subskrybenci/import/{import}', [AdminSubscriberController::class, 'importUpdate'])->name('subskrybenci.import.update');
            Route::get('subskrybenci/import/{import}/postep', [AdminSubscriberController::class, 'importProgress'])->name('subskrybenci.import.progress');
            Route::delete('subskrybenci/import/{import}', [AdminSubscriberController::class, 'importDestroy'])->name('subskrybenci.import.destroy');
            Route::get('subskrybenci/eksport', [AdminSubscriberController::class, 'export'])->name('subskrybenci.export');
            Route::post('subskrybenci/zbiorczo', [AdminSubscriberController::class, 'bulk'])->name('subskrybenci.bulk');
            Route::post('subskrybenci/{subscriber}/anonimizuj', [AdminSubscriberController::class, 'anonymize'])->name('subskrybenci.anonymize');
            Route::post('subskrybenci/{subscriber}/wyslij-potwierdzenie', [AdminSubscriberController::class, 'resendConfirmation'])->name('subskrybenci.resend');
            Route::post('subskrybenci/{subscriber}/synchronizuj', [AdminSubscriberController::class, 'sync'])->name('subskrybenci.sync');
            Route::get('subskrybenci/{subscriber}/dane', [AdminSubscriberController::class, 'personalData'])->name('subskrybenci.data');
            Route::resource('subskrybenci', AdminSubscriberController::class)->parameters(['subskrybenci' => 'subscriber']);

            Route::resource('listy', AdminListController::class)->parameters(['listy' => 'list']);
            Route::post('listy/{list}/dodaj', [AdminListController::class, 'addMembers'])->name('listy.add');
            Route::post('listy/{list}/czlonkowie', [AdminListController::class, 'members'])->name('listy.members');
            Route::get('listy/{list}/eksport', [AdminListController::class, 'export'])->name('listy.export');
            Route::post('segmenty/policz', [AdminSegmentController::class, 'count'])->name('segmenty.count');
            Route::resource('segmenty', AdminSegmentController::class)->parameters(['segmenty' => 'segment'])->except('show');

            Route::post('tematy/kolejnosc', [AdminTopicController::class, 'reorder'])->name('tematy.reorder');
            Route::resource('tematy', AdminTopicController::class)->parameters(['tematy' => 'topic'])->except('show');

            Route::resource('formularze', AdminFormController::class)->parameters(['formularze' => 'form'])->except('show');
        });

        // Szablony i maile systemowe.
        Route::middleware('can:newsletter.templates')->group(function (): void {
            Route::get('mosaico', [AdminTemplateController::class, 'mosaico'])->name('mosaico');
            Route::get('szablony/systemowy/{key}', [AdminTemplateController::class, 'system'])->name('szablony.system');
            Route::resource('szablony', AdminTemplateController::class)->parameters(['szablony' => 'template'])->except('show');
            Route::get('szablony/{template}/edytor', [AdminTemplateController::class, 'editor'])->name('szablony.editor');
            Route::patch('szablony/{template}/edytor', [AdminTemplateController::class, 'saveEditor'])->name('szablony.editor.save');
            Route::post('szablony/{template}/duplikuj', [AdminTemplateController::class, 'duplicate'])->name('szablony.duplicate');
        });

        // Kampanie: tworzenie i edycja (statyczne adresy przed {campaign}).
        Route::middleware('can:newsletter.campaigns')->group(function (): void {
            Route::get('kampanie/create', [AdminCampaignController::class, 'create'])->name('kampanie.create');
            Route::post('kampanie', [AdminCampaignController::class, 'store'])->name('kampanie.store');
            Route::get('kampanie/{campaign}/edit', [AdminCampaignController::class, 'edit'])->name('kampanie.edit');
            Route::match(['put', 'patch'], 'kampanie/{campaign}', [AdminCampaignController::class, 'update'])->name('kampanie.update');
            Route::delete('kampanie/{campaign}', [AdminCampaignController::class, 'destroy'])->name('kampanie.destroy');
            Route::get('kampanie/{campaign}/edytor', [AdminCampaignController::class, 'editor'])->name('kampanie.editor');
            Route::patch('kampanie/{campaign}/edytor', [AdminCampaignController::class, 'saveEditor'])->name('kampanie.editor.save');
            Route::post('kampanie/{campaign}/test', [AdminCampaignController::class, 'sendTest'])->name('kampanie.test');
            Route::post('kampanie/{campaign}/odbiorcy', [AdminCampaignController::class, 'audienceCount'])->name('kampanie.audience');
            Route::post('kampanie/{campaign}/duplikuj', [AdminCampaignController::class, 'duplicate'])->name('kampanie.duplicate');
        });

        // Kampanie: przeglądanie.
        Route::middleware('can:newsletter.view')->group(function (): void {
            Route::get('kampanie', [AdminCampaignController::class, 'index'])->name('kampanie.index');
            Route::get('kampanie/{campaign}', [AdminCampaignController::class, 'show'])->name('kampanie.show');
            Route::get('kampanie/{campaign}/podglad', [AdminCampaignController::class, 'preview'])->name('kampanie.preview');
        });

        // Kampanie: wysyłka — osobne uprawnienie, bo wysyłki nie da się cofnąć.
        Route::middleware('can:newsletter.send')->group(function (): void {
            Route::post('kampanie/{campaign}/wyslij', [AdminCampaignController::class, 'send'])->name('kampanie.send');
            Route::post('kampanie/{campaign}/zaplanuj', [AdminCampaignController::class, 'schedule'])->name('kampanie.schedule');
            Route::post('kampanie/{campaign}/wstrzymaj', [AdminCampaignController::class, 'pause'])->name('kampanie.pause');
            Route::post('kampanie/{campaign}/wznow', [AdminCampaignController::class, 'resume'])->name('kampanie.resume');
            Route::post('kampanie/{campaign}/anuluj', [AdminCampaignController::class, 'cancel'])->name('kampanie.cancel');
            Route::post('kampanie/{campaign}/ponow-nieudane', [AdminCampaignController::class, 'retryFailed'])->name('kampanie.retry');
        });

        // Raporty kampanii (zawierają adresy odbiorców).
        Route::middleware('can:newsletter.reports')->group(function (): void {
            Route::get('kampanie/{campaign}/raport', [AdminCampaignController::class, 'report'])->name('kampanie.report');
            Route::get('kampanie/{campaign}/raport.csv', [AdminCampaignController::class, 'reportCsv'])->name('kampanie.report.csv');
            Route::get('kampanie/{campaign}/raport.pdf', [AdminReportController::class, 'campaignPdf'])->name('kampanie.report.pdf');
            Route::get('raporty', [AdminReportController::class, 'index'])->name('raporty.index');
            Route::get('raporty.csv', [AdminReportController::class, 'csv'])->name('raporty.csv');
            Route::get('raporty.pdf', [AdminReportController::class, 'pdf'])->name('raporty.pdf');
        });

        Route::middleware('can:newsletter.settings')->group(function (): void {
            Route::get('ustawienia', [AdminSettingsController::class, 'edit'])->name('ustawienia.edit');
            Route::put('ustawienia', [AdminSettingsController::class, 'update'])->name('ustawienia.update');
            Route::post('ustawienia/test', [AdminSettingsController::class, 'testMail'])->name('ustawienia.test');
        });

        // Zasoby edytora Mosaico (obrazy, treści ze strony) — dla kampanii albo szablonów.
        Route::middleware('can:newsletter.design')->group(function (): void {
            Route::get('tresc/{source}', [AdminContentController::class, 'items'])->name('content.items');

            Route::get('mosaico/upload', [AdminMosaicoController::class, 'gallery'])->name('mosaico.gallery');
            Route::post('mosaico/upload', [AdminMosaicoController::class, 'upload'])->name('mosaico.upload');
            Route::get('mosaico/img', [AdminMosaicoController::class, 'image'])->name('mosaico.image');
            Route::post('mosaico/dl', [AdminMosaicoController::class, 'download'])->name('mosaico.download');
        });
    });

});
