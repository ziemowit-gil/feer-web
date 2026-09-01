<?php

use App\Http\Controllers\Auth\MemberMicrosoftAuthController;
use App\Http\Controllers\Admin\ActivityController as AdminActivityController;
use App\Http\Controllers\Admin\AnnualReportController as AdminAnnualReportController;
use App\Http\Controllers\Admin\ApprovalController as AdminApprovalController;
use App\Http\Controllers\Admin\CalendarController as AdminCalendarController;
use App\Http\Controllers\Admin\EditLockController as AdminEditLockController;
use App\Http\Controllers\Admin\HelpPointController as AdminHelpPointController;
use App\Http\Controllers\Admin\OrganizationController as AdminOrganizationController;
use App\Http\Controllers\FederationController;
use App\Http\Controllers\MemberOrganizationController;
use App\Http\Controllers\OrganizationLoginController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\RevisionController as AdminRevisionController;
use App\Http\Controllers\Admin\SearchController as AdminSearchController;
use App\Http\Controllers\Admin\TrashController as AdminTrashController;
use App\Http\Controllers\Admin\LinkCheckController as AdminLinkCheckController;
use App\Http\Controllers\Admin\LandingPageController as AdminLandingPageController;
use App\Http\Controllers\Admin\AttachmentController as AdminAttachmentController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ContentPortabilityController as AdminContentPortabilityController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EducationalMaterialController as AdminEducationalMaterialController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\GalleryImageController;
use App\Http\Controllers\Admin\HeroSlideController;
use App\Http\Controllers\Admin\MaterialSubscriberController as AdminMaterialSubscriberController;
use App\Http\Controllers\Admin\MediaLibraryController;
use App\Http\Controllers\Admin\MeetingSignupController as AdminMeetingSignupController;
use App\Http\Controllers\Admin\NavItemController;
use App\Http\Controllers\Admin\NewsCategoryController as AdminNewsCategoryController;
use App\Http\Controllers\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\Admin\NewsletterController as AdminNewsletterController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PageImageController as AdminPageImageController;
use App\Http\Controllers\Admin\PartnerController as AdminPartnerController;
use App\Http\Controllers\Admin\PollController as AdminPollController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\QuickActionController as AdminQuickActionController;
use App\Http\Controllers\Admin\RedirectController as AdminRedirectController;
use App\Http\Controllers\Admin\AccessibilityReportController as AdminAccessibilityReportController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\TimelineController as AdminTimelineController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\UserGroupController as AdminUserGroupController;
use App\Http\Controllers\Admin\ContentTemplateController as AdminContentTemplateController;
use App\Http\Controllers\Admin\TagController as AdminTagController;
use App\Http\Controllers\Admin\TaskController as AdminTaskController;
use App\Http\Controllers\Admin\VolunteerAdController as AdminVolunteerAdController;
use App\Http\Controllers\Admin\MailTemplateController as AdminMailTemplateController;
use App\Http\Controllers\Admin\ModuleController as AdminModuleController;
use App\Http\Controllers\Admin\WcagScanController as AdminWcagScanController;
use App\Http\Controllers\AccessibilityController;
use App\Http\Controllers\AccessibilityReportController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EducationalMaterialController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HelpMapController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MaterialSubscriberController;
use App\Http\Controllers\MeetingSignupController;
use App\Http\Controllers\NewsArchiveController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PollVoteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ShortcutController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\Bip\BipController;
use App\Http\Controllers\Bip\BipDocumentController as AdminBipDocumentController;
use App\Http\Controllers\BannerTrackingController;
use App\Http\Controllers\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Admin\BannerZoneController as AdminBannerZoneController;
use App\Http\Controllers\Admin\HomepageLayoutController;
use App\Http\Controllers\Admin\BrandAccessUserController as AdminBrandAccessUserController;
use App\Http\Controllers\Admin\DocxImportController;
use App\Http\Controllers\Admin\FacilitatorController as AdminFacilitatorController;
use App\Http\Controllers\Admin\EtrController as AdminEtrController;
use App\Http\Controllers\Admin\MemberInvitationController as AdminMemberInvitationController;
use App\Http\Controllers\Admin\CacheController as AdminCacheController;
use App\Http\Controllers\Admin\HealthCheckController as AdminHealthCheckController;
use App\Http\Controllers\EtrController;
use App\Http\Controllers\MemberInvitationController;
use App\Http\Controllers\VolunteerController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\SubscribeController;
use App\Http\Controllers\Admin\CampaignController as AdminCampaignController;
use App\Http\Controllers\Admin\SubscriberController as AdminSubscriberController;
use App\Http\Controllers\JobOfferController;
use App\Http\Controllers\Admin\JobOfferController as AdminJobOfferController;
use App\Http\Controllers\JoinUsController;
use App\Http\Controllers\CooperationFormController;
use App\Http\Controllers\Admin\CooperationRequestController as AdminCooperationRequestController;
use App\Http\Controllers\PodcastController;
use App\Http\Controllers\PayuWebhookController;
use App\Http\Controllers\Admin\PodcastController as AdminPodcastController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\Admin\FormularzeController as AdminFormularzeController;
use App\Http\Controllers\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\InstallController;
use App\Http\Middleware\RedirectIfInstalled;
use Illuminate\Support\Facades\Route;

// ── Instalator ────────────────────────────────────────────────────────────────
Route::middleware([RedirectIfInstalled::class])->prefix('install')->group(function () {
    Route::get('/',  [InstallController::class, 'index'])->name('install.index');
    Route::post('/', [InstallController::class, 'post'])->name('install.post');
});

// Poza RedirectIfInstalled — instalacja jest już zakończona (installed.lock istnieje)
// w chwili, gdy wdrażający dociera do kroku "done" i pobiera certyfikat.
Route::get('/install/certificate', [InstallController::class, 'downloadCertificate'])->name('install.certificate');

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/etr', [EtrController::class, 'about'])->name('etr.about');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/mapa-strony', [SitemapController::class, 'page'])->name('sitemap.page');

