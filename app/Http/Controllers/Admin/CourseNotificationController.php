<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\CourseNewEditionMail;
use App\Models\Course;
use App\Models\CourseNotification;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

/**
 * Panel: osoby czekające na powiadomienie o terminie szkolenia („Powiadom mnie”). Wysyłka jest ręczna, jednorazowa
 * na zapis: wiadomość o wybranym terminie z otwartą rekrutacją do tych, którzy jeszcze nic nie dostali.
 *
 * Metody: send(), destroy().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class CourseNotificationController extends Controller
{
    public function send(Request $request, Course $course)
    {
        $this->authorizeSite($course);
        abort_unless(Schema::hasTable('course_notifications'), 404);

        $data = $request->validate([
            'edition_id' => ['required', 'integer', Rule::exists('course_editions', 'id')->where('course_id', $course->id)],
        ], ['edition_id.required' => 'Wybierz termin, o którym chcesz powiadomić.']);

        $edition = $course->editions()->findOrFail($data['edition_id']);
        if (! $edition->acceptsRegistrations()) {
            return back()->withErrors(['edition_id' => 'Ten termin nie przyjmuje zapisów, więc nie ma sensu o nim powiadamiać.']);
        }

        $siteName = SiteSetting::current()->site_name;
        $sent = 0;
        $failed = 0;

        foreach (CourseNotification::where('course_id', $course->id)->whereNull('notified_at')->get() as $subscriber) {
            try {
                Mail::to($subscriber->email)->send(new CourseNewEditionMail($course, $edition, $siteName));
                $subscriber->forceFill(['notified_at' => now()])->save();
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Nie wysłano powiadomienia o terminie szkolenia', ['kurs' => $course->id, 'błąd' => $e->getMessage()]);
            }
        }

        $message = $sent === 0 && $failed === 0
            ? 'Nikt nie czeka na powiadomienie: wszyscy już je dostali.'
            : "Wysłano powiadomienie: {$sent}".($failed ? ", nie udało się: {$failed} (zostaną na liście, spróbuj ponownie)" : '').'.';

        return back()->with('status', $message);
    }

    public function destroy(CourseNotification $notification)
    {
        $this->authorizeSite($notification->course);
        $notification->delete();

        return back()->with('status', 'Adres usunięty z listy powiadomień.');
    }

    private function authorizeSite(Course $course): void
    {
        abort_unless($course->site_id === SiteSetting::current()->id, 404);
    }
}
