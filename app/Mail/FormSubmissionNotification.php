<?php

namespace App\Mail;

use App\Models\FormDefinition;
use App\Models\FormSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Powiadomienie o nowym zgłoszeniu z formularza (moduł Formularze): tabela
 * pól w HTML z odnośnikiem do zgłoszenia w panelu. Ten sam Mailable obsługuje
 * kopię dla osoby zgłaszającej ($forSubmitter = true) — bez danych technicznych.
 */
class FormSubmissionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public FormDefinition $form,
        public FormSubmission $submission,
        public bool $forSubmitter = false,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->forSubmitter
            ? 'Potwierdzenie zgłoszenia: '.$this->form->title
            : (filled($this->form->settings['notification_subject'] ?? null)
                ? str_replace(['{tytul}', '{id}'], [$this->form->title, (string) $this->submission->id], $this->form->settings['notification_subject'])
                : 'Nowe zgłoszenie: '.$this->form->title);

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.form-submission', with: [
            'rows' => $this->rows(),
            'forSubmitter' => $this->forSubmitter,
            'form' => $this->form,
            'submission' => $this->submission,
            'introText' => $this->forSubmitter
                ? (filled($this->form->settings['submitter_copy_message'] ?? null)
                    ? $this->form->settings['submitter_copy_message']
                    : 'Dziękujemy za wypełnienie formularza. Poniżej kopia przesłanych danych.')
                : null,
        ]);
    }

    /**
     * Pary etykieta → wartość w kolejności pól formularza.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public function rows(): array
    {
        $data = $this->submission->data ?? [];

        return collect($this->form->normalizedFields())->map(function (array $field) use ($data) {
            $value = $data[$field['key']] ?? null;
            if (is_array($value)) {
                $value = implode(', ', array_map('strval', $value));
            }

            return [
                'label' => (string) ($field['label'] ?? $field['key']),
                'value' => trim((string) ($value ?? '')) === '' ? '—' : (string) $value,
            ];
        })->all();
    }

    /** Wersja tekstowa (dla transportów bez HTML / podglądu). */
    public function plainText(): string
    {
        return implode("\n", array_map(fn ($r) => $r['label'].': '.$r['value'], $this->rows()));
    }
}
