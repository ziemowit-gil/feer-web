<?php

namespace App\Http\Controllers;

use App\Http\Requests\WebinarRegistrationRequest;
use App\Models\LandingPage;
use App\Services\Webinar\RegistrationHandler;

class LandingPageController extends Controller
{
    public function show(string $slug)
    {
        $page = LandingPage::published()->where('slug', $slug)->firstOrFail();

        return view('lp.show', compact('page'));
    }

    public function register(WebinarRegistrationRequest $request, string $slug, RegistrationHandler $handler)
    {
        $page = LandingPage::published()->where('slug', $slug)->firstOrFail();

        $handler->handle($page, $request->validated());

        $message = $page->form_success ?: 'Dziękujemy! Twoje zgłoszenie zostało zapisane — szczegóły wyślemy na podany adres e-mail.';

        return response()->json(['ok' => true, 'message' => $message]);
    }
}
