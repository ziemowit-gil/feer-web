<?php

namespace App\Notifications;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Do akceptujących: edytor zgłosił treść do zatwierdzenia.
 *
 * Wysyłana synchronicznie (bez ShouldQueue) — kolejka projektu to `database`,
 * a bez uruchomionego workera powiadomienie by nie dotarło.
 */
class ContentSubmittedForApproval extends Notification
{
    public function __construct(
        private readonly Model $content,
        private readonly string $label,
        private readonly ?string $submittedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nowa treść do zatwierdzenia: ' . $this->content->title)
            ->greeting('Cześć ' . $notifiable->name . ',')
            ->line($this->label . ' „' . $this->content->title . '" została zgłoszona do zatwierdzenia'
                . ($this->submittedBy ? ' przez: ' . $this->submittedBy : '') . '.')
            ->line('Treść pozostaje nieopublikowana do czasu akceptacji.')
            ->action('Przejdź do zatwierdzania', route('admin.zatwierdzanie.index'))
            ->line('Dziękujemy!');
    }
}
