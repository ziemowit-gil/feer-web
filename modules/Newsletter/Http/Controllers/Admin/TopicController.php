<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsCategory;
use App\Models\SiteSetting;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Newsletter\Models\NewsletterTopic;
use Modules\Newsletter\Services\ContentFeeder;

/** Panel: tematy subskrypcji — CRUD, kolejność, powiązanie z kategoriami aktualności, scalanie przy usuwaniu. */
class TopicController extends Controller
{
    public function index()
    {
        $topics = NewsletterTopic::orderBy('order')->orderBy('id')->get();
        $counts = [];
        foreach ($topics as $t) {
            $counts[$t->key] = ['all' => $t->subscribersCount(), 'active' => $t->activeSubscribersCount()];
        }
        // klucze używane przez subskrybentów, ale nieznane w tabeli (po usunięciu bez scalenia)
        $known = $topics->pluck('key')->all();
        $orphans = [];
        Subscriber::query()->whereNotNull('topics')->select('topics')->chunk(1000, function ($rows) use (&$orphans, $known) {
            foreach ($rows as $r) {
                foreach ((array) $r->topics as $k) {
                    if (! in_array($k, $known, true)) {
                        $orphans[$k] = ($orphans[$k] ?? 0) + 1;
                    }
                }
            }
        });

        return view('newsletter::admin.topics.index', compact('topics', 'counts', 'orphans'));
    }

    public function create()
    {
        return $this->form(new NewsletterTopic(['is_active' => true, 'feed_sources' => ['news']]));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['key'] = $this->uniqueKey($data['key'] ?: $data['label']);
        $data['order'] = (int) NewsletterTopic::max('order') + 1;
        $topic = NewsletterTopic::create($data + ['site_id' => SiteSetting::current()->id]);

        return redirect()->route('admin.newsletter.tematy.index')->with('status', "Temat „{$topic->label}” dodany. Pojawi się w formularzach zapisu, preferencjach i kreatorze kampanii.");
    }

    public function edit(NewsletterTopic $topic)
    {
        return $this->form($topic);
    }

    public function update(Request $request, NewsletterTopic $topic)
    {
        $data = $this->validated($request, $topic);
        unset($data['key']); // klucz jest stały — siedzi w danych subskrybentów
        $topic->update($data);

        return redirect()->route('admin.newsletter.tematy.index')->with('status', 'Temat zapisany.');
    }

    /** Usuwa temat; subskrybentów można przepisać na inny temat (scalenie) albo tylko odpiąć. */
    public function destroy(Request $request, NewsletterTopic $topic)
    {
        $target = $request->input('merge_into');
        $targetTopic = $target ? NewsletterTopic::where('key', $target)->where('id', '!=', $topic->id)->first() : null;

        $n = 0;
        Subscriber::query()->whereJsonContains('topics', $topic->key)->chunkById(500, function ($subs) use ($topic, $targetTopic, &$n) {
            foreach ($subs as $s) {
                $topics = array_values(array_diff($s->topics ?? [], [$topic->key]));
                if ($targetTopic) {
                    $topics = array_values(array_unique(array_merge($topics, [$targetTopic->key])));
                }
                $s->forceFill(['topics' => $topics])->saveQuietly();
                $n++;
            }
        });
        $topic->delete();

        return redirect()->route('admin.newsletter.tematy.index')->with('status', "Temat usunięty. Zaktualizowano {$n} subskrybentów" . ($targetTopic ? " (scalono z „{$targetTopic->label}”)." : '.'));
    }

    public function reorder(Request $request)
    {
        $ids = array_map('intval', (array) $request->input('ids', []));
        foreach ($ids as $i => $id) {
            NewsletterTopic::where('id', $id)->update(['order' => $i]);
        }
        \Illuminate\Support\Facades\Cache::forget(NewsletterTopic::CACHE_KEY);

        return response()->json(['ok' => true]);
    }

    private function form(NewsletterTopic $topic)
    {
        return view('newsletter::admin.topics.form', [
            'topic'      => $topic,
            'categories' => \Illuminate\Support\Facades\Schema::hasTable('news_categories') ? NewsCategory::orderBy('order')->orderBy('name')->get(['slug', 'name', 'color']) : collect(),
            'sources'    => ContentFeeder::SOURCES,
            'others'     => NewsletterTopic::where('id', '!=', $topic->id)->orderBy('order')->get(),
        ]);
    }

    private function validated(Request $request, ?NewsletterTopic $topic = null): array
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'key' => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9_-]*$/'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:60'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'news_category_slugs' => ['nullable', 'array'],
            'news_category_slugs.*' => ['string', 'max:120'],
            'feed_sources' => ['nullable', 'array'],
            'feed_sources.*' => [Rule::in(array_keys(ContentFeeder::SOURCES))],
        ], ['key.regex' => 'Klucz może zawierać tylko małe litery, cyfry, myślnik i podkreślenie.']);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');
        $data['news_category_slugs'] = array_values($data['news_category_slugs'] ?? []);
        $data['feed_sources'] = array_values($data['feed_sources'] ?? []);
        $data['key'] = $data['key'] ?? '';

        return $data;
    }

    private function uniqueKey(string $base): string
    {
        $key = Str::slug($base, '_') ?: 'temat';
        $key = substr($key, 0, 36);
        $candidate = $key;
        $i = 2;
        while (NewsletterTopic::where('key', $candidate)->exists()) {
            $candidate = $key . '_' . $i++;
        }

        return $candidate;
    }
}
