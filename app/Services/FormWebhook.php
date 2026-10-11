<?php

namespace App\Services;

use App\Models\FormDefinition;
use App\Models\FormSubmission;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wysyłka zgłoszenia z formularza na zewnętrzne API (webhook, Ustawienia formularza →
 * „Gdzie trafiają zgłoszenia”). POST JSON; z sekretem — podpis HMAC SHA-256 treści
 * w nagłówku X-Form-Signature („sha256=…”). Pliki: tylko nazwa, rozmiar i typ (zawartość
 * zostaje w CMS-ie). Błąd nie przerywa obsługi zgłoszenia — wynik trafia do zgłoszenia i logu.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class FormWebhook
{
    /** Treść wysyłana do API. */
    public static function payload(FormDefinition $form, FormSubmission $submission): array
    {
        $data = $submission->data ?? [];

        return [
            'event' => 'form.submission',
            'form' => ['id' => $form->id, 'slug' => $form->slug, 'title' => $form->title],
            'submission' => [
                'id' => $submission->id,
                'created_at' => optional($submission->created_at)->toIso8601String(),
            ],
            'fields' => collect($form->inputFields())->map(function (array $field) use ($data) {
                $raw = $data[$field['key']] ?? null;
                if (($field['type'] ?? '') === 'file' && is_array($raw)) {
                    $raw = array_intersect_key($raw, array_flip(['name', 'size', 'mime']));
                }

                return [
                    'key' => $field['key'],
                    'label' => (string) ($field['label'] ?? $field['key']),
                    'type' => $field['type'] ?? 'text',
                    'value' => $raw,
                    'text' => FormDefinition::displayValue($field, $data[$field['key']] ?? null),
                ];
            })->values()->all(),
        ];
    }

    /** Wysyła zgłoszenie; zwraca true przy odpowiedzi 2xx. Wynik zapisuje w zgłoszeniu. */
    public function send(FormDefinition $form, FormSubmission $submission): bool
    {
        $url = trim((string) ($form->settings['webhook_url'] ?? ''));
        if (! preg_match('#^https?://#i', $url)) {
            return false;
        }

        $body = json_encode(self::payload($form, $submission), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $headers = ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'User-Agent' => 'FEER-Formularze/1.0'];
        $secret = (string) ($form->settings['webhook_secret'] ?? '');
        if ($secret !== '') {
            $headers['X-Form-Signature'] = 'sha256='.hash_hmac('sha256', $body, $secret);
        }

        try {
            $response = Http::withHeaders($headers)->timeout(10)->connectTimeout(5)
                ->withBody($body, 'application/json')->post($url);
            $ok = $response->successful();
            $error = $ok ? null : 'HTTP '.$response->status().': '.mb_substr(trim(strip_tags($response->body())), 0, 300);
        } catch (\Throwable $e) {
            $ok = false;
            $error = mb_substr($e->getMessage(), 0, 300);
        }

        $submission->forceFill([
            'webhook_sent_at' => $ok ? now() : null,
            'webhook_error' => $error,
        ])->save();

        if (! $ok) {
            Log::warning('Formularz: nie udało się wysłać zgłoszenia na API.', ['form' => $form->slug, 'submission' => $submission->id, 'error' => $error]);
        }

        return $ok;
    }
}
