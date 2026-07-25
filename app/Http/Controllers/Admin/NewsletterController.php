<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function edit()
    {
        return view('admin.newsletter.edit', ['settings' => SiteSetting::current()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'newsletter_code' => ['nullable', 'string'],
        ]);

        SiteSetting::current()->update($data);

        return redirect()->route('admin.newsletter.edit')->with('status', 'Kod formularza newslettera został zapisany.');
    }
}
