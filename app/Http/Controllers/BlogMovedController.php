<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;

/**
 * Blog „Wiem FEER” wyłączony — piszemy na Substacku. Dawne adresy bloga (/wiem, /blog, /wiem-feer
 * z dowolną dalszą ścieżką) prowadzą na profil Substack z Ustawień, a dopóki go nie ma — do aktualności.
 * Trasa działa tylko przy wyłączonym module bloga: włączony moduł ma własne trasy, które idą wcześniej.
 */
class BlogMovedController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $substack = trim((string) SiteSetting::current()->substack_url);
        if ($substack !== '' && preg_match('~^https?://~i', $substack)) {
            return redirect()->away($substack, 301);
        }

        return redirect()->to(site_route('news.index'), 302);
    }
}
