<?php

namespace App\Mail;

use App\Models\SklepOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SklepOrderPaidMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SklepOrder $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Twój zakup w sklepie '.config('app.name'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.sklep-order-paid');
    }
}
