<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Indywidualne logowanie organizacji członkowskiej — każda organizacja ma
 * własny login i hasło (analogicznie do stron typu "brand_assets") i po
 * zalogowaniu może edytować wyłącznie swoją wizytówkę w katalogu.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class OrganizationLoginController extends Controller
{
    public function showLogin()
    {
        if ($this->currentOrganization()) {
            return redirect()->route('organization.panel.edit');
        }

        return view('organizations.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $organization = Organization::where('login', $data['login'])->first();

        if (! $organization || ! $organization->password || ! Hash::check($data['password'], $organization->password)) {
            return back()->withErrors(['login' => 'Nieprawidłowy login lub hasło.'])->onlyInput('login');
        }

        $request->session()->regenerate();
        session(['organization_id' => $organization->id]);

        return redirect()->route('organization.panel.edit');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('organization_id');

        return redirect()->route('organization.login');
    }

    private function currentOrganization(): ?Organization
    {
        $id = session('organization_id');

        return $id ? Organization::find($id) : null;
    }
}
