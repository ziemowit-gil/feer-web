<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaterialSubscriber;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Panel admin: lista subskrybentów powiadomień o materiałach edukacyjnych z eksportem CSV.
 *
 * Metody: index(), destroy(), export().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class MaterialSubscriberController extends Controller
{
    /** Wyświetla listę subskrybentów powiadomień o materiałach edukacyjnych. */
    public function index()
    {
        return view('admin.material-subscribers.index', [
            'subscribers' => MaterialSubscriber::latest()->paginate(50),
            'total' => MaterialSubscriber::count(),
        ]);
    }

    /** Usuwa adres e-mail z listy subskrybentów. */
    public function destroy(MaterialSubscriber $subscriber)
    {
        $subscriber->delete();

        return redirect()
            ->route('admin.zapisy-materialy.index')
            ->with('status', 'Adres został usunięty z listy.');
    }

    /** Eksportuje listę subskrybentów do pliku CSV. */
    public function export(): StreamedResponse
    {
        $filename = 'zapisy-materialy-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['email', 'data zapisu']);

            MaterialSubscriber::orderBy('created_at')->chunk(200, function ($chunk) use ($handle) {
                foreach ($chunk as $subscriber) {
                    fputcsv($handle, [$subscriber->email, $subscriber->created_at->format('Y-m-d H:i')]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
