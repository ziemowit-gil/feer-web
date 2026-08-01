<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $filter = $request->get('status', 'open');
        $mine = $request->boolean('moje');

        $query = Task::with(['assignee', 'creator'])
            ->forUser($user)
            ->orderByRaw("FIELD(priority, 'high', 'normal', 'low')")
            ->orderBy('due_date')
            ->orderBy('created_at', 'desc');

        if ($filter === 'done') {
            $query->where('status', 'done');
        } elseif ($filter === 'open') {
            $query->whereIn('status', ['todo', 'in_progress']);
        }

        if ($mine) {
            $query->where('assigned_to', $user->id);
        }

        $tasks = $query->get();
        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $openCount = Task::forUser($user)->pending()->count();
        $doneCount = Task::forUser($user)->where('status', 'done')->count();

        return view('admin.tasks.index', compact('tasks', 'users', 'filter', 'mine', 'openCount', 'doneCount'));
    }

    public function create(): View
    {
        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        return view('admin.tasks.form', ['task' => new Task(), 'users' => $users]);
    }

    public function store(TaskRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        if (($data['status'] ?? '') === 'done') {
            $data['completed_at'] = now();
        }

        $task = Task::create($data);

        $this->maybeSendAssignedNotification($task, null, $request->user()->name);

        return redirect()->route('admin.zadania.index')->with('status', 'Zadanie zostało dodane.');
    }

    public function edit(Task $zadanie): View
    {
        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        return view('admin.tasks.form', ['task' => $zadanie, 'users' => $users]);
    }

    public function update(TaskRequest $request, Task $zadanie): RedirectResponse
    {
        $data = $request->validated();
        $previousAssignee = $zadanie->assigned_to;

        if ($data['status'] === 'done' && ! $zadanie->completed_at) {
            $data['completed_at'] = now();
        } elseif ($data['status'] !== 'done') {
            $data['completed_at'] = null;
        }

        $zadanie->update($data);

        $this->maybeSendAssignedNotification($zadanie, $previousAssignee, $request->user()->name);

        return redirect()->route('admin.zadania.index')->with('status', 'Zadanie zostało zaktualizowane.');
    }

    public function destroy(Task $zadanie): RedirectResponse
    {
        $zadanie->delete();
        return redirect()->route('admin.zadania.index')->with('status', 'Zadanie zostało usunięte.');
    }

    public function done(Task $zadanie): RedirectResponse
    {
        $zadanie->update(['status' => 'done', 'completed_at' => now()]);
        return redirect()->back()->with('status', 'Zadanie oznaczone jako zrobione.');
    }

    private function maybeSendAssignedNotification(Task $task, ?int $previousAssignee, string $assignedBy): void
    {
        if (! $task->assigned_to) {
            return;
        }

        if ($task->assigned_to === $previousAssignee) {
            return;
        }

        $assignee = User::find($task->assigned_to);
        if (! $assignee) {
            return;
        }

        $prefs = $assignee->notification_preferences ?? [];
        if (($prefs['task_assigned'] ?? true) === false) {
            return;
        }

        try {
            $assignee->notify(new TaskAssigned($task, $assignedBy));
        } catch (\Throwable) {
            // swallow — mail errors shouldn't break the panel
        }
    }

    public static function myPendingCount(int $userId): int
    {
        return Task::where('assigned_to', $userId)->pending()->count();
    }
}
