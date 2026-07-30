<?php

namespace App\Notifications;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Do autora zgłoszenia: treść odrzucona — wróciła do wersji roboczej. */
class ContentRejected extends Notification
{
    public function __construct(
        private readonly Model $content,
        private readonly string $label,
        private readonly ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Odrzucono: ' . $this->content->title)
            ->greeting('Cześć ' . $notifiable->name . ',')
            ->line('Twoja treść — ' . $this->label . ' „' . $this->content->title
                . '" — została odrzucona i wróciła do wersji roboczej (szkicu).');

        if (filled($this->reason)) {
            $mail->line('Powód: ' . $this->reason);
        }

        return $mail->line('Popraw treść i zgłoś ją ponownie do zatwierdzenia.');
    }
}
