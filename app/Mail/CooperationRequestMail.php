<?php

namespace App\Mail;

use App\Models\CooperationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CooperationRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CooperationRequest $cooperationRequest) {}

    public function envelope(): Envelope
    {
        $from = $this->cooperationRequest->organization
            ? "{$this->cooperationRequest->name} ({$this->cooperationRequest->organization})"
            : $this->cooperationRequest->name;

        return new Envelope(
            subject: "[Współpraca] Nowe zgłoszenie od {$from}",
            replyTo: [$this->cooperationRequest->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cooperation-request',
        );
    }
}
