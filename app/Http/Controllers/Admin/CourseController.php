<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEdition;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Panel: oferta szkoleń i zajęć (szkolenia i ich terminy). Zapisami uczestników zajmuje się CourseRegistrationController.
 *
 * Metody: index(), create(), store(), edit(), update(), destroy(), storeEdition(), updateEdition(), destroyEdition().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::forCurrentSite()
            ->withCount('editions')
            ->orderByRaw("case status when 'active' then 0 when 'draft' then 1 else 2 end")
            ->orderBy('order')->orderBy('title')
            ->get();

        return view('admin.courses.index', compact('courses'));
    }

    public function create()
    {
        return view('admin.courses.form', ['course' => new Course(['format' => 'stationary', 'status' => 'draft', 'category' => 'other'])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug(($data['slug'] ?? '') ?: $data['title']);

        $course = Course::create($data);

        return redirect()->route('admin.kursy.edit', $course)->with('status', 'Szkolenie dodane. Dodaj teraz terminy.');
    }

    public function edit(Course $course)
    {
        $this->authorizeSite($course);
        $course->load(['editions' => fn ($q) => $q->withCount([
            'registrations as seats_taken' => fn ($r) => $r->whereIn('status', CourseEdition::SEAT_STATUSES),
            'registrations as waitlist_count' => fn ($r) => $r->where('status', 'waitlist'),
        ])]);

        $notifications = \Illuminate\Support\Facades\Schema::hasTable('course_notifications')
            ? \App\Models\CourseNotification::where('course_id', $course->id)->orderBy('created_at')->get()
            : collect();

        return view('admin.courses.form', compact('course', 'notifications'));
    }

    public function update(Request $request, Course $course)
    {
        $this->authorizeSite($course);
        $data = $this->validated($request, $course);
        $data['slug'] = $this->uniqueSlug(($data['slug'] ?? '') ?: $data['title'], $course->id);

        $course->update($data);

        return back()->with('status', 'Zapisano szkolenie.');
    }

    public function destroy(Course $course)
    {
        $this->authorizeSite($course);
        $course->delete();

        return redirect()->route('admin.kursy.index')->with('status', 'Szkolenie usunięte razem z terminami i zapisami.');
    }

    public function storeEdition(Request $request, Course $course)
    {
        $this->authorizeSite($course);
        $edition = $course->editions()->create($this->validatedEdition($request));

        $waiting = $edition->acceptsRegistrations() && \Illuminate\Support\Facades\Schema::hasTable('course_notifications')
            ? \App\Models\CourseNotification::where('course_id', $course->id)->whereNull('notified_at')->count()
            : 0;

        return back()->with('status', 'Termin dodany.'.($waiting ? " {$waiting} os. czeka na powiadomienie o terminie (sekcja „Powiadomienia o terminie” niżej)." : ''));
    }

    public function updateEdition(Request $request, CourseEdition $edition)
    {
        $this->authorizeSite($edition->course);
        $edition->update($this->validatedEdition($request));

        return back()->with('status', 'Termin zapisany.');
    }

    public function destroyEdition(CourseEdition $edition)
    {
        $this->authorizeSite($edition->course);
        $edition->delete();

        return back()->with('status', 'Termin usunięty razem z zapisami.');
    }

    /** Rekord musi należeć do aktywnej witryny panelu (witryny w sieci mają osobne oferty). */
    private function authorizeSite(Course $course): void
    {
        abort_unless($course->site_id === \App\Models\SiteSetting::current()->id, 404);
    }

    private function validated(Request $request, ?Course $course = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'lead' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:100000'],
            'category' => ['required', Rule::in(array_keys(Course::CATEGORIES))],
            'format' => ['required', Rule::in(array_keys(Course::FORMATS))],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'status' => ['required', Rule::in(array_keys(Course::STATUSES))],
            'order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'facilitator_id' => ['nullable', 'integer', 'exists:facilitators,id'],
            'duration' => ['nullable', 'string', 'max:120'],
            'level' => ['nullable', 'string', 'max:60'],
            'certificate' => ['nullable', 'string', 'max:200'],
            'price_note' => ['nullable', 'string', 'max:300'],
            'funding' => ['nullable', 'string', 'max:300'],
            'prerequisites' => ['nullable', 'string', 'max:3000'],
            'terms' => ['nullable', 'string', 'max:50000'],
            'audience' => ['nullable', 'array'], 'audience.*.text' => ['nullable', 'string', 'max:200'],
            'outcomes' => ['nullable', 'array'], 'outcomes.*.text' => ['nullable', 'string', 'max:300'],
            'program' => ['nullable', 'array'], 'program.*.title' => ['nullable', 'string', 'max:160'], 'program.*.hours' => ['nullable', 'string', 'max:40'], 'program.*.text' => ['nullable', 'string', 'max:1500'],
            'faq' => ['nullable', 'array'], 'faq.*.question' => ['nullable', 'string', 'max:300'], 'faq.*.answer' => ['nullable', 'string', 'max:2000'],
        ]);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['terms'] = filled($data['terms'] ?? null) ? \App\Support\SafeHtml::clean($data['terms']) : null;
        foreach (['audience', 'outcomes'] as $list) {
            $data[$list] = collect($request->input($list, []))->map(fn ($r) => ['text' => trim((string) ($r['text'] ?? ''))])->filter(fn ($r) => $r['text'] !== '')->values()->all() ?: null;
        }
        $data['program'] = collect($request->input('program', []))->map(fn ($r) => ['title' => trim((string) ($r['title'] ?? '')), 'hours' => trim((string) ($r['hours'] ?? '')), 'text' => trim((string) ($r['text'] ?? ''))])->filter(fn ($r) => $r['title'] !== '')->values()->all() ?: null;
        $data['faq'] = collect($request->input('faq', []))->map(fn ($r) => ['question' => trim((string) ($r['question'] ?? '')), 'answer' => trim((string) ($r['answer'] ?? ''))])->filter(fn ($r) => $r['question'] !== '')->values()->all() ?: null;
        // Do czasu migracji nowe kolumny są pomijane, żeby zapis nie padał.
        foreach (['facilitator_id', 'duration', 'level', 'certificate', 'price_note', 'funding', 'prerequisites', 'terms', 'audience', 'outcomes', 'program', 'faq'] as $column) {
            if (! \Illuminate\Support\Facades\Schema::hasColumn('courses', $column)) {
                unset($data[$column]);
            }
        }
        $data['order'] = (int) ($data['order'] ?? 0);
        $data['description'] = filled($data['description'] ?? null) ? \App\Support\SafeHtml::clean($data['description']) : null;

        return $data;
    }

    private function validatedEdition(Request $request): array
    {
        $data = $request->validate([
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'schedule' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_url' => ['nullable', 'url', 'max:255'],
            'instructor' => ['nullable', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:0', 'max:10000'],
            'enrollment_status' => ['required', Rule::in(array_keys(CourseEdition::ENROLLMENT))],
        ]);

        return $data;
    }

    /** Unikalny slug w obrębie witryny (z kolejnym numerem przy kolizji). */
    private function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'szkolenie';
        $slug = $base;
        $i = 2;
        while (Course::forCurrentSite()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
