<?php

declare(strict_types=1);

namespace Modules\Contact\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Modules\Contact\Support\ContactOptions;
use Symfony\Component\HttpFoundation\Response;

/** /kontakt/kontakt.vcf — wizytówka organizacji do zapisania w telefonie (gdy włączona w Komunikacja → Kontakt). */
final class VcardController extends Controller
{
    public function show(): Response
    {
        $settings = SiteSetting::current();
        abort_unless(ContactOptions::for($settings)->vcard(), 404);

        return response(ContactOptions::vcard3($settings), 200, [
            'Content-Type' => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="kontakt.vcf"',
        ]);
    }
}
