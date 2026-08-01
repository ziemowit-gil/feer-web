<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDueSoon extends Notification
{
    public function __construct(
        private readonly Task $task,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Termin zadania mija jutro: ' . $this->task->title)
            ->greeting('Cześć ' . $notifiable->name . ',')
            ->line('Termin Twojego zadania „' . $this->task->title . '" mija jutro (' . $this->task->due_date->format('d.m.Y') . ').')
            ->line('Aktualny status: ' . $this->task->statusLabel() . '.')
            ->action('Przejdź do zadania', route('admin.zadania.index', ['moje' => 1]))
            ->line('Dziękujemy!');
    }
}
