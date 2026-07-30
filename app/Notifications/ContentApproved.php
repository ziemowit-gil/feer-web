<?php

namespace App\Notifications;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Do autora zgłoszenia: treść została zatwierdzona i opublikowana. */
class ContentApproved extends Notification
{
    public function __construct(
        private readonly Model $content,
        private readonly string $label,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Zatwierdzono: ' . $this->content->title)
            ->greeting('Cześć ' . $notifiable->name . ',')
            ->line('Twoja treść — ' . $this->label . ' „' . $this->content->title
                . '" — została zatwierdzona i opublikowana.')
            ->line('Dziękujemy za wkład!');
    }
}
