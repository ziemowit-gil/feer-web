<?php

namespace App\Http\Controllers;

use App\Models\JobOffer;
use App\Models\SiteSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

/**
 * Publiczna lista aktywnych ogłoszeń o pracę, widok szczegółów i eksport PDF.
 *
 * Metody: index(), show(), pdf().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class JobOfferController extends Controller
{
    public function index()
    {
        return view('praca.index', [
            'offers' => JobOffer::active()->get(),
        ]);
    }

    public function show(JobOffer $offer)
    {
        abort_unless($offer->is_published && ! $offer->isClosed(), 404);

        return view('praca.show', compact('offer'));
    }

    public function pdf(JobOffer $offer): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless($offer->is_published, 404);

        $siteSettings = SiteSetting::current();
        $printedAt    = now()->format('d.m.Y');
        $filename     = Str::slug($offer->title) . '.pdf';

        return Pdf::loadView('praca.pdf', compact('offer', 'siteSettings', 'printedAt'))
            ->setPaper('a4')
            ->download($filename);
    }
}
