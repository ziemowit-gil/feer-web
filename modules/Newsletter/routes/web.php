<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Newsletter\Http\Controllers\Admin\CampaignController as AdminCampaignController;
use Modules\Newsletter\Http\Controllers\Admin\ContentController as AdminContentController;
use Modules\Newsletter\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use Modules\Newsletter\Http\Controllers\Admin\FormController as AdminFormController;
use Modules\Newsletter\Http\Controllers\Admin\ListController as AdminListController;
use Modules\Newsletter\Http\Controllers\Admin\MosaicoController as AdminMosaicoController;
use Modules\Newsletter\Http\Controllers\Admin\SegmentController as AdminSegmentController;
use Modules\Newsletter\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use Modules\Newsletter\Http\Controllers\Admin\SubscriberController as AdminSubscriberController;
use Modules\Newsletter\Http\Controllers\Admin\TemplateController as AdminTemplateController;
use Modules\Newsletter\Http\Controllers\Public\PreferencesController;
use Modules\Newsletter\Http\Controllers\Public\SignupController;
use Modules\Newsletter\Http\Controllers\Public\TrackingController;
use Modules\Newsletter\Http\Controllers\Public\WebhookController;

Route::group(['middleware' => 'web', 'module' => 'newsletter'], function (): void {

// ── Publiczne ─────────────────────────────────────────────────────────────────
Route::get('/newsletter', [SignupController::class, 'show'])->name('newsletter.show');
Route::get('/newsletter/{form:slug}', [SignupController::class, 'showForm'])->name('newsletter.form');
Route::post('/newsletter/zapis', [SignupController::class, 'store'])->name('newsletter.subscribe')->middleware('throttle:5,1');
Route::get('/newsletter/oczekiwanie', [SignupController::class, 'pending'])->name('newsletter.pending');

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
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('subskrybenci/import', [AdminSubscriberController::class, 'importForm'])->name('subskrybenci.import');
        Route::post('subskrybenci/import', [AdminSubscriberController::class, 'import'])->name('subskrybenci.import.store');
        Route::get('subskrybenci/eksport', [AdminSubscriberController::class, 'export'])->name('subskrybenci.export');
        Route::post('subskrybenci/zbiorczo', [AdminSubscriberController::class, 'bulk'])->name('subskrybenci.bulk');
        Route::post('subskrybenci/{subscriber}/anonimizuj', [AdminSubscriberController::class, 'anonymize'])->name('subskrybenci.anonymize');
        Route::post('subskrybenci/{subscriber}/wyslij-potwierdzenie', [AdminSubscriberController::class, 'resendConfirmation'])->name('subskrybenci.resend');
        Route::post('subskrybenci/{subscriber}/synchronizuj', [AdminSubscriberController::class, 'sync'])->name('subskrybenci.sync');
        Route::get('subskrybenci/{subscriber}/dane', [AdminSubscriberController::class, 'personalData'])->name('subskrybenci.data');
        Route::resource('subskrybenci', AdminSubscriberController::class)->parameters(['subskrybenci' => 'subscriber']);

        Route::resource('listy', AdminListController::class)->parameters(['listy' => 'list'])->except('show');
        Route::post('segmenty/policz', [AdminSegmentController::class, 'count'])->name('segmenty.count');
        Route::resource('segmenty', AdminSegmentController::class)->parameters(['segmenty' => 'segment'])->except('show');

        Route::resource('szablony', AdminTemplateController::class)->parameters(['szablony' => 'template'])->except('show');
        Route::get('szablony/{template}/edytor', [AdminTemplateController::class, 'editor'])->name('szablony.editor');
        Route::patch('szablony/{template}/edytor', [AdminTemplateController::class, 'saveEditor'])->name('szablony.editor.save');
        Route::post('szablony/{template}/duplikuj', [AdminTemplateController::class, 'duplicate'])->name('szablony.duplicate');

        Route::resource('kampanie', AdminCampaignController::class)->parameters(['kampanie' => 'campaign']);
        Route::get('kampanie/{campaign}/edytor', [AdminCampaignController::class, 'editor'])->name('kampanie.editor');
        Route::patch('kampanie/{campaign}/edytor', [AdminCampaignController::class, 'saveEditor'])->name('kampanie.editor.save');
        Route::get('kampanie/{campaign}/podglad', [AdminCampaignController::class, 'preview'])->name('kampanie.preview');
        Route::post('kampanie/{campaign}/test', [AdminCampaignController::class, 'sendTest'])->name('kampanie.test');
        Route::post('kampanie/{campaign}/odbiorcy', [AdminCampaignController::class, 'audienceCount'])->name('kampanie.audience');
        Route::post('kampanie/{campaign}/wyslij', [AdminCampaignController::class, 'send'])->name('kampanie.send');
        Route::post('kampanie/{campaign}/zaplanuj', [AdminCampaignController::class, 'schedule'])->name('kampanie.schedule');
        Route::post('kampanie/{campaign}/wstrzymaj', [AdminCampaignController::class, 'pause'])->name('kampanie.pause');
        Route::post('kampanie/{campaign}/wznow', [AdminCampaignController::class, 'resume'])->name('kampanie.resume');
        Route::post('kampanie/{campaign}/anuluj', [AdminCampaignController::class, 'cancel'])->name('kampanie.cancel');
        Route::post('kampanie/{campaign}/duplikuj', [AdminCampaignController::class, 'duplicate'])->name('kampanie.duplicate');
        Route::get('kampanie/{campaign}/raport', [AdminCampaignController::class, 'report'])->name('kampanie.report');
        Route::get('kampanie/{campaign}/raport.csv', [AdminCampaignController::class, 'reportCsv'])->name('kampanie.report.csv');

        Route::resource('formularze', AdminFormController::class)->parameters(['formularze' => 'form'])->except('show');

        Route::get('ustawienia', [AdminSettingsController::class, 'edit'])->name('ustawienia.edit');
        Route::put('ustawienia', [AdminSettingsController::class, 'update'])->name('ustawienia.update');
        Route::post('ustawienia/test', [AdminSettingsController::class, 'testMail'])->name('ustawienia.test');

        Route::get('tresc/{source}', [AdminContentController::class, 'items'])->name('content.items');

        Route::get('mosaico/upload', [AdminMosaicoController::class, 'gallery'])->name('mosaico.gallery');
        Route::post('mosaico/upload', [AdminMosaicoController::class, 'upload'])->name('mosaico.upload');
        Route::get('mosaico/img', [AdminMosaicoController::class, 'image'])->name('mosaico.image');
        Route::post('mosaico/dl', [AdminMosaicoController::class, 'download'])->name('mosaico.download');
    });

});
