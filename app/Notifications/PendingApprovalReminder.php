<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Przypomnienie do zatwierdzających: lista treści czekających na akceptację
 * dłużej niż 12 godzin. Wysyłana godzinowo przez approvals:notify-pending.
 */
class PendingApprovalReminder extends Notification
{
    public function __construct(
        private readonly array $items,
        private readonly User $recipient,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = count($this->items);
        $msg = (new MailMessage)
            ->subject("Treści oczekujące na zatwierdzenie ({$count})")
            ->greeting('Cześć ' . $this->recipient->name . ',')
            ->line("Poniższe treści czekają na Twoje zatwierdzenie od ponad 12 godzin:");

        foreach ($this->items as $item) {
            $elapsed = $item['waiting_since']->diffForHumans(short: true);
            $msg->line("**{$item['label']}**: [{$item['title']}]({$item['edit_url']}) "
                . "— zgłosił(a): {$item['submitted_by']}, czeka od: {$elapsed}");
        }

        return $msg
            ->action('Przejdź do listy do zatwierdzenia', route('admin.zatwierdzanie.index'))
            ->line('Dziękujemy za szybką reakcję!');
    }
}
