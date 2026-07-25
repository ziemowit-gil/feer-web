<?php

namespace App\Http\Controllers;

use App\Models\Poll;
use App\Models\PollOption;
use Illuminate\Http\Request;

class PollVoteController extends Controller
{
    public function store(Request $request, Poll $poll)
    {
        $data = $request->validate([
            'option_id' => ['required', 'exists:poll_options,id'],
        ]);

        $votedKey = "voted_polls.{$poll->id}";

        if (! session()->has($votedKey)) {
            PollOption::where('poll_id', $poll->id)
                ->where('id', $data['option_id'])
                ->increment('votes');

            session([$votedKey => $data['option_id']]);
        }

        return redirect(route('home').'#ankieta');
    }
}
