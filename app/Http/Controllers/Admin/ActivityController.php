<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Panel admin: przeglądarka dziennika zdarzeń (spatie/laravel-activitylog)
 * z filtrami po typie zdarzenia, obiekcie, użytkowniku i dacie.
 */
class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $event    = $request->query('event', '');
        $subject  = $request->query('subject', '');
        $userName = $request->query('user', '');
        $dateFrom = $request->query('date_from', '');
        $dateTo   = $request->query('date_to', '');

        $logs = Activity::with('causer')
            ->where('log_name', 'cms')
            ->when($event !== '', fn ($q) => $q->where('event', $event))
            ->when($subject !== '', fn ($q) => $q->where('subject_type', $subject))
            ->when($userName !== '', fn ($q) => $q->whereHasMorph(
                'causer', [User::class],
                fn ($u) => $u->where('name', 'like', '%' . addcslashes($userName, '%_') . '%')
                             ->orWhere('email', 'like', '%' . addcslashes($userName, '%_') . '%')
            ))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $users = User::orderBy('name')->pluck('name');

        return view('admin.activity.index', compact('logs', 'event', 'subject', 'userName', 'dateFrom', 'dateTo', 'users'));
    }
}
