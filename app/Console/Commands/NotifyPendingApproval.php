<?php

namespace App\Console\Commands;

use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use App\Models\User;
use App\Notifications\PendingApprovalReminder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('approvals:notify-pending')]
#[Description('Wysyła przypomnienie do zatwierdzających o treściach czekających ponad 12 h.')]
class NotifyPendingApproval extends Command
{
    public function handle(): int
    {
        // Szukamy treści, które przekroczyły próg 12 h (okno 1 h pasujące do godzinnego crona).
        $from = now()->subHours(13);
        $until = now()->subHours(12);

        $items = collect();

        foreach ([
            News::class    => ['label' => 'Aktualność', 'route' => 'admin.newsy.edit'],
            Page::class    => ['label' => 'Strona',     'route' => 'admin.podstrony.edit'],
            Project::class => ['label' => 'Projekt',    'route' => 'admin.projekty.edit'],
        ] as $class => ['label' => $label, 'route' => $route]) {
            $class::with('submittedBy')
                ->where('pending_approval', true)
                ->whereBetween('updated_at', [$from, $until])
                ->get()
                ->each(function ($model) use ($label, $route, &$items) {
                    $items->push([
                        'label'        => $label,
                        'title'        => $model->title,
                        'submitted_by' => $model->submittedBy?->name ?? '—',
                        'waiting_since'=> $model->updated_at,
                        'edit_url'     => route($route, $model),
                    ]);
                });
        }

        if ($items->isEmpty()) {
            $this->info('Brak treści wymagających przypomnienia.');

            return self::SUCCESS;
        }

        $approvers = User::approvers()->whereNotNull('email')->get();

        $sent = 0;
        foreach ($approvers as $approver) {
            try {
                $approver->notify(new PendingApprovalReminder($items->all(), $approver));
                $sent++;
            } catch (\Throwable $e) {
                $this->error("Błąd powiadomienia dla {$approver->email}: {$e->getMessage()}");
            }
        }

        $this->info("Wysłano {$sent} przypomnień o {$items->count()} treściach.");

        return self::SUCCESS;
    }
}
