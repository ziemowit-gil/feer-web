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

        return view('admin.courses.form', compact('course'));
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
        $course->editions()->create($this->validatedEdition($request));

        return back()->with('status', 'Termin dodany.');
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
        ]);
        $data['is_featured'] = $request->boolean('is_featured');
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
