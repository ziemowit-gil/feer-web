<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Modules\Newsletter\Models\NewsletterList;
use Modules\Newsletter\Models\NewsletterSegment;
use Modules\Newsletter\Services\SegmentResolver;

class SegmentController extends Controller
{
    public function index()
    {
        return view('newsletter::admin.segments.index', ['segments' => NewsletterSegment::orderBy('name')->get()]);
    }

    public function create()
    {
        return $this->form(new NewsletterSegment(['rules' => ['match' => 'all', 'rules' => [['field' => 'status', 'op' => 'eq', 'value' => 'confirmed']]]]));
    }

    public function store(Request $request)
    {
        $segment = NewsletterSegment::create($this->validated($request) + ['site_id' => SiteSetting::current()->id]);
        $segment->refreshCount();

        return redirect()->route('admin.newsletter.segmenty.index')->with('status', "Segment „{$segment->name}” utworzony ({$segment->cached_count} osób).");
    }

    public function edit(NewsletterSegment $segment)
    {
        return $this->form($segment);
    }

    public function update(Request $request, NewsletterSegment $segment)
    {
        $segment->update($this->validated($request));
        $segment->refreshCount();

        return redirect()->route('admin.newsletter.segmenty.index')->with('status', "Segment zapisany ({$segment->cached_count} osób).");
    }

    public function destroy(NewsletterSegment $segment)
    {
        $segment->delete();

        return redirect()->route('admin.newsletter.segmenty.index')->with('status', 'Segment usunięty.');
    }

    /** Liczność „na żywo" dla reguł z formularza (JSON). */
    public function count(Request $request, SegmentResolver $resolver)
    {
        $rules = $this->rulesFrom($request);

        return response()->json(['count' => $resolver->query($rules)->count()]);
    }

    private function form(NewsletterSegment $segment)
    {
        return view('newsletter::admin.segments.form', [
            'segment'   => $segment,
            'fields'    => SegmentResolver::FIELDS,
            'operators' => SegmentResolver::OPERATORS,
            'topics'    => Subscriber::availableTopics(),
            'statuses'  => Subscriber::STATUSES,
            'channels'  => Subscriber::CHANNELS,
            'lists'     => NewsletterList::orderBy('name')->pluck('name', 'id'),
            'sites'     => SiteSetting::query()->pluck('site_name', 'id'),
        ]);
    }

    private function validated(Request $request): array
    {
        $request->validate(['name' => ['required', 'string', 'max:120']]);

        return ['name' => $request->input('name'), 'rules' => $this->rulesFrom($request)];
    }

    private function rulesFrom(Request $request): array
    {
        $rules = [];
        foreach ((array) $request->input('rules', []) as $r) {
            if (empty($r['field']) || ! array_key_exists($r['field'], SegmentResolver::FIELDS)) {
                continue;
            }
            $value = $r['value'] ?? null;
            if (is_string($value) && in_array($r['op'] ?? '', ['in', 'not_in', 'contains', 'not_contains'], true)) {
                $value = array_values(array_filter(array_map('trim', explode(',', $value))));
            }
            $rules[] = ['field' => $r['field'], 'op' => $r['op'] ?? 'eq', 'value' => $value];
        }

        return ['match' => $request->input('match', 'all') === 'any' ? 'any' : 'all', 'rules' => $rules];
    }
}
