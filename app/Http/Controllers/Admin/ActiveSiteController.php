<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;

/**
 * Przełącznik aktywnej witryny w panelu admina (patrz
 * Middleware\ResolveAdminActiveSite i widok admin.layout — dropdown
 * w pasku bocznym). Po przełączeniu wszystkie ekrany CRUD (aktualności,
 * podstrony, ustawienia itd.) operują na wybranej witrynie.
 */
class ActiveSiteController extends Controller
{
    public function switch(SiteSetting $site)
    {
        session(['admin_active_site_id' => $site->id]);

        return back()->with('status', 'Pracujesz teraz na witrynie „'.$site->site_name.'".');
    }
}
