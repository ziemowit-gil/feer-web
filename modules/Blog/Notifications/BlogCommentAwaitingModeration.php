<?php

namespace Modules\Blog\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Modules\Blog\Models\BlogComment;

/**
 * Do moderatorów bloga: nowy komentarz czeka na zatwierdzenie.
 *
 * Wysyłana synchronicznie (bez ShouldQueue) — jak ContentSubmittedForApproval:
 * bez uruchomionego workera kolejki powiadomienie by nie dotarło.
 */
class BlogCommentAwaitingModeration extends Notification
{
    public function __construct(private readonly BlogComment $comment) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = $this->comment->article?->title ?? '(usunięty artykuł)';

        return (new MailMessage)
            ->subject('Nowy komentarz do moderacji: ' . $title)
            ->greeting('Cześć ' . $notifiable->name . ',')
            ->line('Do artykułu Wiem FEER „' . $title . '" dodano komentarz, który czeka na zatwierdzenie.')
            ->line('Autor: ' . $this->comment->author_name)
            ->line('Treść: ' . Str::limit($this->comment->body, 300))
            ->action('Przejdź do moderacji komentarzy', route('admin.komentarze-bloga.index'))
            ->line('Komentarz nie jest widoczny na stronie do czasu zatwierdzenia.');
    }
}
