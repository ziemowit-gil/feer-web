<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskDueSoon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tasks:remind-due')]
#[Description('Wysyła przypomnienia e-mail o zadaniach, których termin mija jutro.')]
class SendTaskDueReminders extends Command
{
    public function handle(): int
    {
        $tasks = Task::with('assignee')
            ->pending()
            ->whereNotNull('assigned_to')
            ->whereNotNull('due_date')
            ->whereDate('due_date', now()->addDay()->toDateString())
            ->get();

        $sent = 0;

        foreach ($tasks as $task) {
            $user = $task->assignee;
            if (! $user) {
                continue;
            }

            $prefs = $user->notification_preferences ?? [];
            if (($prefs['task_due_soon'] ?? true) === false) {
                continue;
            }

            try {
                $user->notify(new TaskDueSoon($task));
                $sent++;
            } catch (\Throwable) {
                // swallow — nie przerywaj pętli z powodu błędu maila
            }
        }

        $this->info("Wysłano {$sent} przypomni" . ($sent === 1 ? 'enie' : ($sent < 5 ? 'enia' : 'eń')) . ".");

        return self::SUCCESS;
    }
}
