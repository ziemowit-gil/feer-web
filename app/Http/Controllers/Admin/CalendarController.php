<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CalendarController extends Controller
{
    /** Kalendarz redakcyjny: publikacje aktualności i terminy wydarzeń w danym miesiącu. */
    public function index(Request $request)
    {
        $month = $request->query('month');
        try {
            $cursor = $month ? Carbon::createFromFormat('Y-m', $month)->startOfMonth() : Carbon::now()->startOfMonth();
        } catch (\Throwable) {
            $cursor = Carbon::now()->startOfMonth();
        }

        $user = $request->user();
        $monthStart = $cursor->copy()->startOfMonth();
        $monthEnd = $cursor->copy()->endOfMonth();

        // Zbierz wpisy z zakresu miesiąca, pogrupowane po dacie (Y-m-d).
        $byDay = [];

        if ($user->canAccessModule('news')) {
            News::whereBetween('published_at', [$monthStart, $monthEnd])->get()->each(function (News $n) use (&$byDay) {
                $key = $n->published_at->format('Y-m-d');
                $byDay[$key][] = [
                    'kind' => $n->published_at->isFuture() ? 'Zaplanowany news' : 'Aktualność',
                    'title' => $n->title,
                    'url' => route('admin.newsy.edit', $n),
                    'color' => $n->published_at->isFuture() ? 'amber' : 'sky',
                    'icon' => 'fa-newspaper',
                ];
            });
        }

        if ($user->canAccessModule('events')) {
            Event::whereBetween('starts_at', [$monthStart, $monthEnd])->get()->each(function (Event $e) use (&$byDay) {
                $key = $e->starts_at->format('Y-m-d');
                $byDay[$key][] = [
                    'kind' => 'Wydarzenie',
                    'title' => $e->title,
                    'url' => route('admin.wydarzenia.edit', $e),
                    'color' => 'violet',
                    'icon' => 'fa-calendar-day',
                ];
            });
        }

        // Siatka: od poniedziałku tygodnia z 1. dniem miesiąca do niedzieli tygodnia z ostatnim.
        $gridStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);

        $days = [];
        for ($d = $gridStart->copy(); $d->lte($gridEnd); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $days[] = [
                'date' => $d->copy(),
                'inMonth' => $d->month === $cursor->month,
                'isToday' => $d->isToday(),
                'items' => $byDay[$key] ?? [],
            ];
        }

        return view('admin.calendar.index', [
            'cursor' => $cursor,
            'days' => $days,
            'prevMonth' => $cursor->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $cursor->copy()->addMonth()->format('Y-m'),
        ]);
    }
}
