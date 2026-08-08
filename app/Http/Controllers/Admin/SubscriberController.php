<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Panel admin: przegląd i usuwanie tematycznych subskrybentów.
 *
 * Metody: index(), destroy(), export().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class SubscriberController extends Controller
{
    /** Lista subskrybentów z filtrowaniem po temacie i statusie potwierdzenia. */
    public function index(Request $request)
    {
        $topic     = $request->query('topic', '');
        $confirmed = $request->query('confirmed', '');

        $subscribers = Subscriber::query()
            ->when($topic !== '', fn ($q) => $q->whereJsonContains('topics', $topic))
            ->when($confirmed === '1', fn ($q) => $q->whereNotNull('confirmed_at'))
            ->when($confirmed === '0', fn ($q) => $q->whereNull('confirmed_at'))
            ->latest()
            ->get();

        return view('admin.subscribers.index', [
            'subscribers' => $subscribers,
            'topics'      => Subscriber::$availableTopics,
            'topic'       => $topic,
            'confirmed'   => $confirmed,
        ]);
    }

    /** Usuwa subskrybenta. */
    public function destroy(Subscriber $subscriber)
    {
        $subscriber->delete();

        return back()->with('status', 'Subskrybent został usunięty.');
    }

    /** Eksportuje listę do CSV. */
    public function export(Request $request): StreamedResponse
    {
        $topic = $request->query('topic', '');

        $subscribers = Subscriber::query()
            ->whereNotNull('confirmed_at')
            ->when($topic !== '', fn ($q) => $q->whereJsonContains('topics', $topic))
            ->latest()
            ->get();

        $filename = 'subskrybenci' . ($topic ? "-{$topic}" : '') . '-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($subscribers) {
            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF"); // BOM dla Excela
            fputcsv($out, ['Imię / nick', 'E-mail', 'Tematy', 'Data potwierdzenia'], ';');

            foreach ($subscribers as $s) {
                fputcsv($out, [
                    $s->name ?? '',
                    $s->email,
                    implode(', ', $s->topicLabels()),
                    $s->confirmed_at?->format('Y-m-d H:i'),
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
