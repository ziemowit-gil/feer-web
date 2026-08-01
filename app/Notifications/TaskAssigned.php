<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssigned extends Notification
{
    public function __construct(
        private readonly Task $task,
        private readonly string $assignedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Nowe zadanie: ' . $this->task->title)
            ->greeting('Cześć ' . $notifiable->name . ',')
            ->line($this->assignedBy . ' przypisał(-a) Ci nowe zadanie: „' . $this->task->title . '".')
            ->line('Priorytet: ' . $this->task->priorityLabel());

        if ($this->task->due_date) {
            $message->line('Termin: ' . $this->task->due_date->format('d.m.Y'));
        }

        if ($this->task->description) {
            $message->line($this->task->description);
        }

        return $message
            ->action('Przejdź do zadania', route('admin.zadania.index'))
            ->line('Dziękujemy!');
    }
}
