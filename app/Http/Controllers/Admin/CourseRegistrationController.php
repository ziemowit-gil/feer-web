<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseEdition;
use App\Models\CourseRegistration;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Panel: uczestnicy konkretnego terminu: lista, ręczne dodawanie przez koordynatora (zapisy telefoniczne i mailowe),
 * szybka zmiana statusu (potwierdzenie udziału lub wpłaty, lista rezerwowa, anulowanie, ukończenie) i usuwanie.
 *
 * Metody: index(), store(), status(), destroy().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class CourseRegistrationController extends Controller
{
    public function index(Request $request, CourseEdition $edition)
    {
        $this->authorizeSite($edition);
        $edition->load('course');

        $filter = array_key_exists((string) $request->query('status'), CourseRegistration::STATUSES) ? $request->query('status') : null;

        $registrations = $edition->registrations()
            ->when($filter, fn ($q) => $q->where('status', $filter))
            ->orderByRaw("case status when 'pending' then 0 when 'waitlist' then 1 when 'confirmed' then 2 when 'completed' then 3 else 4 end")
            ->orderBy('created_at')
            ->get();

        $counts = $edition->registrations()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('admin.courses.registrations', compact('edition', 'registrations', 'counts', 'filter'));
    }

    /** Ręczny zapis: przy braku miejsc status zajmujący miejsce zamienia się na listę rezerwową. */
    public function store(Request $request, CourseEdition $edition)
    {
        $this->authorizeSite($edition);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'accessibility_needs' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(array_keys(CourseRegistration::STATUSES))],
            'source' => ['required', Rule::in(['phone', 'email', 'other'])],
        ]);

        $notice = DB::transaction(function () use ($edition, $data) {
            $locked = CourseEdition::lockForUpdate()->findOrFail($edition->id);
            $email = mb_strtolower(trim($data['email']));

            if ($locked->registrations()->where('email', $email)->where('status', '!=', 'cancelled')->exists()) {
                return ['error' => 'Ten adres e-mail jest już na liście tego terminu.'];
            }

            $status = $data['status'];
            $moved = in_array($status, CourseEdition::SEAT_STATUSES, true) && $locked->isFull();
            if ($moved) {
                $status = 'waitlist';
            }

            $locked->registrations()->create([...$data, 'email' => $email, 'status' => $status]);

            return ['ok' => $moved ? 'Dodano na listę rezerwową: w terminie nie ma wolnych miejsc.' : 'Uczestnik dodany.'];
        });

        return isset($notice['error'])
            ? back()->withErrors(['email' => $notice['error']])->withInput()
            : back()->with('status', $notice['ok']);
    }

    /** Szybka zmiana statusu; przeniesienie z listy rezerwowej do pełnego terminu jest dozwolone (koordynator decyduje) z ostrzeżeniem. */
    public function status(Request $request, CourseRegistration $registration)
    {
        $this->authorizeSite($registration->edition);

        $status = $request->validate(['status' => ['required', Rule::in(array_keys(CourseRegistration::STATUSES))]])['status'];

        $edition = $registration->edition;
        $overbooked = ! $registration->holdsSeat()
            && in_array($status, CourseEdition::SEAT_STATUSES, true)
            && $edition->isFull();

        $registration->update(['status' => $status]);

        return back()->with('status', $overbooked
            ? 'Status zmieniony, ale limit miejsc w tym terminie jest już przekroczony.'
            : 'Status zmieniony na: '.$registration->statusLabel().'.');
    }

    public function destroy(CourseRegistration $registration)
    {
        $this->authorizeSite($registration->edition);
        $registration->delete();

        return back()->with('status', 'Zgłoszenie usunięte.');
    }

    private function authorizeSite(CourseEdition $edition): void
    {
        abort_unless($edition->course->site_id === SiteSetting::current()->id, 404);
    }
}
