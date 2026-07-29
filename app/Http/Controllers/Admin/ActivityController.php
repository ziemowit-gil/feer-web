<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $event = $request->query('event', '');
        $subject = $request->query('subject', '');

        $logs = ActivityLog::with('user')
            ->when($event !== '', fn ($q) => $q->where('event', $event))
            ->when($subject !== '', fn ($q) => $q->where('subject_type', $subject))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('admin.activity.index', compact('logs', 'event', 'subject'));
    }
}