Route::middleware('module:projects')->group(function () {
    Route::get('/projekty', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projekty/archiwum', [ProjectController::class, 'archive'])->name('projects.archive');
    Route::get('/projekty/{project:slug}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/kategoria/{category:slug}', [ProjectController::class, 'category'])->name('categories.show');
});

Route::get('/organizacje-czlonkowskie', [FederationController::class, 'organizations'])->name('federation.organizations');
Route::get('/organizacje-czlonkowskie/{organization:slug}', [FederationController::class, 'organizationShow'])->name('federation.organizations.show');
// Uwaga: /dolacz-do-nas jest już zajęte przez generyczną stronę „Dołącz do nas"
// (oferty pracy + wolontariat, JoinUsController) — członkostwo w federacji ma
// osobny, bardziej precyzyjny adres.
Route::get('/dolacz-do-federacji', [FederationController::class, 'joinUs'])->name('federation.join');
Route::post('/dolacz-do-federacji', [FederationController::class, 'submitApplication'])->name('federation.join.submit')->middleware('throttle:5,10');
Route::get('/dolacz-do-federacji/krs/{krs}', [FederationController::class, 'lookupKrs'])->name('federation.join.krs')->middleware('throttle:20,1')->where('krs', '[0-9]{1,10}');

Route::get('/mapa-pomocy', [HelpMapController::class, 'index'])->name('help-map.index')->middleware('module:help_map');

// Samoobsługowa edycja danych organizacji z poziomu Strefy członkowskiej
// (logowanie indywidualne per organizacja — patrz OrganizationLoginController).
Route::get('/organizacje/logowanie', [OrganizationLoginController::class, 'showLogin'])->name('organization.login');
Route::post('/organizacje/logowanie', [OrganizationLoginController::class, 'login'])->name('organization.login.submit')->middleware('throttle:10,1');
Route::post('/organizacje/wyloguj', [OrganizationLoginController::class, 'logout'])->name('organization.logout');
Route::get('/organizacje/panel', [MemberOrganizationController::class, 'edit'])->name('organization.panel.edit');
Route::put('/organizacje/panel', [MemberOrganizationController::class, 'update'])->name('organization.panel.update');
Route::delete('/organizacje/panel/zdjecia/{media}', [MemberOrganizationController::class, 'destroyPhoto'])->name('organization.panel.photos.destroy');

Route::middleware('module:news')->group(function () {
    Route::get('/aktualnosci', [NewsController::class, 'index'])->name('news.index');
    Route::get('/archiwum', [NewsArchiveController::class, 'index'])->name('news.archiwum');
    // Kanał RSS — przed trasą ze slugiem, inaczej „rss.xml" zostałoby uznane za slug aktualności.
    Route::get('/aktualnosci/rss.xml', [FeedController::class, 'news'])->name('news.feed');
    Route::get('/rss.xml', [FeedController::class, 'news'])->name('feed');
    Route::get('/aktualnosci/{news:slug}', [NewsController::class, 'show'])->name('news.show');
    Route::get('/aktualnosci/{news:slug}/pdf', [NewsController::class, 'pdf'])->name('news.pdf');
});

Route::post('/ankieta/{poll}/glosuj', [PollVoteController::class, 'store'])->name('polls.vote')->middleware(['module:polls', 'throttle:3,1']);

Route::middleware('module:materials')->group(function () {
    Route::get('/materialy', [EducationalMaterialController::class, 'index'])->name('materials.index');
    Route::post('/materialy/zapis', [MaterialSubscriberController::class, 'store'])->name('materials.subscribe')->middleware('throttle:5,1');
});


Route::middleware('module:volunteering')->group(function () {
    Route::get('/wolontariat', [VolunteerController::class, 'index'])->name('volunteer.index');
    Route::get('/wolontariat/{ad:slug}', [VolunteerController::class, 'show'])->name('volunteer.show');
});

Route::middleware('module:jobs')->group(function () {
    Route::get('/praca', [JobOfferController::class, 'index'])->name('praca.index');
    Route::get('/praca/{offer:slug}/pdf', [JobOfferController::class, 'pdf'])->name('praca.pdf');
    Route::get('/praca/{offer:slug}', [JobOfferController::class, 'show'])->name('praca.show');
});

Route::get('/dolacz-do-nas', [JoinUsController::class, 'index'])->name('join-us.index');

Route::middleware('module:events')->group(function () {
    Route::get('/wydarzenia', [EventController::class, 'index'])->name('events.index');
    Route::get('/wydarzenia/{event:slug}', [EventController::class, 'show'])->name('events.show');
});

Route::get('/faq', [FaqController::class, 'index'])->name('faq.index')->middleware('module:faq');

Route::get('/sprawozdania', [ReportController::class, 'index'])->name('reports.index')->middleware('module:reports');

Route::middleware('module:landing')->group(function () {
    Route::get('/lp/{slug}', [LandingPageController::class, 'show'])->name('lp.show');
    Route::post('/lp/{slug}/rejestracja', [LandingPageController::class, 'register'])->name('lp.register')->middleware('throttle:10,1');
    Route::get('/lp/{slug}/kalendarz.ics', [LandingPageController::class, 'calendar'])->name('lp.calendar');
});

Route::get('/szukaj', [SearchController::class, 'index'])->name('search');

Route::get('/newsletter', [NewsletterController::class, 'index'])->name('newsletter.show');

// Kampanie zbiórkowe
Route::get('/kampanie', [CampaignController::class, 'index'])->name('kampanie.index');
Route::get('/kampanie/{slug}', [CampaignController::class, 'show'])->name('kampanie.show');

// Subskrypcje tematyczne
Route::get('/subskrypcje', [SubscribeController::class, 'create'])->name('subskrypcje.form');
Route::post('/subskrypcje', [SubscribeController::class, 'store'])->name('subskrypcje.store')->middleware('throttle:5,1');
Route::get('/subskrypcje/oczekiwanie', [SubscribeController::class, 'pending'])->name('subskrypcje.pending');
Route::get('/subskrypcje/potwierdz/{token}', [SubscribeController::class, 'confirm'])->name('subskrypcje.confirm');
Route::get('/subskrypcje/wypisz/{token}', [SubscribeController::class, 'unsubscribe'])->name('subskrypcje.unsubscribe');
Route::delete('/subskrypcje/wypisz/{token}', [SubscribeController::class, 'doUnsubscribe'])->name('subskrypcje.do-unsubscribe');

Route::get('/wsparcie', [SupportController::class, 'index'])->name('support.show')->middleware('module:support');

// Podcasty
Route::get('/podcasty', [PodcastController::class, 'index'])->name('podcasts.index');
Route::get('/podcasty/{podcast:slug}', [PodcastController::class, 'show'])->name('podcasts.show');
Route::get('/podcasty/{podcast}/audio', [PodcastController::class, 'stream'])->name('podcasts.stream')->middleware('auth');

// PayU — webhook bez CSRF (przyjmuje JSON od serwera PayU)
Route::post('/payu/webhook', [PayuWebhookController::class, 'handle'])->name('payu.webhook')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// BIP: strona główna, rejestr zmian i szczegół dokumentu — kontrolowane przez moduł.
Route::middleware('module:bip')->group(function () {
    Route::get('/bip', [BipController::class, 'index'])->name('bip');
    Route::get('/bip/rejestr-zmian', [BipController::class, 'changeLog'])->name('bip.changelog');
    Route::get('/bip/{bipDocument:slug}', [BipController::class, 'show'])->name('bip.document');
});
Route::get('/instagram', [ShortcutController::class, 'instagram'])->name('shortcut.instagram');
Route::get('/fb', [ShortcutController::class, 'facebook'])->name('shortcut.fb');
Route::get('/facebook', [ShortcutController::class, 'facebook'])->name('shortcut.facebook');
Route::get('/li', [ShortcutController::class, 'linkedin'])->name('shortcut.li');
Route::get('/linkedin', [ShortcutController::class, 'linkedin'])->name('shortcut.linkedin');

// Deklaracja dostępności (ustawa o dostępności cyfrowej) + formularz zgłaszania barier.
Route::get('/deklaracja-dostepnosci', [AccessibilityController::class, 'show'])->name('accessibility.show');
Route::post('/deklaracja-dostepnosci/zglos', [AccessibilityReportController::class, 'store'])->name('accessibility.report')->middleware('throttle:5,1');

// Śledzenie bannerów (wyświetlenia + kliknięcia).
Route::post('/b/imp/{banner}', [BannerTrackingController::class, 'impression'])
    ->name('banner.impression')
    ->middleware('throttle:120,1');
Route::get('/b/click/{banner}', [BannerTrackingController::class, 'click'])
    ->name('banner.click');

Route::get('/kontakt', [ContactController::class, 'index'])->name('contact.show');
Route::post('/kontakt', [ContactController::class, 'store'])->name('contact.store')->middleware('throttle:5,1');
Route::post('/kontakt/przyjde', [MeetingSignupController::class, 'store'])->name('meeting.signup')->middleware('throttle:5,1');

// Samodzielny moduł rezerwacji spotkań (bez panelu CMS, dostępny publicznie).
Route::get('/rezerwuj-spotkanie-modul', [MeetingSignupController::class, 'publicShow'])->name('booking.show');
Route::post('/rezerwuj-spotkanie-modul', [MeetingSignupController::class, 'publicStore'])->name('booking.store')->middleware('throttle:5,1');

Route::redirect('/dashboard', '/'.config('app.admin_prefix', 'admin'))->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/powiadomienia', [ProfileController::class, 'updateNotifications'])->name('profile.notifications');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', '2fa'])->prefix(config('app.admin_prefix', 'admin'))->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Historia zmian treści (dostęp weryfikowany per moduł w kontrolerze).
    Route::get('historia/{type}/{id}', [AdminRevisionController::class, 'index'])->name('historia.index');
    Route::get('historia/{type}/{id}/json', [AdminRevisionController::class, 'json'])->name('historia.json');
    Route::post('historia/{type}/{id}/przywroc/{revision}', [AdminRevisionController::class, 'restore'])->name('historia.restore');

    // Globalna wyszukiwarka panelu (paleta poleceń).
    Route::get('szukaj', AdminSearchController::class)->name('search');

    // Centrum powiadomień — oznaczanie jako przejrzane.
    Route::post('powiadomienia/widziano', [AdminNotificationController::class, 'seen'])->name('powiadomienia.seen');

    // Zadania (lista + CRUD + szybkie oznaczenie jako done).
    Route::resource('zadania', AdminTaskController::class)->parameters(['zadania' => 'zadanie'])->except('show');
    Route::post('zadania/{zadanie}/done', [AdminTaskController::class, 'done'])->name('zadania.done');

    // Blokada równoczesnej edycji (heartbeat, dostęp per moduł w kontrolerze).
    Route::post('blokada-edycji', AdminEditLockController::class)->name('edit-lock');

    // Szablony treści — API JSON (dla JS w formularzach) + zarządzanie.
    Route::get('szablony', [AdminContentTemplateController::class, 'index'])->name('szablony.index');
    Route::get('szablony/zarzadzaj', [AdminContentTemplateController::class, 'manage'])->name('szablony.manage');
    Route::get('szablony/{template}/dane', [AdminContentTemplateController::class, 'load'])->name('szablony.load');
    Route::post('szablony', [AdminContentTemplateController::class, 'store'])->name('szablony.store');
    Route::delete('szablony/{template}', [AdminContentTemplateController::class, 'destroy'])->name('szablony.destroy');

    // Kalendarz redakcyjny.
    Route::get('kalendarz', [AdminCalendarController::class, 'index'])->name('kalendarz.index');

    // Kosz — miękkie usuwanie treści (dostęp per moduł w kontrolerze).
    Route::get('kosz', [AdminTrashController::class, 'index'])->name('kosz.index');
    Route::post('kosz/{type}/{id}/przywroc', [AdminTrashController::class, 'restore'])->name('kosz.restore');
    Route::delete('kosz/{type}/{id}', [AdminTrashController::class, 'forceDelete'])->name('kosz.force');

    Route::middleware(['module:pages', 'module-access:pages'])->group(function () {
        Route::get('osoby', [AdminPageController::class, 'persons'])->name('osoby.index');
        Route::get('osoby/create', [AdminPageController::class, 'createPerson'])->name('osoby.create');
        Route::get('osoby/{page}/scal', [AdminPageController::class, 'showMergePerson'])->name('osoby.scal');
        Route::post('osoby/{page}/scal', [AdminPageController::class, 'performMergePerson'])->name('osoby.scal.wykonaj');
        Route::delete('osoby/{page}', [AdminPageController::class, 'destroy'])->name('osoby.destroy');
        Route::get('podstrony/eksport', [AdminPageController::class, 'export'])->name('podstrony.eksport');
        Route::get('raporty/brakujace-alt', [AdminPageController::class, 'missingAltReport'])->name('raporty.brakujace-alt');
        Route::resource('podstrony', AdminPageController::class)->parameters(['podstrony' => 'page']);
        Route::put('edycja-na-zywo', [\App\Http\Controllers\Admin\InlineEditController::class, 'update'])->name('inline-edit.update');
        Route::post('podstrony/{page}/pliki', [AdminAttachmentController::class, 'storeForPage'])->name('podstrony.pliki.store');
        Route::get('podstrony/{page}/dostep/eksport', [AdminBrandAccessUserController::class, 'export'])->name('podstrony.dostep.eksport');
        Route::get('podstrony/{page}/dostep', [AdminBrandAccessUserController::class, 'index'])->name('podstrony.dostep.index');
        Route::post('podstrony/{page}/dostep', [AdminBrandAccessUserController::class, 'store'])->name('podstrony.dostep.store');
        Route::post('podstrony/{page}/dostep/{user}/reset-haslo', [AdminBrandAccessUserController::class, 'resetPassword'])->name('podstrony.dostep.reset');
        Route::patch('podstrony/{page}/dostep/{user}/aktywuj', [AdminBrandAccessUserController::class, 'toggleActive'])->name('podstrony.dostep.aktywuj');
        Route::delete('podstrony/{page}/dostep/{user}', [AdminBrandAccessUserController::class, 'destroy'])->name('podstrony.dostep.destroy');
        Route::post('podstrony/{page}/zdjecia', [AdminPageImageController::class, 'store'])->name('podstrony.zdjecia.store');
        Route::put('podstrony/zdjecia/{image}', [AdminPageImageController::class, 'update'])->name('podstrony.zdjecia.update');
        Route::delete('podstrony/zdjecia/{image}', [AdminPageImageController::class, 'destroy'])->name('podstrony.zdjecia.destroy');
        Route::post('podstrony/zbiorczo', [AdminPageController::class, 'bulk'])->name('podstrony.bulk');
        Route::post('podstrony/{page}/klonuj', [AdminPageController::class, 'clone'])->name('podstrony.clone');
        Route::patch('podstrony/{page}/kolejnosc', [AdminPageController::class, 'updateOrder'])->name('podstrony.kolejnosc');
        Route::patch('podstrony/{page}/widocznosc', [AdminPageController::class, 'toggleVisibility'])->name('podstrony.widocznosc');
        Route::patch('podstrony/{page}/wylacz', [AdminPageController::class, 'toggleDisabled'])->name('podstrony.wylacz');
        Route::patch('podstrony/{page}/wyroznienie', [AdminPageController::class, 'toggleFeatured'])->name('podstrony.wyroznienie');

    });

    // Oś czasu (historia) strony „O organizacji" jako osobna pozycja w menu — moduł niezależny od "Podstrony".
    Route::middleware(['module:pages', 'module:timeline', 'module-access:pages'])->group(function () {
        Route::get('os-czasu', [AdminTimelineController::class, 'edit'])->name('os-czasu.edit');
        Route::put('os-czasu/{page}', [AdminTimelineController::class, 'update'])->name('os-czasu.update');
    });

    // Zgłoszenia z formularzy współpracy — moduł niezależny od "Podstrony".
    Route::middleware(['module:pages', 'module:cooperation', 'module-access:pages'])->group(function () {
        Route::get('wspolpraca-zgloszenia', [AdminCooperationRequestController::class, 'index'])->name('wspolpraca-zgloszenia.index');
        Route::get('wspolpraca-zgloszenia/{cooperationRequest}', [AdminCooperationRequestController::class, 'show'])->name('wspolpraca-zgloszenia.show');
        Route::delete('wspolpraca-zgloszenia/{cooperationRequest}', [AdminCooperationRequestController::class, 'destroy'])->name('wspolpraca-zgloszenia.destroy');
    });

    // Mapa pomocy — punkty wsparcia na interaktywnej mapie (szablon federation).
    Route::middleware(['module:help_map', 'module-access:help_map'])->group(function () {
        Route::resource('mapa-pomocy', AdminHelpPointController::class)->parameters(['mapa-pomocy' => 'helpPoint'])->except('show');
    });

    // Organizacje członkowskie — katalog i wizytówki (tylko szablon federation, patrz OrganizationController).
    Route::resource('organizacje', AdminOrganizationController::class)->parameters(['organizacje' => 'organization'])->except('show');
    Route::delete('organizacje/{organization}/zdjecia/{media}', [AdminOrganizationController::class, 'destroyPhoto'])->name('organizacje.photos.destroy');

    Route::middleware(['module:hero', 'module-access:hero'])->group(function () {
        Route::resource('hero', HeroSlideController::class)->parameters(['hero' => 'heroSlide'])->except('show');
        Route::patch('hero-misja-slajd', [HeroSlideController::class, 'updateMissionSlide'])->name('hero.mission-slide');
    });

    Route::middleware(['module:gallery', 'module-access:gallery'])->group(function () {
        Route::resource('galeria', GalleryImageController::class)->parameters(['galeria' => 'galleryImage'])->except('show');
    });

    Route::middleware(['module:projects', 'module-access:projects'])->group(function () {
        Route::resource('kategorie', AdminCategoryController::class)->parameters(['kategorie' => 'category'])->except('show');
        Route::resource('projekty', AdminProjectController::class)->parameters(['projekty' => 'project'])->except('show');
        Route::post('projekty/zbiorczo', [AdminProjectController::class, 'bulk'])->name('projekty.bulk');
    });

    Route::middleware(['module:news', 'module-access:news'])->group(function () {
        Route::get('newsy/eksport', [AdminNewsController::class, 'export'])->name('newsy.eksport');
        Route::get('newsy/sprawdz-duplikat', [AdminNewsController::class, 'checkDuplicate'])->name('newsy.sprawdz-duplikat');
        Route::resource('newsy', AdminNewsController::class)->parameters(['newsy' => 'news'])->except('show');
        Route::post('newsy/{news}/pliki', [AdminAttachmentController::class, 'storeForNews'])->name('newsy.pliki.store');
        Route::post('newsy/{news}/klonuj', [AdminNewsController::class, 'clone'])->name('newsy.klonuj');
        Route::post('newsy/zbiorczo', [AdminNewsController::class, 'bulk'])->name('newsy.bulk');
        Route::resource('kategorie-newsow', AdminNewsCategoryController::class)->parameters(['kategorie-newsow' => 'newsCategory'])->except('show');
        Route::get('tagi', [AdminTagController::class, 'index'])->name('tagi.index');
        Route::put('tagi/{tag}', [AdminTagController::class, 'update'])->name('tagi.update');
        Route::delete('tagi/{tag}', [AdminTagController::class, 'destroy'])->name('tagi.destroy');
    });

    Route::resource('podcasty', AdminPodcastController::class)
        ->parameters(['podcasty' => 'podcast'])
        ->except('show');
    Route::patch('podcasty/{id}/przywroc', [AdminPodcastController::class, 'restore'])->name('podcasty.restore');

    Route::middleware(['module:polls', 'module-access:polls'])->group(function () {
        Route::resource('ankiety', AdminPollController::class)->parameters(['ankiety' => 'poll'])->except('show');
        Route::post('ankiety/{poll}/reset-glosow', [AdminPollController::class, 'resetVotes'])->name('ankiety.reset-votes');
    });

    Route::middleware(['module:quick_actions', 'module-access:quick_actions'])->group(function () {
        Route::resource('szybkie-akcje', AdminQuickActionController::class)->parameters(['szybkie-akcje' => 'quickAction'])->except('show');
    });

    Route::middleware(['module:partners', 'module-access:partners'])->group(function () {
        Route::resource('partnerzy', AdminPartnerController::class)->parameters(['partnerzy' => 'partner'])->except('show');
    });

    Route::middleware(['module:materials', 'module-access:materials'])->group(function () {
        Route::resource('materialy-edukacyjne', AdminEducationalMaterialController::class)->parameters(['materialy-edukacyjne' => 'material'])->except('show');
        Route::post('materialy-edukacyjne/zbiorczo', [AdminEducationalMaterialController::class, 'bulk'])->name('materialy-edukacyjne.bulk');
        Route::get('zapisy-materialy', [AdminMaterialSubscriberController::class, 'index'])->name('zapisy-materialy.index');
        Route::get('zapisy-materialy/eksport', [AdminMaterialSubscriberController::class, 'export'])->name('zapisy-materialy.export');
        Route::delete('zapisy-materialy/{subscriber}', [AdminMaterialSubscriberController::class, 'destroy'])->name('zapisy-materialy.destroy');
    });

    Route::middleware(['module:volunteering', 'module-access:volunteering'])->group(function () {
        Route::resource('wolontariat', AdminVolunteerAdController::class)->parameters(['wolontariat' => 'wolontariat'])->except('show');
        Route::put('wolontariat/{wolontariat}/archiwizuj', [AdminVolunteerAdController::class, 'archive'])->name('wolontariat.archive');
        Route::put('wolontariat/{wolontariat}/przywroc', [AdminVolunteerAdController::class, 'restore'])->name('wolontariat.restore');
        Route::post('wolontariat/{wolontariat}/klonuj', [AdminVolunteerAdController::class, 'clone'])->name('wolontariat.klonuj');
        Route::post('wolontariat/zbiorczo', [AdminVolunteerAdController::class, 'bulk'])->name('wolontariat.bulk');
    });

    Route::middleware(['module:jobs', 'module-access:jobs'])->group(function () {
        Route::resource('praca', AdminJobOfferController::class)->parameters(['praca' => 'praca'])->except('show');
        Route::put('praca/{praca}/archiwizuj', [AdminJobOfferController::class, 'archive'])->name('praca.archive');
        Route::put('praca/{praca}/przywroc', [AdminJobOfferController::class, 'restore'])->name('praca.restore');
        Route::post('praca/{praca}/klonuj', [AdminJobOfferController::class, 'clone'])->name('praca.klonuj');
        Route::post('praca/zbiorczo', [AdminJobOfferController::class, 'bulk'])->name('praca.bulk');
        Route::post('praca/{praca}/pliki', [AdminAttachmentController::class, 'storeForJobOffer'])->name('praca.pliki.store');
    });

    Route::middleware(['module:events', 'module-access:events'])->group(function () {
        Route::get('wydarzenia/statystyki', [AdminEventController::class, 'stats'])->name('wydarzenia.statystyki');
        Route::resource('wydarzenia', AdminEventController::class)->parameters(['wydarzenia' => 'event'])->except('show');
        Route::post('wydarzenia/{event}/na-aktualnosc', [AdminEventController::class, 'toNews'])->name('wydarzenia.na-aktualnosc');
        Route::post('wydarzenia/{event}/na-landing', [AdminEventController::class, 'toLanding'])->name('wydarzenia.na-landing');
        Route::put('wydarzenia/{event}/archiwizuj', [AdminEventController::class, 'archive'])->name('wydarzenia.archive');
        Route::put('wydarzenia/{event}/przywroc', [AdminEventController::class, 'restore'])->name('wydarzenia.restore');
        Route::post('wydarzenia/{event}/klonuj', [AdminEventController::class, 'clone'])->name('wydarzenia.klonuj');
        Route::post('wydarzenia/zbiorczo', [AdminEventController::class, 'bulk'])->name('wydarzenia.bulk');

        Route::resource('prowadzacy', AdminFacilitatorController::class)
            ->parameters(['prowadzacy' => 'prowadzacy'])
            ->except('show');
    });

    Route::middleware(['module:faq', 'module-access:faq'])->group(function () {
        Route::resource('faq', AdminFaqController::class)->parameters(['faq' => 'faq'])->except('show');
    });

    Route::middleware(['module:reports', 'module-access:reports'])->group(function () {
        Route::resource('sprawozdania', AdminAnnualReportController::class)->parameters(['sprawozdania' => 'annualReport'])->except('show');
    });

    Route::middleware(['module:bip', 'module-access:bip'])->group(function () {
        Route::resource('bip-dokumenty', AdminBipDocumentController::class)->parameters(['bip-dokumenty' => 'bipDocument'])->except('show');
        Route::patch('bip-dokumenty/{bipDocument}/widocznosc', [AdminBipDocumentController::class, 'toggleVisibility'])->name('bip-dokumenty.widocznosc');
    });

    // Kolejka moderacji — dostęp pilnuje kontroler (canApproveContent).
    Route::get('zatwierdzanie', [AdminApprovalController::class, 'index'])->name('zatwierdzanie.index');
    Route::post('zatwierdzanie/{type}/{id}/zatwierdz', [AdminApprovalController::class, 'approve'])->name('zatwierdzanie.approve');
    Route::post('zatwierdzanie/{type}/{id}/odrzuc', [AdminApprovalController::class, 'reject'])->name('zatwierdzanie.reject');

    Route::middleware(['module:landing', 'module-access:landing'])->group(function () {
        Route::resource('lp', AdminLandingPageController::class)->parameters(['lp' => 'landing'])->except('show');
        Route::get('lp/{landing}/zapisy', [AdminLandingPageController::class, 'registrations'])->name('lp.registrations');
        Route::get('lp/{landing}/zapisy/eksport', [AdminLandingPageController::class, 'exportRegistrations'])->name('lp.registrations.export');
    });

    Route::middleware(['module:forms', 'module-access:forms'])->group(function () {
        Route::resource('formularze', AdminFormularzeController::class)
            ->parameters(['formularze' => 'formularz'])
            ->except('show');
        Route::get('formularze/{formularz}/zgloszenia', [AdminFormularzeController::class, 'zgloszenia'])->name('formularze.zgloszenia');
        Route::get('formularze/{formularz}/zgloszenia/eksport', [AdminFormularzeController::class, 'eksportZgloszen'])->name('formularze.zgloszenia.eksport');
        Route::delete('formularze/{formularz}/zgloszenia/{submission}', [AdminFormularzeController::class, 'destroyZgloszenie'])->name('formularze.zgloszenia.destroy');
    });

    // Blog „Wiem FEER" — gdy moduł włączony, dostępny dla każdego użytkownika
    // panelu (jak multimedia); wyłączenie modułu chowa całą sekcję.
    Route::get('multimedia', [MediaLibraryController::class, 'index'])->name('multimedia.index');
    Route::get('multimedia/obrazy', [MediaLibraryController::class, 'imagesJson'])->name('multimedia.images');
    Route::get('multimedia/audyt-alt', [MediaLibraryController::class, 'altAudit'])->name('multimedia.alt-audit');
    Route::get('multimedia/unsplash', [MediaLibraryController::class, 'unsplashSearch'])->name('multimedia.unsplash.search');
    Route::post('multimedia/unsplash', [MediaLibraryController::class, 'unsplashImport'])->name('multimedia.unsplash.import');
    Route::post('editor/importuj-docx', [DocxImportController::class, 'import'])->name('editor.docx.import');
    Route::post('multimedia/onedrive', [MediaLibraryController::class, 'oneDriveImport'])->name('multimedia.onedrive.import');
    Route::post('multimedia/upload-ajax', [MediaLibraryController::class, 'uploadAjax'])->name('multimedia.upload-ajax');
    Route::get('multimedia/eksport', [MediaLibraryController::class, 'export'])->name('multimedia.export');
    Route::post('multimedia/import', [MediaLibraryController::class, 'import'])->name('multimedia.import');
    Route::post('multimedia', [MediaLibraryController::class, 'store'])->name('multimedia.store');
    Route::post('multimedia/zbiorczo', [MediaLibraryController::class, 'bulk'])->name('multimedia.bulk');
    Route::post('multimedia/eksport-zaznaczonych', [MediaLibraryController::class, 'exportSelected'])->name('multimedia.export-selected');
    Route::put('multimedia/{media}/alt', [MediaLibraryController::class, 'updateAlt'])->name('multimedia.alt');
    Route::put('multimedia/{media}/autor', [MediaLibraryController::class, 'updateAuthor'])->name('multimedia.author');
    Route::put('multimedia/{media}/nazwa', [MediaLibraryController::class, 'rename'])->name('multimedia.rename');
    Route::put('multimedia/{media}/tagi', [MediaLibraryController::class, 'updateTags'])->name('multimedia.tags');
    Route::delete('multimedia/kosz', [MediaLibraryController::class, 'emptyArchive'])->name('multimedia.empty-archive');
    Route::put('multimedia/{media}/folder', [MediaLibraryController::class, 'move'])->name('multimedia.move');
    Route::put('multimedia/{media}/archiwizuj', [MediaLibraryController::class, 'archive'])->name('multimedia.archive');
    Route::put('multimedia/{media}/przywroc', [MediaLibraryController::class, 'restore'])->name('multimedia.restore');
    Route::delete('multimedia/{media}', [MediaLibraryController::class, 'destroy'])->name('multimedia.destroy');

    Route::post('multimedia/foldery', [MediaLibraryController::class, 'storeFolder'])->name('multimedia.foldery.store');
    Route::put('multimedia/foldery/{folder}', [MediaLibraryController::class, 'updateFolder'])->name('multimedia.foldery.update');
    Route::delete('multimedia/foldery/{folder}', [MediaLibraryController::class, 'destroyFolder'])->name('multimedia.foldery.destroy');

    Route::get('pliki/lista', [AdminAttachmentController::class, 'lista'])->name('pliki.lista');
    Route::delete('pliki/{attachment}', [AdminAttachmentController::class, 'destroy'])->name('pliki.destroy');

    Route::middleware('admin')->group(function () {
        // Układ strony głównej (drag-and-drop z frontendu).
        Route::post('homepage/section-order', [HomepageLayoutController::class, 'updateSectionOrder'])
            ->name('homepage.section-order');

        Route::get('ustawienia', [SiteSettingController::class, 'edit'])->name('ustawienia.edit');
        Route::put('ustawienia', [SiteSettingController::class, 'update'])->name('ustawienia.update');
        Route::post('ustawienia/test-poczty', [SiteSettingController::class, 'mailTest'])->name('ustawienia.mail-test');
        Route::post('ustawienia/token-awaryjny', [SiteSettingController::class, 'regenerateEmergencyToken'])->name('ustawienia.emergency-token');
        Route::post('ustawienia/strefa-nadpisz', [SiteSettingController::class, 'overwriteStrefa'])->name('strefa.overwrite');
        Route::post('ustawienia/prefix-panelu', [SiteSettingController::class, 'updateAdminPrefix'])->name('ustawienia.prefix');
        Route::get('ustawienia/dev', [SiteSettingController::class, 'dev'])->name('ustawienia.dev');
        Route::get('ustawienia/env', [SiteSettingController::class, 'envEdit'])->name('ustawienia.env');
        Route::post('ustawienia/env', [SiteSettingController::class, 'envUpdate'])->name('ustawienia.env.update');
        Route::post('push/wyslij', [SiteSettingController::class, 'sendPush'])->name('push.send');

        Route::get('moduly', [AdminModuleController::class, 'index'])->name('moduly.index');
        Route::post('moduly/{identifier}/install', [AdminModuleController::class, 'install'])->name('moduly.install');
        Route::post('moduly/{identifier}/activate', [AdminModuleController::class, 'activate'])->name('moduly.activate');
        Route::post('moduly/{identifier}/deactivate', [AdminModuleController::class, 'deactivate'])->name('moduly.deactivate');

        Route::get('zgloszenia-spotkania', [AdminMeetingSignupController::class, 'index'])->name('zgloszenia-spotkania.index');
        Route::get('zgloszenia-spotkania/eksport', [AdminMeetingSignupController::class, 'export'])->name('zgloszenia-spotkania.export');
        Route::delete('zgloszenia-spotkania/{signup}', [AdminMeetingSignupController::class, 'destroy'])->name('zgloszenia-spotkania.destroy');

        Route::get('zgloszenia-barier', [AdminAccessibilityReportController::class, 'index'])->name('zgloszenia-barier.index');
        Route::get('zgloszenia-barier/eksport', [AdminAccessibilityReportController::class, 'export'])->name('zgloszenia-barier.export');
        Route::delete('zgloszenia-barier/{report}', [AdminAccessibilityReportController::class, 'destroy'])->name('zgloszenia-barier.destroy');

        Route::get('wiadomosci-kontaktowe', [AdminContactMessageController::class, 'index'])->name('wiadomosci-kontaktowe.index');
        Route::get('wiadomosci-kontaktowe/{contactMessage}', [AdminContactMessageController::class, 'show'])->name('wiadomosci-kontaktowe.show');
        Route::post('wiadomosci-kontaktowe/{contactMessage}/replied', [AdminContactMessageController::class, 'markReplied'])->name('wiadomosci-kontaktowe.replied');
        Route::delete('wiadomosci-kontaktowe/{contactMessage}', [AdminContactMessageController::class, 'destroy'])->name('wiadomosci-kontaktowe.destroy');
        Route::post('ustawienia/mail/test', [AdminContactMessageController::class, 'mailTest'])->name('ustawienia.mail-test');

        Route::get('newsletter', [AdminNewsletterController::class, 'edit'])->name('newsletter.edit');
        Route::put('newsletter', [AdminNewsletterController::class, 'update'])->name('newsletter.update');

        Route::get('subskrybenci', [AdminSubscriberController::class, 'index'])->name('subskrybenci.index');
        Route::get('subskrybenci/eksport', [AdminSubscriberController::class, 'export'])->name('subskrybenci.export');
        Route::delete('subskrybenci/{subscriber}', [AdminSubscriberController::class, 'destroy'])->name('subskrybenci.destroy');

        Route::resource('kampanie', AdminCampaignController::class)->parameters(['kampanie' => 'campaign'])->except('show');
        Route::post('kampanie/zbiorczo', [AdminCampaignController::class, 'bulk'])->name('kampanie.bulk');

        Route::resource('pozycje-menu', NavItemController::class)->parameters(['pozycje-menu' => 'navItem'])->except('show');
        Route::patch('pozycje-menu/{navItem}/przenies', [NavItemController::class, 'move'])->name('pozycje-menu.przenies');
        Route::post('pozycje-menu/kolejnosc', [NavItemController::class, 'reorder'])->name('pozycje-menu.reorder');

        Route::resource('grupy', AdminUserGroupController::class)->parameters(['grupy' => 'group'])->except('show');

        // Przenośność treści (eksport/import) między instalacjami.
        Route::get('tresc', [AdminContentPortabilityController::class, 'index'])->name('tresc.index');
        Route::get('tresc/eksport', [AdminContentPortabilityController::class, 'export'])->name('tresc.export');
        Route::post('tresc/import', [AdminContentPortabilityController::class, 'import'])->name('tresc.import');

        // Przekierowania 301.
        Route::get('przekierowania', [AdminRedirectController::class, 'index'])->name('przekierowania.index');
        Route::post('przekierowania', [AdminRedirectController::class, 'store'])->name('przekierowania.store');
        Route::put('przekierowania/{przekierowanie}', [AdminRedirectController::class, 'update'])->name('przekierowania.update');
        Route::delete('przekierowania/{przekierowanie}', [AdminRedirectController::class, 'destroy'])->name('przekierowania.destroy');
        Route::get('przekierowania-eksport', [AdminRedirectController::class, 'export'])->name('przekierowania.export');
        Route::post('przekierowania-import', [AdminRedirectController::class, 'import'])->name('przekierowania.import');

        // ETR — wersja łatwa do czytania (polimorficzna, dotyczy newsów i podstron).
        Route::put('etr/{type}/{id}', [AdminEtrController::class, 'update'])->name('etr.update');
        Route::delete('etr/{type}/{id}', [AdminEtrController::class, 'destroy'])->name('etr.destroy');

        // Bannery i strefy wyświetlania.
        Route::resource('banery', AdminBannerController::class)
            ->parameters(['banery' => 'banner'])
            ->except('show');
        Route::post('banery/zbiorczo', [AdminBannerController::class, 'bulk'])->name('banery.bulk');
        Route::post('banery/{banner}/toggle', [AdminBannerController::class, 'toggle'])
            ->name('banery.toggle');
        Route::resource('strefy-bannerow', AdminBannerZoneController::class)
            ->parameters(['strefy-bannerow' => 'strefaBanneru'])
            ->except('show');
    });

    Route::middleware('admin')->group(function () {
        Route::resource('uzytkownicy', AdminUserController::class)->parameters(['uzytkownicy' => 'user'])->except('show');
        Route::delete('uzytkownicy/{user}/microsoft', [AdminUserController::class, 'unlinkMicrosoft'])->name('uzytkownicy.microsoft.unlink');

        Route::resource('zaproszenia-strefy', AdminMemberInvitationController::class)
            ->parameters(['zaproszenia-strefy' => 'zaproszenieStrefy'])
            ->only(['index', 'create', 'store', 'destroy']);

        Route::get('health', [AdminHealthCheckController::class, 'index'])->name('health.index');
        Route::get('cache', [AdminCacheController::class, 'index'])->name('cache.index');
        Route::put('cache', [AdminCacheController::class, 'update'])->name('cache.update');
        Route::post('cache/{group}/flush', [AdminCacheController::class, 'flush'])->name('cache.flush');
        Route::post('cache/flush-all', [AdminCacheController::class, 'flushAll'])->name('cache.flush-all');

        Route::get('dziennik', [AdminActivityController::class, 'index'])->name('dziennik.index');
        Route::get('martwe-linki', [AdminLinkCheckController::class, 'index'])->name('martwe-linki.index');
        Route::post('martwe-linki/skanuj', [AdminLinkCheckController::class, 'scan'])->name('martwe-linki.scan');
        Route::get('skaner-wcag', [AdminWcagScanController::class, 'index'])->name('wcag-scans.index');
        Route::post('skaner-wcag/skanuj', [AdminWcagScanController::class, 'scan'])->name('wcag-scans.scan');
        Route::get('skaner-wcag/{wcagScan}', [AdminWcagScanController::class, 'show'])->name('wcag-scans.show');
        Route::delete('skaner-wcag/{wcagScan}', [AdminWcagScanController::class, 'destroy'])->name('wcag-scans.destroy');
    });

    Route::middleware('admin')->group(function () {
        Route::get('szablony-maili', [AdminMailTemplateController::class, 'index'])->name('mail-templates.index');
        Route::get('szablony-maili/{mailTemplate}/edytuj', [AdminMailTemplateController::class, 'edit'])->name('mail-templates.edit');
        Route::put('szablony-maili/{mailTemplate}', [AdminMailTemplateController::class, 'update'])->name('mail-templates.update');
    });

    // Dokumentacja techniczna (tylko admin).
    Route::get('dokumentacja/{plik?}', function (string $plik = 'controllers') {
        $allowed = ['controllers', 'deployment'];
        $plik = in_array($plik, $allowed, true) ? $plik : 'controllers';
        $path = base_path("docs/{$plik}.html");
        abort_unless(file_exists($path), 404);
        return response()->file($path, ['Content-Type' => 'text/html; charset=utf-8']);
    })->middleware('admin')->name('dokumentacja');
});

