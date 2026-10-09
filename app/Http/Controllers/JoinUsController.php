<?php

namespace App\Http\Controllers;

use App\Models\JobOffer;
use App\Models\Page;
use App\Models\VolunteerAd;
use App\Modules\ModuleManager;

class JoinUsController extends Controller
{
    public function __construct(private readonly ModuleManager $modules) {}

    public function index()
    {
        $page = Page::where('slug', 'dolacz')->where('is_published', true)->first();

        $jobsActive = $this->modules->isActive('jobs');
        $volunteeringActive = $this->modules->isActive('volunteering');

        $offers = $jobsActive ? JobOffer::active()->limit(6)->get() : collect();
        $ads = $volunteeringActive ? VolunteerAd::active()->limit(6)->get() : collect();
        $offersCount = $jobsActive ? JobOffer::active()->count() : 0;
        $adsCount = $volunteeringActive ? VolunteerAd::active()->count() : 0;

        // Ścieżki zaangażowania widoczne jako kafle: współpraca (strona typu „Współpraca"), wsparcie, newsletter — zależnie od modułów.
        $settings = \App\Models\SiteSetting::current();
        $cooperationPage = $this->modules->isActive('cooperation')
            ? Page::where('type', 'wspolpraca')->where('is_published', true)->orderBy('order')->first()
            : null;
        $supportActive = $settings->isModuleEnabled('support');
        $newsletterActive = \Illuminate\Support\Facades\Route::has('newsletter.show');

        return view('dolacz-do-nas', compact('page', 'offers', 'ads', 'offersCount', 'adsCount', 'jobsActive', 'volunteeringActive', 'cooperationPage', 'supportActive', 'newsletterActive'));
    }
}
