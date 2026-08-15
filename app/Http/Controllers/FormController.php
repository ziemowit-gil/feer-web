<?php

namespace App\Http\Controllers;

use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class FormController extends Controller
{
    public function show(FormDefinition $formularz)
    {
        abort_unless($formularz->is_active, 404);

        return view('formularz.show', [
            'form'     => $formularz,
            'settings' => SiteSetting::current(),
        ]);
    }

    public function store(Request $request, FormDefinition $formularz)
    {
        abort_unless($formularz->is_active, 404);

        $request->validate(
            $formularz->validationRules(),
            [],
            $formularz->validationAttributes(),
        );

        $submission = FormSubmission::create([
            'form_definition_id' => $formularz->id,
            'data'               => $request->input('data', []),
            'ip_address'         => $request->ip(),
        ]);

        $notificationEmail = $formularz->settings['notification_email'] ?? null;
        if (filled($notificationEmail)) {
            $this->sendNotification($formularz, $submission, $notificationEmail);
        }

        $confirmationMessage = filled($formularz->settings['confirmation_message'] ?? null)
            ? $formularz->settings['confirmation_message']
            : 'Dziękujemy! Twoje zgłoszenie zostało przyjęte.';

        return redirect()
            ->route('formularz.show', $formularz->slug)
            ->with('success', $confirmationMessage);
    }

    private function sendNotification(FormDefinition $formularz, FormSubmission $submission, string $to): void
    {
        $fields = $formularz->normalizedFields();
        $data   = $submission->data;

        $lines = collect($fields)->map(fn ($f) => [
            'label' => $f['label'],
            'value' => $data[$f['key']] ?? '—',
        ])->all();

        Mail::raw(
            implode("\n", array_map(fn ($l) => $l['label'] . ': ' . $l['value'], $lines)),
            function ($msg) use ($formularz, $to) {
                $msg->to($to)->subject('Nowe zgłoszenie: ' . $formularz->title);
            }
        );
    }
}
