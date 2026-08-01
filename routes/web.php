<?php

use App\Http\Controllers\Auth\MemberMicrosoftAuthController;
use App\Http\Controllers\Admin\ActivityController as AdminActivityController;
use App\Http\Controllers\Admin\AnnualReportController as AdminAnnualReportController;
use App\Http\Controllers\Admin\ApprovalController as AdminApprovalController;
use App\Http\Controllers\Admin\CalendarController as AdminCalendarController;
use App\Http\Controllers\Admin\EditLockController as AdminEditLockController;
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
use App\Http\Controllers\Admin\TaskController as AdminTaskController;
use App\Http\Controllers\Admin\VolunteerAdController as AdminVolunteerAdController;
use App\Http\Controllers\AccessibilityController;
use App\Http\Controllers\AccessibilityReportController;
use App\Http\Controllers\Admin\BlogArticleController as AdminBlogArticleController;
use App\Http\Controllers\Admin\BlogCommentController as AdminBlogCommentController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BlogCommentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EducationalMaterialController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MaterialSubscriberController;
use App\Http\Controllers\MeetingSignupController;
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
use App\Http\Controllers\BannerTrackingController;
use App\Http\Controllers\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Admin\BannerZoneController as AdminBannerZoneController;
use App\Http\Controllers\Admin\EtrController as AdminEtrController;
use App\Http\Controllers\EtrController;
use App\Http\Controllers\VolunteerController;
use Illuminate\Support\Facades\Route;

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

Route::middleware('module:news')->group(function () {
    Route::get('/aktualnosci', [NewsController::class, 'index'])->name('news.index');
    Route::get('/aktualnosci/{news:slug}', [NewsController::class, 'show'])->name('news.show');
});

Route::post('/ankieta/{poll}/glosuj', [PollVoteController::class, 'store'])->name('polls.vote')->middleware('module:polls');

Route::middleware('module:materials')->group(function () {
    Route::get('/materialy', [EducationalMaterialController::class, 'index'])->name('materials.index');
    Route::post('/materialy/zapis', [MaterialSubscriberController::class, 'store'])->name('materials.subscribe');
});

// Blog „Wiem FEER" — artykuły z komentarzami (osobna baza SQLite).
Route::middleware('module:blog')->group(function () {
    Route::get('/wiem-feer', [BlogController::class, 'index'])->name('blog.index');
    Route::get('/wiem-feer/{article:slug}', [BlogController::class, 'show'])->name('blog.show');
    Route::post('/wiem-feer/{article:slug}/komentarz', [BlogCommentController::class, 'store'])
        ->name('blog.comments.store')->middleware('throttle:5,1');
});

Route::middleware('module:volunteering')->group(function () {
    Route::get('/wolontariat', [VolunteerController::class, 'index'])->name('volunteer.index');
    Route::get('/wolontariat/{ad:slug}', [VolunteerController::class, 'show'])->name('volunteer.show');
});

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

Route::get('/wsparcie', [SupportController::class, 'index'])->name('support.show')->middleware('module:support');

