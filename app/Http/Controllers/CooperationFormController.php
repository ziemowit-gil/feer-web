<?php

namespace App\Http\Controllers;

use App\Mail\CooperationRequestMail;
use App\Models\CooperationRequest;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CooperationFormController extends Controller
{
    public function show(Page $page)
    {
        abort_unless($page->isCooperation(), 404);

        $cd = $page->cooperation_data ?? [];
        abort_unless(! empty($cd['form_enabled']), 404);

        return view('cooperation.form', compact('page', 'cd'));
    }

    public function store(Request $request, Page $page)
    {
        abort_unless($page->isCooperation(), 404);

        $cd = $page->cooperation_data ?? [];
        abort_unless(! empty($cd['form_enabled']), 404);

        $data = $request->validate([
            'name'               => ['required', 'string', 'max:120'],
            'email'              => ['required', 'email', 'max:200'],
            'organization'       => ['nullable', 'string', 'max:200'],
            'sector'             => ['nullable', 'string', 'max:120'],
            'cooperation_types'  => ['nullable', 'array'],
            'cooperation_types.*'=> ['string', 'max:120'],
            'message'            => ['nullable', 'string', 'max:2000'],
            'privacy'            => ['accepted'],
        ], [
            'name.required'    => 'Imię i nazwisko jest wymagane.',
            'email.required'   => 'Adres e-mail jest wymagany.',
            'email.email'      => 'Podaj prawidłowy adres e-mail.',
            'privacy.accepted' => 'Wymagana zgoda na przetwarzanie danych.',
        ]);

        $req = CooperationRequest::create([
            'page_id'           => $page->id,
            'name'              => $data['name'],
            'email'             => $data['email'],
            'organization'      => $data['organization'] ?? null,
            'sector'            => $data['sector'] ?? null,
            'cooperation_types' => $data['cooperation_types'] ?? null,
            'message'           => $data['message'] ?? null,
        ]);

        $recipient = filled($cd['form_recipient'] ?? null)
            ? $cd['form_recipient']
            : SiteSetting::current()->contact_email;

        if (filled($recipient)) {
            Mail::to($recipient)->send(new CooperationRequestMail($req));
        }

        return redirect()
            ->route('cooperation.form.show', $page)
            ->with('sent', $cd['form_confirmation'] ?? 'Dziękujemy! Odezwiemy się wkrótce.');
    }
}
