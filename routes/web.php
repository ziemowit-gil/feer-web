<?php

use App\Http\Controllers\Admin\AnnualReportController as AdminAnnualReportController;
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
use App\Http\Controllers\VolunteerController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

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

Route::get('/kontakt', [ContactController::class, 'index'])->name('contact.show');
Route::post('/kontakt', [ContactController::class, 'store'])->name('contact.store')->middleware('throttle:5,1');
Route::post('/kontakt/przyjde', [MeetingSignupController::class, 'store'])->name('meeting.signup')->middleware('throttle:5,1');

Route::redirect('/dashboard', '/admin')->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::middleware(['module:pages', 'module-access:pages'])->group(function () {
        Route::resource('podstrony', AdminPageController::class)->parameters(['podstrony' => 'page']);
        Route::post('podstrony/{page}/pliki', [AdminAttachmentController::class, 'storeForPage'])->name('podstrony.pliki.store');
        Route::post('podstrony/{page}/zdjecia', [AdminPageImageController::class, 'store'])->name('podstrony.zdjecia.store');
        Route::put('podstrony/zdjecia/{image}', [AdminPageImageController::class, 'update'])->name('podstrony.zdjecia.update');
        Route::delete('podstrony/zdjecia/{image}', [AdminPageImageController::class, 'destroy'])->name('podstrony.zdjecia.destroy');
        Route::post('podstrony/{page}/klonuj', [AdminPageController::class, 'clone'])->name('podstrony.clone');
        Route::patch('podstrony/{page}/kolejnosc', [AdminPageController::class, 'updateOrder'])->name('podstrony.kolejnosc');
        Route::patch('podstrony/{page}/widocznosc', [AdminPageController::class, 'toggleVisibility'])->name('podstrony.widocznosc');
        Route::patch('podstrony/{page}/wylacz', [AdminPageController::class, 'toggleDisabled'])->name('podstrony.wylacz');

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
    });

    Route::middleware(['module:events', 'module-access:events'])->group(function () {
        Route::resource('wydarzenia', AdminEventController::class)->parameters(['wydarzenia' => 'event'])->except('show');
        Route::post('wydarzenia/{event}/na-aktualnosc', [AdminEventController::class, 'toNews'])->name('wydarzenia.na-aktualnosc');
        Route::post('wydarzenia/{event}/na-landing', [AdminEventController::class, 'toLanding'])->name('wydarzenia.na-landing');
    });

    Route::middleware(['module:faq', 'module-access:faq'])->group(function () {
        Route::resource('faq', AdminFaqController::class)->parameters(['faq' => 'faq'])->except('show');
    });

    Route::middleware(['module:reports', 'module-access:reports'])->group(function () {
        Route::resource('sprawozdania', AdminAnnualReportController::class)->parameters(['sprawozdania' => 'annualReport'])->except('show');
    });

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
        Route::get('komentarze-bloga', [AdminBlogCommentController::class, 'index'])->name('komentarze-bloga.index');
        Route::patch('komentarze-bloga/{comment}/zatwierdz', [AdminBlogCommentController::class, 'approve'])->name('komentarze-bloga.approve');
        Route::delete('komentarze-bloga/{comment}', [AdminBlogCommentController::class, 'destroy'])->name('komentarze-bloga.destroy');
    });

    Route::get('multimedia', [MediaLibraryController::class, 'index'])->name('multimedia.index');
    Route::get('multimedia/obrazy', [MediaLibraryController::class, 'imagesJson'])->name('multimedia.images');
    Route::get('multimedia/unsplash', [MediaLibraryController::class, 'unsplashSearch'])->name('multimedia.unsplash.search');
    Route::post('multimedia/unsplash', [MediaLibraryController::class, 'unsplashImport'])->name('multimedia.unsplash.import');
    Route::get('multimedia/eksport', [MediaLibraryController::class, 'export'])->name('multimedia.export');
    Route::post('multimedia/import', [MediaLibraryController::class, 'import'])->name('multimedia.import');
    Route::post('multimedia', [MediaLibraryController::class, 'store'])->name('multimedia.store');
    Route::post('multimedia/zbiorczo', [MediaLibraryController::class, 'bulk'])->name('multimedia.bulk');
    Route::post('multimedia/eksport-zaznaczonych', [MediaLibraryController::class, 'exportSelected'])->name('multimedia.export-selected');
    Route::put('multimedia/{media}/folder', [MediaLibraryController::class, 'move'])->name('multimedia.move');
    Route::put('multimedia/{media}/archiwizuj', [MediaLibraryController::class, 'archive'])->name('multimedia.archive');
    Route::put('multimedia/{media}/przywroc', [MediaLibraryController::class, 'restore'])->name('multimedia.restore');
    Route::delete('multimedia/{media}', [MediaLibraryController::class, 'destroy'])->name('multimedia.destroy');

    Route::post('multimedia/foldery', [MediaLibraryController::class, 'storeFolder'])->name('multimedia.foldery.store');
    Route::put('multimedia/foldery/{folder}', [MediaLibraryController::class, 'updateFolder'])->name('multimedia.foldery.update');
    Route::delete('multimedia/foldery/{folder}', [MediaLibraryController::class, 'destroyFolder'])->name('multimedia.foldery.destroy');

    Route::delete('pliki/{attachment}', [AdminAttachmentController::class, 'destroy'])->name('pliki.destroy');

    Route::middleware('admin')->group(function () {
        Route::get('ustawienia', [SiteSettingController::class, 'edit'])->name('ustawienia.edit');
        Route::put('ustawienia', [SiteSettingController::class, 'update'])->name('ustawienia.update');
        Route::post('ustawienia/test-poczty', [SiteSettingController::class, 'mailTest'])->name('ustawienia.mail-test');

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
    });

    Route::middleware('admin')->group(function () {
        Route::resource('uzytkownicy', AdminUserController::class)->parameters(['uzytkownicy' => 'user'])->except('show');
        Route::delete('uzytkownicy/{user}/microsoft', [AdminUserController::class, 'unlinkMicrosoft'])->name('uzytkownicy.microsoft.unlink');
    });
});

require __DIR__.'/auth.php';

// Odblokowanie strony wewnętrznej hasłem (przed catch-all, dwuczłonowa ścieżka).
Route::post('/{page:slug}/odblokuj', [PageController::class, 'unlock'])->name('page.unlock')->middleware('module:pages');

// Catch-all for top-level pages (e.g. /fundacja instead of /strona/fundacja).
// Kept last so every more specific route above always wins; a page whose
// slug collides with one of those is unreachable here, which is why
// AdminPageController's slug generator treats reserved words as taken.
Route::get('/{page:slug}', [PageController::class, 'show'])->name('page.show')->middleware('module:pages');
