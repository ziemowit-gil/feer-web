<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberInvitation;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MemberInvitationController extends Controller
{
    /** Strona lądowania — wybór metody logowania (MS365 lub magic link). */
    public function show(string $token): View|RedirectResponse
    {
        $invitation = MemberInvitation::findValidByToken($token);

        if (! $invitation) {
            abort(404, 'Zaproszenie jest nieważne, wygasło lub zostało już wykorzystane.');
        }

        $settings = SiteSetting::current();

        return view('auth.invitation', compact('invitation', 'token', 'settings'));
    }

    /** Logowanie magic linkiem — bez MS365. */
    public function magic(string $token): RedirectResponse
    {
        $invitation = MemberInvitation::findValidByToken($token);

        if (! $invitation) {
            return redirect()->route('member.login')
                ->with('error', 'Zaproszenie jest nieważne, wygasło lub zostało już wykorzystane.');
        }

        $member = Member::where('email', $invitation->email)->first()
            ?? Member::create([
                'name'  => explode('@', $invitation->email)[0],
                'email' => $invitation->email,
            ]);

        $invitation->markUsed();

        Auth::guard('member')->login($member);

        return redirect()->intended('/');
    }

    /** Przekierowanie do MS365 z kontekstem zaproszenia (token w sesji). */
    public function redirectToMicrosoft(string $token): RedirectResponse
    {
        $invitation = MemberInvitation::findValidByToken($token);

        if (! $invitation) {
            return redirect()->route('member.login')
                ->with('error', 'Zaproszenie jest nieważne, wygasło lub zostało już wykorzystane.');
        }

        // Token trafia do sesji — callback MS365 go odczyta i oznaczy jako użyte.
        session(['member_invitation_token' => $token]);

        return redirect()->route('member.microsoft.redirect');
    }
}
