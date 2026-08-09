<?php

namespace App\Support;

use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\TaskController;
use App\Models\AccessibilityReport;
use Modules\Blog\Models\BlogComment;
use App\Models\MeetingSignup;
use App\Modules\ModuleManager;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Centrum powiadomień panelu: agreguje rzeczy wymagające uwagi redakcji,
 * z poszanowaniem uprawnień. Część pozycji jest „do obsłużenia" (znika po
 * załatwieniu), część „nowa od ostatniego zajrzenia" (znacznik na użytkowniku).
 */
class AdminNotifications
{
    /** @return array<int, array{label: string, count: int, url: string, icon: string}> */
    public static function items(User $user): array
    {
        $seen = $user->notifications_seen_at ?? Carbon::createFromTimestamp(0);
        $items = [];

        if ($user->canApproveContent()) {
            self::push($items, 'Treści do zatwierdzenia', ApprovalController::pendingCount(),
                route('admin.zatwierdzanie.index'), 'fa-clipboard-check');
        }

        self::push($items, 'Moje zadania', TaskController::myPendingCount($user->id),
            route('admin.zadania.index', ['moje' => 1]), 'fa-list-check');

        if ($user->canAccessModule('blog') && app(ModuleManager::class)->isActive('blog')) {
            self::push($items, 'Komentarze do moderacji', BlogComment::pending()->count(),
                route('admin.komentarze-bloga.index'), 'fa-comments');
        }

        if ($user->isAdmin()) {
            self::push($items, 'Nowe zgłoszenia barier', AccessibilityReport::where('created_at', '>', $seen)->count(),
                route('admin.zgloszenia-barier.index'), 'fa-universal-access');

            self::push($items, 'Nowe zapisy na spotkania', MeetingSignup::where('created_at', '>', $seen)->count(),
                route('admin.zgloszenia-spotkania.index'), 'fa-user-plus');
        }

        return $items;
    }

    public static function count(User $user): int
    {
        return array_sum(array_column(self::items($user), 'count'));
    }

    private static function push(array &$items, string $label, int $count, string $url, string $icon): void
    {
        if ($count > 0) {
            $items[] = ['label' => $label, 'count' => $count, 'url' => $url, 'icon' => $icon];
        }
    }
}
