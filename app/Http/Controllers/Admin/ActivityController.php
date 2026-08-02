<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

/**
 * Panel admin: przeglądarka dziennika zdarzeń (ActivityLog) z filtrami po typie zdarzenia,
 * obiekcie i użytkowniku.
 *
 * Metody: index().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class ActivityController extends Controller
{
    /** Wyświetla dziennik zdarzeń z filtrami po typie, obiekcie, użytkowniku i dacie. */
    public function index(Request $request)
    {
        $event    = $request->query('event', '');
        $subject  = $request->query('subject', '');
        $userName = $request->query('user', '');
        $dateFrom = $request->query('date_from', '');
        $dateTo   = $request->query('date_to', '');

        $logs = ActivityLog::with('user')
            ->when($event !== '', fn ($q) => $q->where('event', $event))
            ->when($subject !== '', fn ($q) => $q->where('subject_type', $subject))
            ->when($userName !== '', fn ($q) => $q->where('user_name', 'like', '%'.addcslashes($userName, '%_').'%'))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $users = ActivityLog::selectRaw('DISTINCT user_name')
            ->whereNotNull('user_name')
            ->orderBy('user_name')
            ->pluck('user_name');

        return view('admin.activity.index', compact('logs', 'event', 'subject', 'userName', 'dateFrom', 'dateTo', 'users'));
    }
}
