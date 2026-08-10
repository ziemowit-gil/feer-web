<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CooperationRequest;
use App\Models\Page;
use Illuminate\Http\Request;

class CooperationRequestController extends Controller
{
    public function index(Request $request)
    {
        $pageId = $request->query('strona');

        $requests = CooperationRequest::with('page')
            ->when($pageId, fn ($q) => $q->where('page_id', $pageId))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $pages = Page::where('type', 'wspolpraca')->orderBy('title')->get();

        return view('admin.cooperation-requests.index', compact('requests', 'pages', 'pageId'));
    }

    public function show(CooperationRequest $cooperationRequest)
    {
        $cooperationRequest->markAsRead();

        return view('admin.cooperation-requests.show', compact('cooperationRequest'));
    }

    public function destroy(CooperationRequest $cooperationRequest)
    {
        $cooperationRequest->delete();

        return redirect()->route('admin.wspolpraca-zgloszenia.index')
            ->with('status', 'Zgłoszenie zostało usunięte.');
    }
}
