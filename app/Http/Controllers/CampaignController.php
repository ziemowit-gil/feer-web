<?php

namespace App\Http\Controllers;

use App\Models\Campaign;

/**
 * Publiczny listing kampanii zbiórkowych i podstrona pojedynczej kampanii.
 *
 * Metody: index(), show().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::published()
            ->orderBy('order')
            ->orderByDesc('created_at')
            ->get();

        return view('campaigns.index', compact('campaigns'));
    }

    public function show(string $slug)
    {
        $campaign = Campaign::published()->where('slug', $slug)->firstOrFail();

        return view('campaigns.show', compact('campaign'));
    }
}