// Sztywne skróty: /bip (strona-pośrednik z informacją), /instagram i /fb (przekierowania).
Route::get('/bip', [ShortcutController::class, 'bip'])->name('bip');
Route::get('/instagram', [ShortcutController::class, 'instagram'])->name('shortcut.instagram');
Route::get('/fb', [ShortcutController::class, 'facebook'])->name('shortcut.fb');
Route::get('/facebook', [ShortcutController::class, 'facebook'])->name('shortcut.facebook');

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
        Route::resource('podstrony', AdminPageController::class)->parameters(['podstrony' => 'page']);
        Route::post('podstrony/{page}/pliki', [AdminAttachmentController::class, 'storeForPage'])->name('podstrony.pliki.store');
        Route::post('podstrony/{page}/zdjecia', [AdminPageImageController::class, 'store'])->name('podstrony.zdjecia.store');
        Route::put('podstrony/zdjecia/{image}', [AdminPageImageController::class, 'update'])->name('podstrony.zdjecia.update');
        Route::delete('podstrony/zdjecia/{image}', [AdminPageImageController::class, 'destroy'])->name('podstrony.zdjecia.destroy');
        Route::post('podstrony/zbiorczo', [AdminPageController::class, 'bulk'])->name('podstrony.bulk');
        Route::post('podstrony/{page}/klonuj', [AdminPageController::class, 'clone'])->name('podstrony.clone');
        Route::patch('podstrony/{page}/kolejnosc', [AdminPageController::class, 'updateOrder'])->name('podstrony.kolejnosc');
        Route::patch('podstrony/{page}/widocznosc', [AdminPageController::class, 'toggleVisibility'])->name('podstrony.widocznosc');
        Route::patch('podstrony/{page}/wylacz', [AdminPageController::class, 'toggleDisabled'])->name('podstrony.wylacz');
        Route::patch('podstrony/{page}/wyroznienie', [AdminPageController::class, 'toggleFeatured'])->name('podstrony.wyroznienie');

        // Oś czasu (historia) strony „O organizacji" jako osobna pozycja w menu.
        Route::get('os-czasu', [AdminTimelineController::class, 'edit'])->name('os-czasu.edit');
        Route::put('os-czasu/{page}', [AdminTimelineController::class, 'update'])->name('os-czasu.update');
    });

    Route::middleware(['module:hero', 'module-access:hero'])->group(function () {
        Route::resource('hero', HeroSlideController::class)->parameters(['hero' => 'heroSlide'])->except('show');
    });

    Route::middleware(['module:gallery', 'module-access:gallery'])->group(function () {
        Route::resource('galeria', GalleryImageController::class)->parameters(['galeria' => 'galleryImage'])->except('show');
    });

    Route::middleware(['module:projects', 'module-access:projects'])->group(function () {
        Route::resource('kategorie', AdminCategoryController::class)->parameters(['kategorie' => 'category'])->except('show');
        Route::resource('projekty', AdminProjectController::class)->parameters(['projekty' => 'project'])->except('show');
    });

    Route::middleware(['module:news', 'module-access:news'])->group(function () {
        Route::resource('newsy', AdminNewsController::class)->parameters(['newsy' => 'news'])->except('show');
        Route::post('newsy/{news}/pliki', [AdminAttachmentController::class, 'storeForNews'])->name('newsy.pliki.store');
        Route::post('newsy/{news}/klonuj', [AdminNewsController::class, 'clone'])->name('newsy.klonuj');
        Route::post('newsy/zbiorczo', [AdminNewsController::class, 'bulk'])->name('newsy.bulk');
        Route::resource('kategorie-newsow', AdminNewsCategoryController::class)->parameters(['kategorie-newsow' => 'newsCategory'])->except('show');
    });

    Route::middleware(['module:polls', 'module-access:polls'])->group(function () {
        Route::resource('ankiety', AdminPollController::class)->parameters(['ankiety' => 'poll'])->except('show');
    });

    Route::middleware(['module:quick_actions', 'module-access:quick_actions'])->group(function () {
        Route::resource('szybkie-akcje', AdminQuickActionController::class)->parameters(['szybkie-akcje' => 'quickAction'])->except('show');
    });

    Route::middleware(['module:partners', 'module-access:partners'])->group(function () {
        Route::resource('partnerzy', AdminPartnerController::class)->parameters(['partnerzy' => 'partner'])->except('show');
    });

    Route::middleware(['module:materials', 'module-access:materials'])->group(function () {
        Route::resource('materialy-edukacyjne', AdminEducationalMaterialController::class)->parameters(['materialy-edukacyjne' => 'material'])->except('show');
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

    Route::middleware(['module:events', 'module-access:events'])->group(function () {
        Route::resource('wydarzenia', AdminEventController::class)->parameters(['wydarzenia' => 'event'])->except('show');
        Route::post('wydarzenia/{event}/na-aktualnosc', [AdminEventController::class, 'toNews'])->name('wydarzenia.na-aktualnosc');
        Route::post('wydarzenia/{event}/na-landing', [AdminEventController::class, 'toLanding'])->name('wydarzenia.na-landing');
        Route::put('wydarzenia/{event}/archiwizuj', [AdminEventController::class, 'archive'])->name('wydarzenia.archive');
        Route::put('wydarzenia/{event}/przywroc', [AdminEventController::class, 'restore'])->name('wydarzenia.restore');
        Route::post('wydarzenia/{event}/klonuj', [AdminEventController::class, 'clone'])->name('wydarzenia.klonuj');
        Route::post('wydarzenia/zbiorczo', [AdminEventController::class, 'bulk'])->name('wydarzenia.bulk');
    });

    Route::middleware(['module:faq', 'module-access:faq'])->group(function () {
        Route::resource('faq', AdminFaqController::class)->parameters(['faq' => 'faq'])->except('show');
    });

    Route::middleware(['module:reports', 'module-access:reports'])->group(function () {
        Route::resource('sprawozdania', AdminAnnualReportController::class)->parameters(['sprawozdania' => 'annualReport'])->except('show');
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

    // Blog „Wiem FEER" — gdy moduł włączony, dostępny dla każdego użytkownika
    // panelu (jak multimedia); wyłączenie modułu chowa całą sekcję.
    Route::middleware('module:blog')->group(function () {
        Route::resource('wiem-feer', AdminBlogArticleController::class)->parameters(['wiem-feer' => 'article'])->except('show');
        Route::patch('wiem-feer/{article}/wylacz', [AdminBlogArticleController::class, 'toggleDisabled'])->name('wiem-feer.wylacz');
        Route::post('wiem-feer/{article}/klonuj', [AdminBlogArticleController::class, 'clone'])->name('wiem-feer.klonuj');
        Route::get('komentarze-bloga', [AdminBlogCommentController::class, 'index'])->name('komentarze-bloga.index');
        Route::patch('komentarze-bloga/{comment}/zatwierdz', [AdminBlogCommentController::class, 'approve'])->name('komentarze-bloga.approve');
        Route::delete('komentarze-bloga/{comment}', [AdminBlogCommentController::class, 'destroy'])->name('komentarze-bloga.destroy');
    });

    Route::get('multimedia', [MediaLibraryController::class, 'index'])->name('multimedia.index');
    Route::get('multimedia/obrazy', [MediaLibraryController::class, 'imagesJson'])->name('multimedia.images');
    Route::get('multimedia/audyt-alt', [MediaLibraryController::class, 'altAudit'])->name('multimedia.alt-audit');
    Route::get('multimedia/unsplash', [MediaLibraryController::class, 'unsplashSearch'])->name('multimedia.unsplash.search');
    Route::post('multimedia/unsplash', [MediaLibraryController::class, 'unsplashImport'])->name('multimedia.unsplash.import');
    Route::post('multimedia/onedrive', [MediaLibraryController::class, 'oneDriveImport'])->name('multimedia.onedrive.import');
    Route::post('multimedia/upload-ajax', [MediaLibraryController::class, 'uploadAjax'])->name('multimedia.upload-ajax');
    Route::get('multimedia/eksport', [MediaLibraryController::class, 'export'])->name('multimedia.export');
    Route::post('multimedia/import', [MediaLibraryController::class, 'import'])->name('multimedia.import');
    Route::post('multimedia', [MediaLibraryController::class, 'store'])->name('multimedia.store');
    Route::post('multimedia/zbiorczo', [MediaLibraryController::class, 'bulk'])->name('multimedia.bulk');
    Route::post('multimedia/eksport-zaznaczonych', [MediaLibraryController::class, 'exportSelected'])->name('multimedia.export-selected');
    Route::put('multimedia/{media}/alt', [MediaLibraryController::class, 'updateAlt'])->name('multimedia.alt');
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
        Route::get('ustawienia', [SiteSettingController::class, 'edit'])->name('ustawienia.edit');
        Route::put('ustawienia', [SiteSettingController::class, 'update'])->name('ustawienia.update');
        Route::post('ustawienia/test-poczty', [SiteSettingController::class, 'mailTest'])->name('ustawienia.mail-test');
        Route::post('ustawienia/strefa-nadpisz', [SiteSettingController::class, 'overwriteStrefa'])->name('strefa.overwrite');
        Route::post('ustawienia/prefix-panelu', [SiteSettingController::class, 'updateAdminPrefix'])->name('ustawienia.prefix');

        Route::get('zgloszenia-spotkania', [AdminMeetingSignupController::class, 'index'])->name('zgloszenia-spotkania.index');
        Route::get('zgloszenia-spotkania/eksport', [AdminMeetingSignupController::class, 'export'])->name('zgloszenia-spotkania.export');
        Route::delete('zgloszenia-spotkania/{signup}', [AdminMeetingSignupController::class, 'destroy'])->name('zgloszenia-spotkania.destroy');

        Route::get('zgloszenia-barier', [AdminAccessibilityReportController::class, 'index'])->name('zgloszenia-barier.index');
        Route::get('zgloszenia-barier/eksport', [AdminAccessibilityReportController::class, 'export'])->name('zgloszenia-barier.export');
        Route::delete('zgloszenia-barier/{report}', [AdminAccessibilityReportController::class, 'destroy'])->name('zgloszenia-barier.destroy');

        Route::get('newsletter', [AdminNewsletterController::class, 'edit'])->name('newsletter.edit');
        Route::put('newsletter', [AdminNewsletterController::class, 'update'])->name('newsletter.update');

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
        Route::post('banery/{banner}/toggle', [AdminBannerController::class, 'toggle'])
            ->name('banery.toggle');
        Route::resource('strefy-bannerow', AdminBannerZoneController::class)
            ->parameters(['strefy-bannerow' => 'strefaBanneru'])
            ->except('show');
    });

    Route::middleware('admin')->group(function () {
        Route::resource('uzytkownicy', AdminUserController::class)->parameters(['uzytkownicy' => 'user'])->except('show');
        Route::delete('uzytkownicy/{user}/microsoft', [AdminUserController::class, 'unlinkMicrosoft'])->name('uzytkownicy.microsoft.unlink');

        Route::get('dziennik', [AdminActivityController::class, 'index'])->name('dziennik.index');
        Route::get('martwe-linki', [AdminLinkCheckController::class, 'index'])->name('martwe-linki.index');
        Route::post('martwe-linki/skanuj', [AdminLinkCheckController::class, 'scan'])->name('martwe-linki.scan');
    });
});

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

// Catch-all for top-level pages (e.g. /fundacja instead of /strona/fundacja).
// Kept last so every more specific route above always wins; a page whose
// slug collides with one of those is unreachable here, which is why
// AdminPageController's slug generator treats reserved words as taken.
Route::get('/{page:slug}', [PageController::class, 'show'])->name('page.show')->middleware('module:pages');