// Subskrypcje Web Push — rejestracja i wyrejestrowanie.
Route::post('/push/subscribe',   [\App\Http\Controllers\PushController::class, 'subscribe'])->name('push.subscribe');
Route::post('/push/unsubscribe', [\App\Http\Controllers\PushController::class, 'unsubscribe'])->name('push.unsubscribe');

// Manifest PWA — dynamicznie generowany z ustawień serwisu.
Route::get('/manifest.webmanifest', function () {
    $s = App\Models\SiteSetting::current();
    return response()
        ->view('pwa.manifest', ['settings' => $s])
        ->header('Content-Type', 'application/manifest+json');
})->name('pwa.manifest');

require __DIR__.'/auth.php';

// Przekierowanie ze starego adresu /strefa na nowy slug strony strefy.
// Ścieżki MS OAuth (/strefa/logowanie itd.) działają niezależnie — ten redirect
// dotyczy tylko dokładnego GET /strefa, nie podścieżek.
Route::redirect('/strefa', '/strefa-wspolpracownika-feer', 301);

// Strefa wewnętrzna: osobne logowanie współpracowników przez Microsoft 365
// (guard „member"), niezależne od logowania do panelu.
Route::prefix('strefa')->group(function () {
    Route::get('logowanie', [MemberMicrosoftAuthController::class, 'create'])->name('member.login');
    Route::get('microsoft/redirect', [MemberMicrosoftAuthController::class, 'redirect'])->name('member.microsoft.redirect');
    Route::get('microsoft/callback', [MemberMicrosoftAuthController::class, 'callback'])->name('member.microsoft.callback');
    Route::post('wyloguj', [MemberMicrosoftAuthController::class, 'destroy'])->name('member.logout');

    // Zaproszenia indywidualne (magic link lub przekierowanie do MS365).
    Route::get('zaproszenie/{token}',             [MemberInvitationController::class, 'show'])->name('member.zaproszenie.show');
    Route::post('zaproszenie/{token}/magic',      [MemberInvitationController::class, 'magic'])->name('member.zaproszenie.magic');
    Route::get('zaproszenie/{token}/microsoft',   [MemberInvitationController::class, 'redirectToMicrosoft'])->name('member.zaproszenie.microsoft');
});

