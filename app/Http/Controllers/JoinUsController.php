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

        $offers = $jobsActive
            ? JobOffer::active()->limit(4)->get()
            : collect();

        $ads = $volunteeringActive
            ? VolunteerAd::active()->limit(4)->get()
            : collect();

        return view('dolacz-do-nas', compact('page', 'offers', 'ads', 'jobsActive', 'volunteeringActive'));
    }
}
