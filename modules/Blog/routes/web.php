<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\BlogController;
use Modules\Blog\Http\Controllers\BlogCommentController;
use Modules\Blog\Http\Controllers\Admin\BlogArticleController as AdminBlogArticleController;
use Modules\Blog\Http\Controllers\Admin\BlogAuthorController as AdminBlogAuthorController;
use Modules\Blog\Http\Controllers\Admin\BlogCategoryController as AdminBlogCategoryController;
use Modules\Blog\Http\Controllers\Admin\BlogCommentController as AdminBlogCommentController;

/*
 * Trasy modułów ładowane przez loadRoutesFrom() nie dostają grupy `web` z bootstrap/app.php —
 * bez niej nie ma sesji, CSRF ani logowania. Atrybut `module` sprawia, że
 * ModuleServiceProvider::prioritizeModuleRoutes() stawia je przed catch-allem stron
 * („/{page:slug}") i krótkimi trasami sub-witryn („/{siteSlug}/…"), więc /wiem zawsze trafia tu.
 */
Route::group(['middleware' => 'web', 'module' => 'blog'], function (): void {

$prefix = trim((string) config('blog.prefix', 'wiem'), '/');

// ── Publiczne (/wiem) — w oprawie witryny sieci „wiem", jeśli istnieje ─────────
Route::prefix($prefix)->middleware('blog.site')->group(function (): void {
    Route::get('/', [BlogController::class, 'index'])->name('blog.index');
    // Stałe adresy przed /{article:slug}, żeby nie zostały wzięte za slug wpisu.
    Route::get('/rss.xml', [BlogController::class, 'feed'])->name('blog.feed');
    Route::get('/autorzy', [BlogController::class, 'authors'])->name('blog.authors');
    Route::get('/autor/{author:slug}', [BlogController::class, 'author'])->name('blog.author');
    Route::get('/{article:slug}', [BlogController::class, 'show'])->name('blog.show');
    Route::post('/{article:slug}/komentarz', [BlogCommentController::class, 'store'])
        ->name('blog.comments.store')
        ->middleware('throttle:5,1');
});

// ── Dawne adresy → /wiem (301, z zachowaniem ścieżki i parametrów) ─────────────
foreach ((array) config('blog.legacy_prefixes', []) as $legacy) {
    $legacy = trim((string) $legacy, '/');
    if ($legacy === '' || $legacy === $prefix) {
        continue;
    }
    Route::get($legacy . '/{path?}', [BlogController::class, 'legacyRedirect'])
        ->where('path', '.*')
        ->name('blog.legacy.' . $legacy);
}

// ── Adminowe ──────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'verified', '2fa', 'module-access:blog'])
    ->prefix(config('app.admin_prefix', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        // Edytor Markdown: podgląd i konwersja HTML → Markdown (JSON, przed zasobem).
        Route::post('wiem-feer/podglad-markdown', [AdminBlogArticleController::class, 'previewMarkdown'])
            ->name('wiem-feer.podglad-markdown');
        Route::post('wiem-feer/html-na-markdown', [AdminBlogArticleController::class, 'convertToMarkdown'])
            ->name('wiem-feer.html-na-markdown');
        Route::post('wiem-feer/zbiorczo', [AdminBlogArticleController::class, 'bulk'])
            ->name('wiem-feer.zbiorczo');
        Route::resource('wiem-feer', AdminBlogArticleController::class)
            ->parameters(['wiem-feer' => 'article'])
            ->except('show');
        Route::patch('wiem-feer/{article}/wylacz', [AdminBlogArticleController::class, 'toggleDisabled'])
            ->name('wiem-feer.wylacz');
        Route::post('wiem-feer/{article}/klonuj', [AdminBlogArticleController::class, 'clone'])
            ->name('wiem-feer.klonuj');
        Route::resource('kategorie-bloga', AdminBlogCategoryController::class)
            ->parameters(['kategorie-bloga' => 'category'])
            ->only(['index', 'store', 'edit', 'update', 'destroy']);
        Route::resource('autorzy-bloga', AdminBlogAuthorController::class)
            ->parameters(['autorzy-bloga' => 'author'])
            ->except(['show', 'destroy']);
        Route::patch('autorzy-bloga/{author}/aktywnosc', [AdminBlogAuthorController::class, 'toggleActive'])
            ->name('autorzy-bloga.aktywnosc');
        Route::get('komentarze-bloga', [AdminBlogCommentController::class, 'index'])
            ->name('komentarze-bloga.index');
        Route::patch('komentarze-bloga/{comment}/zatwierdz', [AdminBlogCommentController::class, 'approve'])
            ->name('komentarze-bloga.approve');
        Route::post('komentarze-bloga/{comment}/odpowiedz', [AdminBlogCommentController::class, 'reply'])
            ->name('komentarze-bloga.reply');
        Route::delete('komentarze-bloga/{comment}', [AdminBlogCommentController::class, 'destroy'])
            ->name('komentarze-bloga.destroy');
    });

});