// Panel zarządzania rezerwacjami/terminami — dostęp przez Microsoft 365 (guard „member").
Route::prefix('rezerwacje')->name('rezerwacje.')->group(function () {
    Route::get('/',                                    [\App\Http\Controllers\ReserwacjeController::class, 'index'])->name('index');
    Route::post('/termin',                             [\App\Http\Controllers\ReserwacjeController::class, 'storeTermin'])->name('termin.store');
    Route::post('/termin/{index}',                     [\App\Http\Controllers\ReserwacjeController::class, 'destroyTermin'])->name('termin.destroy');
    Route::post('/powiadom',                           [\App\Http\Controllers\ReserwacjeController::class, 'notify'])->name('notify');
    Route::post('/zgloszenie/{signup}/potwierdz',      [\App\Http\Controllers\ReserwacjeController::class, 'confirmSignup'])->name('signup.confirm');
    Route::post('/zgloszenie/{signup}/usun',           [\App\Http\Controllers\ReserwacjeController::class, 'destroySignup'])->name('signup.destroy');
    Route::get('/eksport',                             [\App\Http\Controllers\ReserwacjeController::class, 'export'])->name('export');
});

// Odblokowanie strony wewnętrznej hasłem (przed catch-all, dwuczłonowa ścieżka).
Route::post('/{page:slug}/odblokuj', [PageController::class, 'unlock'])->name('page.unlock')->middleware('module:pages');

