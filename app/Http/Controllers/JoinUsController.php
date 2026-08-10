<?php

namespace App\Http\Controllers;

use App\Models\JobOffer;
use App\Models\VolunteerAd;
use App\Modules\ModuleManager;

class JoinUsController extends Controller
{
    public function __construct(private readonly ModuleManager $modules) {}

    public function index()
    {
        $jobsActive = $this->modules->isActive('jobs');
        $volunteeringActive = $this->modules->isActive('volunteering');

        $offers = $jobsActive
            ? JobOffer::active()->limit(4)->get()
            : collect();

        $ads = $volunteeringActive
            ? VolunteerAd::active()->limit(4)->get()
            : collect();

        return view('dolacz-do-nas', compact('offers', 'ads', 'jobsActive', 'volunteeringActive'));
    }
}
