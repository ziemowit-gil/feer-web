<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AttachesLibraryMedia;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Support\FundraisingTexts;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Panel admin: zbiórki (moduł Fundraising i darowizny) — dane zbiórki, tryb
 * płatności (Przelewy24 / wplacam.ngo.pl / link), progi kwot, grafiki
 * z biblioteki plików i nadpisania tekstów (FundraisingTexts).
 *
 * Metody: index(), create(), store(), edit(), update(), destroy(), bulk().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class CampaignController extends Controller
{
    use AttachesLibraryMedia;

    /** Grafiki zbiórki: pole formularza => kolekcja mediów. */
    private const IMAGES = [
        'image' => 'image',
        'thanks_image' => 'thanks_image',
        'thanks_email_image' => 'thanks_email_image',
    ];

    /** Lista kampanii z filtrowaniem po statusie. */
    public function index(Request $request)
    {
        $status = $request->query('status', '');

        $campaigns = Campaign::withTrashed()
            ->when(in_array($status, ['active', 'closed', 'draft'], true), fn ($q) => $q->where('status', $status)->whereNull('deleted_at'))
            ->when($status === 'published', fn ($q) => $q->where('is_published', true)->whereNull('deleted_at'))
            ->when($status === 'trashed', fn ($q) => $q->onlyTrashed())
            ->when($status === '', fn ($q) => $q->whereNull('deleted_at'))
            ->orderBy('order')
            ->orderByDesc('created_at')
            ->withCount(['donations as paid_donations_count' => fn ($q) => $q->where('status', 'paid')])
            ->get();

        return view('admin.campaigns.index', [
            'campaigns' => $campaigns,
            'status'    => $status,
        ]);
    }

    public function create()
    {
        return view('admin.campaigns.form', ['campaign' => new Campaign]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['title']);

        $campaign = Campaign::create($data);
        $this->saveImages($request, $campaign);
        $campaign->refreshCollected();

        return redirect()->route('admin.kampanie.edit', $campaign)->with('status', 'Zbiórka została utworzona.');
    }

    public function edit(Campaign $campaign)
    {
        return view('admin.campaigns.form', ['campaign' => $campaign]);
    }

    public function update(Request $request, Campaign $campaign)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['title'], $campaign->id);

        $campaign->update($data);
        $this->saveImages($request, $campaign);
        $campaign->refreshCollected();

        return redirect()->route('admin.kampanie.edit', $campaign)->with('status', 'Zbiórka została zapisana.');
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();

        return redirect()->route('admin.kampanie.index')->with('status', 'Kampania przeniesiona do kosza.');
    }

    /** Akcje zbiorcze: publish, unpublish, trash, restore. */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'in:publish,unpublish,trash,restore'],
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer'],
        ]);

        $campaigns = Campaign::withTrashed()->whereIn('id', $data['ids'])->get();

        if ($campaigns->isEmpty()) {
            return back()->with('error', 'Nie znaleziono kampanii.');
        }

        $count = $campaigns->count();

        match ($data['action']) {
            'trash'     => $campaigns->each->delete(),
            'restore'   => $campaigns->each->restore(),
            'publish'   => Campaign::whereIn('id', $campaigns->pluck('id'))->update(['is_published' => true, 'status' => 'active']),
            'unpublish' => Campaign::whereIn('id', $campaigns->pluck('id'))->update(['is_published' => false, 'status' => 'draft']),
        };

        $message = match ($data['action']) {
            'trash'     => "Przeniesiono do kosza: {$count}.",
            'restore'   => "Przywrócono: {$count}.",
            'publish'   => "Opublikowano: {$count}.",
            'unpublish' => "Cofnięto publikację: {$count}.",
        };

        return back()->with('status', $message);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'slug'             => ['nullable', 'string', 'max:255'],
            'status'           => ['required', Rule::in(array_keys(Campaign::STATUSES))],
            'excerpt'          => ['nullable', 'string', 'max:400'],
            'content'          => ['nullable', 'string'],
            'goal_amount'      => ['required', 'integer', 'min:0', 'max:100000000'],
            'external_amount'  => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'amount_options'   => ['nullable', 'string', 'max:100', 'regex:/^[0-9\s,;]*$/'],
            'payment_mode'     => ['required', Rule::in(array_keys(Campaign::PAYMENT_MODES))],
            'wplacam_url'      => ['nullable', 'required_if:payment_mode,wplacam', 'url', 'max:500', function ($attribute, $value, $fail) {
                if (filled($value) && ! Campaign::isWplacamUrl($value)) {
                    $fail('Podaj adres zbiórki w serwisie wplacam.ngo.pl (https://wplacam.ngo.pl/…).');
                }
            }],
            'wplacam_display'  => ['nullable', Rule::in(array_keys(Campaign::WPLACAM_DISPLAYS))],
            'donation_url'     => ['nullable', 'required_if:payment_mode,external', 'url', 'max:500'],
            'starts_at'        => ['nullable', 'date'],
            'ends_at'          => ['nullable', 'date', 'after_or_equal:starts_at'],
            'order'            => ['nullable', 'integer', 'min:0'],
            'image_alt'        => ['nullable', 'string', 'max:255'],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'image'            => ['nullable', 'image', 'max:4096'],
            'image_library_id' => $this->libraryMediaRule(),
            'thanks_image'     => ['nullable', 'image', 'max:4096'],
            'thanks_image_library_id' => $this->libraryMediaRule(),
            'thanks_email_image' => ['nullable', 'image', 'max:2048'],
            'thanks_email_image_library_id' => $this->libraryMediaRule(),
        ] + FundraisingTexts::rules(), [
            'wplacam_url.required_if' => 'Podaj adres zbiórki w wplacam.ngo.pl.',
            'donation_url.required_if' => 'Podaj adres zewnętrznej zbiórki.',
        ]);

        $data['slug'] = trim($data['slug'] ?? '');
        $data['order'] = $data['order'] ?? 0;
        $data['external_amount'] = (int) ($data['external_amount'] ?? 0);
        $data['wplacam_display'] = $data['wplacam_display'] ?? 'redirect';
        $data['is_featured'] = $request->boolean('is_featured');
        $data['allow_custom_amount'] = $request->boolean('allow_custom_amount');
        $data['allow_recurring'] = $request->boolean('allow_recurring');
        $data['show_recent_donations'] = $request->boolean('show_recent_donations');
        $data['texts'] = FundraisingTexts::clean($request->input('texts', [])) ?: null;

        unset($data['image'], $data['image_library_id'], $data['thanks_image'], $data['thanks_image_library_id'],
            $data['thanks_email_image'], $data['thanks_email_image_library_id']);

        return $data;
    }

    /** Grafiki: wgranie z dysku, wybór z biblioteki plików albo usunięcie. */
    private function saveImages(Request $request, Campaign $campaign): void
    {
        foreach (self::IMAGES as $field => $collection) {
            if ($request->hasFile($field)) {
                $campaign->addMediaFromRequest($field)->toMediaCollection($collection);
            } elseif (! $this->attachFromLibrary($request, $campaign, $field, $collection) && $request->boolean('remove_'.$field)) {
                $campaign->clearMediaCollection($collection);
            }
        }
    }

    private function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'kampania';
        $slug = $base;
        $suffix = 2;

        while (Campaign::withTrashed()->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
