<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\MicrosoftAuthController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\SuperAdminController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\TwoFactorSettingController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    // Logowanie głównego administratora certyfikatem klienta (.pfx) — niezależne
    // od zwykłego logowania hasłem/2FA.
    Route::get('super', [SuperAdminController::class, 'create'])
        ->name('super-admin.login');

    Route::post('super', [SuperAdminController::class, 'store'])
        ->middleware('throttle:5,1');

    Route::get('{token}', [AuthenticatedSessionController::class, 'createEmergency'])
        ->where('token', '[A-Za-z0-9]{24}')
        ->name('login.emergency');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('auth/microsoft/redirect', [MicrosoftAuthController::class, 'redirect'])
        ->name('auth.microsoft.redirect');

    Route::get('auth/microsoft/callback', [MicrosoftAuthController::class, 'callback'])
        ->name('auth.microsoft.callback');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');

    // Drugi składnik logowania hasłem (po weryfikacji hasła, przed pełnym zalogowaniem).
    Route::get('two-factor-challenge', [TwoFactorChallengeController::class, 'create'])
        ->name('two-factor.login');

    Route::post('two-factor-challenge', [TwoFactorChallengeController::class, 'store'])
        ->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    // Zarządzanie 2FA na stronie profilu.
    Route::post('two-factor/enable', [TwoFactorSettingController::class, 'enable'])->name('two-factor.enable');
    Route::post('two-factor/confirm', [TwoFactorSettingController::class, 'confirm'])->name('two-factor.confirm');
    Route::delete('two-factor', [TwoFactorSettingController::class, 'disable'])->name('two-factor.disable');
    Route::post('two-factor/recovery-codes', [TwoFactorSettingController::class, 'regenerateRecovery'])->name('two-factor.recovery');
    Route::post('two-factor/yubikey', [TwoFactorSettingController::class, 'addYubikey'])->name('two-factor.yubikey.add');
    Route::delete('two-factor/yubikey', [TwoFactorSettingController::class, 'removeYubikey'])->name('two-factor.yubikey.remove');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
