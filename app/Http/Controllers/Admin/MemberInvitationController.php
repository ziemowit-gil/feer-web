<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemberInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberInvitationController extends Controller
{
    public function index(): View
    {
        $invitations = MemberInvitation::with('invitedBy')
            ->latest()
            ->paginate(30);

        return view('admin.member-invitations.index', compact('invitations'));
    }

    public function create(): View
    {
        return view('admin.member-invitations.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email'      => ['required', 'email', 'max:255'],
            'note'       => ['nullable', 'string', 'max:500'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        MemberInvitation::create([
            'email'      => strtolower(trim($data['email'])),
            'token'      => MemberInvitation::generateToken(),
            'invited_by' => auth()->id(),
            'note'       => $data['note'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return redirect()->route('admin.zaproszenia-strefy.index')
            ->with('success', 'Zaproszenie zostało wygenerowane.');
    }

    public function destroy(MemberInvitation $zaproszenieStrefy): RedirectResponse
    {
        $zaproszenieStrefy->delete();

        return redirect()->route('admin.zaproszenia-strefy.index')
            ->with('success', 'Zaproszenie zostało usunięte.');
    }
}
