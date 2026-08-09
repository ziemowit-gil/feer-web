<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\BlogController;
use Modules\Blog\Http\Controllers\BlogCommentController;
use Modules\Blog\Http\Controllers\Admin\BlogArticleController as AdminBlogArticleController;
use Modules\Blog\Http\Controllers\Admin\BlogCommentController as AdminBlogCommentController;

// ── Publiczne ─────────────────────────────────────────────────────────────────
Route::get('/wiem-feer', [BlogController::class, 'index'])->name('blog.index');
Route::get('/wiem-feer/{article:slug}', [BlogController::class, 'show'])->name('blog.show');
Route::post('/wiem-feer/{article:slug}/komentarz', [BlogCommentController::class, 'store'])
    ->name('blog.comments.store')
    ->middleware('throttle:5,1');

// ── Adminowe ──────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'verified', '2fa'])
    ->prefix(config('app.admin_prefix', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::resource('wiem-feer', AdminBlogArticleController::class)
            ->parameters(['wiem-feer' => 'article'])
            ->except('show');
        Route::patch('wiem-feer/{article}/wylacz', [AdminBlogArticleController::class, 'toggleDisabled'])
            ->name('wiem-feer.wylacz');
        Route::post('wiem-feer/{article}/klonuj', [AdminBlogArticleController::class, 'clone'])
            ->name('wiem-feer.klonuj');
        Route::get('komentarze-bloga', [AdminBlogCommentController::class, 'index'])
            ->name('komentarze-bloga.index');
        Route::patch('komentarze-bloga/{comment}/zatwierdz', [AdminBlogCommentController::class, 'approve'])
            ->name('komentarze-bloga.approve');
        Route::delete('komentarze-bloga/{comment}', [AdminBlogCommentController::class, 'destroy'])
            ->name('komentarze-bloga.destroy');
    });