// Logowanie do strony z zasobami marki (login + indywidualne hasło).
Route::get('/{page:slug}/logowanie', [PageController::class, 'brandLogin'])->name('page.brand-login')->middleware('module:pages');
Route::post('/{page:slug}/logowanie', [PageController::class, 'brandLoginPost'])->name('page.brand-login.post')->middleware(['module:pages', 'throttle:10,1']);
Route::post('/{page:slug}/wyloguj', [PageController::class, 'brandLogout'])->name('page.brand-logout')->middleware('module:pages');

// Strona osoby: /{org-slug}/osoba/{person-slug} (musi być przed catch-all).
Route::get('/{parentSlug}/osoba/{personSlug}', [PageController::class, 'showPerson'])
    ->name('page.person')
    ->middleware('module:pages');

// Formularz współpracy (musi być przed catch-all /{page:slug}).
Route::get('/{page:slug}/formularz', [CooperationFormController::class, 'show'])->name('cooperation.form.show')->middleware(['module:pages', 'module:cooperation']);
Route::post('/{page:slug}/formularz', [CooperationFormController::class, 'store'])->name('cooperation.form.store')->middleware(['module:pages', 'module:cooperation', 'throttle:5,10']);

// Kreator formularzy — publiczne wyświetlenie i zapis zgłoszenia.
Route::middleware('module:forms')->group(function () {
    Route::get('/formularz/{formularz:slug}', [FormController::class, 'show'])->name('formularz.show');
    Route::post('/formularz/{formularz:slug}', [FormController::class, 'store'])->name('formularz.store')->middleware('throttle:5,10');
});

// Catch-all for top-level pages (e.g. /fundacja instead of /strona/fundacja).
// Kept last so every more specific route above always wins; a page whose
// slug collides with one of those is unreachable here, which is why
// AdminPageController's slug generator treats reserved words as taken.
Route::get('/{page:slug}', [PageController::class, 'show'])->name('page.show')->middleware('module:pages');
