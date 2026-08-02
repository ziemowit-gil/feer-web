<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use App\Models\HeroSlide;
use App\Models\MediaFolder;
use App\Models\MediaLibrary;
use App\Models\News;
use App\Models\Partner;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class MediaLibraryController extends Controller
{
    /**
     * Maps a morphed model class to how it should be labelled and linked
     * back to from the media browser, and which module governs access to
     * it (null means admin-only, since it's not a toggleable content module).
     */
    private const OWNERS = [
        GalleryImage::class => ['label' => 'Zdjęcie w galerii', 'route' => 'admin.galeria.edit', 'param' => 'galleryImage', 'module' => 'gallery'],
        HeroSlide::class => ['label' => 'Slajd hero', 'route' => 'admin.hero.edit', 'param' => 'heroSlide', 'module' => 'hero'],
        Project::class => ['label' => 'Projekt', 'route' => 'admin.projekty.edit', 'param' => 'project', 'module' => 'projects'],
        News::class => ['label' => 'Aktualność', 'route' => 'admin.newsy.edit', 'param' => 'news', 'module' => 'news'],
        Partner::class => ['label' => 'Partner', 'route' => 'admin.partnerzy.edit', 'param' => 'partner', 'module' => 'partners'],
        SiteSetting::class => ['label' => 'Ustawienia strony', 'route' => 'admin.ustawienia.edit', 'param' => null, 'module' => null],
        MediaLibrary::class => ['label' => 'Biblioteka plików', 'route' => null, 'param' => null, 'module' => null],
    ];

    /**
     * How to derive a screen-reader-friendly description of each owner's
     * media from its own fields, since the file name alone (a generated
     * hash) tells a screen reader user nothing about what the image shows.
     */
    private const ALT_SOURCES = [
        GalleryImage::class => 'caption',
        HeroSlide::class => 'title',
        Project::class => 'image_alt_or_title',
        News::class => 'image_alt_or_title',
        Partner::class => 'name',
        SiteSetting::class => 'site_name',
        MediaLibrary::class => null,
    ];

    public function index(Request $request)
    {
        $folder = $request->filled('folder') ? MediaFolder::findOrFail($request->integer('folder')) : null;
        $showArchived = $request->boolean('archived');
        $search = $request->input('q');
        $tag = $request->input('tag');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $authorId = $request->filled('author') ? (int) $request->input('author') : null;
        $withArchived = $request->boolean('with_archived');

        $media = Media::query()
            ->with('uploadedBy:id,name')
            ->whereIn('model_type', $this->accessibleModelTypes())
            ->where('media_folder_id', $folder?->id)
            ->when(
                ! $withArchived,
                fn ($query) => $showArchived
                    ? $query->whereNotNull('archived_at')
                    : $query->whereNull('archived_at')
            )
            ->when($search, fn ($query) => $query->where('file_name', 'like', '%'.addcslashes($search, '%_').'%'))
            ->when($tag, fn ($query) => $query->where('custom_properties', 'like', '%"'.addslashes($tag).'"%'))
            ->when($dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->when($authorId, fn ($query) => $query->where('uploaded_by_user_id', $authorId))
            ->latest()
            ->paginate(36)
            ->withQueryString()
            ->through(function (Media $media) {
                $model = $media->model_type::find($media->model_id);

                $isImage = str_starts_with($media->mime_type, 'image/');
                $isWebp  = $media->mime_type === 'image/webp';

                $hasWebpConversion = false;
                if ($isImage && ! $isWebp) {
                    try {
                        $hasWebpConversion = file_exists($media->getPath('webp'));
                    } catch (\Throwable) {
                        // model nie ma zarejestrowanej konwersji 'webp'
                    }
                }

                return [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'is_image' => $isImage,
                    'is_webp' => $isWebp,
                    'has_webp_conversion' => $hasWebpConversion,
                    'file_name' => $media->file_name,
                    'size' => $media->human_readable_size,
                    'mime_type' => $media->mime_type,
                    'collection' => $media->collection_name,
                    'created_at' => $media->created_at,
                    'archived' => $media->archived_at !== null,
                    'owner' => $this->describeOwner($media, $model),
                    'alt' => $this->describeAlt($media, $model),
                    'tags' => $media->getCustomProperty('tags', []),
                    'uploader' => $media->uploadedBy?->name,
                ];
            });

        $allTags = Media::query()
            ->whereIn('model_type', $this->accessibleModelTypes())
            ->whereNot('custom_properties', '[]')
            ->whereNotNull('custom_properties')
            ->get(['custom_properties'])
            ->flatMap(fn ($m) => $m->getCustomProperty('tags', []))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $uploaders = Media::query()
            ->whereIn('model_type', $this->accessibleModelTypes())
            ->whereNotNull('uploaded_by_user_id')
            ->with('uploadedBy:id,name')
            ->get(['uploaded_by_user_id'])
            ->map(fn ($m) => $m->uploadedBy)
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        return view('admin.media.index', [
            'media' => $media,
            'folder' => $folder,
            'showArchived' => $showArchived,
            'breadcrumbs' => $folder ? $folder->path() : [],
            'allFolders' => MediaFolder::orderBy('name')->get(),
            'folderTree' => $this->folderTree(),
            'allTags' => $allTags,
            'uploaders' => $uploaders,
            'currentSearch' => $search,
            'currentTag' => $tag,
            'currentDateFrom' => $dateFrom,
            'currentDateTo' => $dateTo,
            'currentAuthor' => $authorId,
            'withArchived' => $withArchived,
        ]);
    }

    public function updateTags(Request $request, Media $media)
    {
        abort_unless(in_array($media->model_type, $this->accessibleModelTypes()), 403);

        $data = $request->validate([
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:60'],
        ]);

        $tags = array_values(array_filter(
            array_unique(array_map('trim', $data['tags'] ?? [])),
            fn ($t) => $t !== ''
        ));

        if (empty($tags)) {
            $media->forgetCustomProperty('tags');
        } else {
            $media->setCustomProperty('tags', $tags);
        }

        $media->save();

        return redirect()->back()->with('status', 'Tagi zostały zaktualizowane.');
    }

    /**
     * All folders grouped by their parent id (root folders under the '' key),
     * so the sidebar tree partial can render the whole hierarchy recursively
     * without an N+1 query per branch. Each folder carries a count of its
     * non-archived files for the badge shown next to it.
     */
    private function folderTree()
    {
        return MediaFolder::query()
            ->withCount(['media' => fn ($query) => $query->whereNull('archived_at')])
            ->orderBy('name')
            ->get()
            ->groupBy('parent_id');
    }

    /**
     * Lightweight JSON list of every accessible image, for the "insert from
     * library" picker in the content editor. Not folder-scoped — the editor
     * just needs to find any image regardless of how it's organized.
     */
    public function imagesJson()
    {
        $images = Media::query()
            ->whereIn('model_type', $this->accessibleModelTypes())
            ->where('mime_type', 'like', 'image/%')
            ->whereNull('archived_at')
            ->latest()
            ->limit(300)
            ->get()
            ->map(function (Media $media) {
                $model = $media->model_type::find($media->model_id);

                return [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'file_name' => $media->file_name,
                    'alt' => $this->describeAlt($media, $model),
                ];
            });

        return response()->json($images);
    }

    /**
     * Proxies a photo search to the Unsplash API so the access key never
     * reaches the browser. Requires UNSPLASH_ACCESS_KEY to be configured.
     */
    public function unsplashSearch(Request $request)
    {
        $accessKey = SiteSetting::current()->unsplashAccessKey();

        abort_unless($accessKey, 501, 'Integracja z Unsplash nie jest skonfigurowana — brak UNSPLASH_ACCESS_KEY.');

        $data = $request->validate([
            'q' => ['required', 'string', 'max:100'],
        ]);

        $response = Http::withHeaders(['Authorization' => "Client-ID {$accessKey}"])
            ->get('https://api.unsplash.com/search/photos', [
                'query' => $data['q'],
                'per_page' => 24,
                'orientation' => 'landscape',
            ]);

        abort_unless($response->successful(), 502, 'Nie udało się połączyć z Unsplash.');

        $results = collect($response->json('results'))->map(fn ($photo) => [
            'id' => $photo['id'],
            'thumb_url' => $photo['urls']['small'],
            'full_url' => $photo['urls']['regular'],
            'alt' => $photo['alt_description'] ?: $photo['description'] ?: 'Zdjęcie z Unsplash',
            'author_name' => $photo['user']['name'],
            'author_url' => $photo['user']['links']['html'].'?utm_source=feer&utm_medium=referral',
            'download_location' => $photo['links']['download_location'],
        ]);

        return response()->json($results);
    }

    /**
     * Downloads a chosen Unsplash photo server-side and saves it into the
     * media library like any other upload, crediting the photographer in
     * the alt text per Unsplash's API guidelines. Also pings the photo's
     * download_location, which Unsplash requires whenever a photo is used.
     */
    public function unsplashImport(Request $request)
    {
        $accessKey = SiteSetting::current()->unsplashAccessKey();

        abort_unless($accessKey, 501, 'Integracja z Unsplash nie jest skonfigurowana — brak UNSPLASH_ACCESS_KEY.');

        $data = $request->validate([
            'full_url' => ['required', 'url'],
            'download_location' => ['required', 'url'],
            'author_name' => ['required', 'string', 'max:255'],
            'folder_id' => ['nullable', 'exists:media_folders,id'],
        ]);

        Http::withHeaders(['Authorization' => "Client-ID {$accessKey}"])->get($data['download_location']);

        $media = MediaLibrary::instance()
            ->addMediaFromUrl($data['full_url'])
            ->usingFileName(Str::random(20).'.jpg')
            ->withCustomProperties(['unsplash_author' => $data['author_name']])
            ->toMediaCollection('files');

        $media->update([
            'uploaded_by_user_id' => auth()->id(),
            ...(!empty($data['folder_id']) ? ['media_folder_id' => $data['folder_id']] : []),
        ]);

        return response()->json([
            'id' => $media->id,
            'url' => $media->getUrl(),
            'file_name' => $media->file_name,
            'alt' => 'Zdjęcie: '.$data['author_name'].' / Unsplash',
        ]);
    }

    /**
     * Downloads an image from a public OneDrive sharing link and saves it into
     * the media library. Uses the Microsoft Graph sharing API (no OAuth needed
     * for "anyone with the link" personal OneDrive files). SharePoint/business
     * accounts require the link to be set to public — internal-only links will
     * be rejected with a 422.
     */
    /**
     * Receives a single file via XHR/fetch (drag-drop or paste in the content
     * editor) and saves it to the media library. Returns JSON in a format that
     * both TinyMCE (location) and CKEditor SimpleUploadAdapter (url) expect.
     */
    public function uploadAjax(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,bmp,avif,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,odt,ods,odp,zip', 'max:20480'],
        ], [], ['file' => 'plik']);

        $media = MediaLibrary::instance()
            ->addMedia($request->file('file'))
            ->toMediaCollection('files');

        $media->update(['uploaded_by_user_id' => auth()->id()]);

        $url = $media->getUrl();

        return response()->json([
            'location' => $url,
            'url' => $url,
            'id' => $media->id,
            'file_name' => $media->file_name,
        ]);
    }

    /**
     * Downloads an image from OneDrive and saves it into the media library.
     * Two modes:
     *  - download_url: pre-authenticated URL returned by the OneDrive File Picker SDK
     *    (no Graph API call needed — the picker already resolved auth)
     *  - url: a public sharing link (1drv.ms or similar) — converted via the Graph
     *    Shares API, which works without OAuth for "anyone with the link" files
     */
    public function oneDriveImport(Request $request)
    {
        $data = $request->validate([
            'download_url' => ['nullable', 'url', 'max:2000'],
            'url' => ['nullable', 'url', 'max:1000'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        abort_unless(!empty($data['download_url']) || !empty($data['url']), 422,
            'Podaj adres URL pliku lub link udostępniania OneDrive.');

        if (!empty($data['download_url'])) {
            $downloadUrl = $data['download_url'];
            $originalName = $data['name'] ?? null;
        } else {
            $shareUrl = $data['url'];
            $encoded = 'u!'.rtrim(strtr(base64_encode($shareUrl), '+/', '-_'), '=');

            $metaResponse = Http::timeout(15)->get(
                "https://api.onedrive.com/v1.0/shares/{$encoded}/root",
                ['$select' => 'name,file,@microsoft.graph.downloadUrl']
            );

            abort_unless($metaResponse->successful(), 422,
                'Nie można uzyskać dostępu do pliku OneDrive. Upewnij się, że link jest udostępniony publicznie.');

            $meta = $metaResponse->json();
            abort_unless(isset($meta['file']), 422, 'Podany link nie wskazuje na plik.');

            $mimeType = $meta['file']['mimeType'] ?? '';
            abort_unless(str_starts_with($mimeType, 'image/'), 422,
                'Plik nie jest obrazem. Obsługiwane formaty: JPEG, PNG, GIF, WebP i inne obrazy.');

            $downloadUrl = $meta['@microsoft.graph.downloadUrl'] ?? null;
            abort_unless($downloadUrl, 422, 'Nie udało się pobrać adresu pliku z OneDrive.');
            $originalName = $meta['name'] ?? null;
        }

        $ext = $originalName ? pathinfo($originalName, PATHINFO_EXTENSION) : 'jpg';
        $fileName = Str::random(20).($ext ? '.'.$ext : '');

        $media = MediaLibrary::instance()
            ->addMediaFromUrl($downloadUrl)
            ->usingFileName($fileName)
            ->withCustomProperties(['onedrive_name' => $originalName])
            ->toMediaCollection('files');

        $media->update(['uploaded_by_user_id' => auth()->id()]);

        return response()->json([
            'id' => $media->id,
            'url' => $media->getUrl(),
            'file_name' => $media->file_name,
            'alt' => $originalName ? pathinfo($originalName, PATHINFO_FILENAME) : '',
        ]);
    }

    /**
     * Uploads one or more files into the library in a single request. The
     * upload form sends `files[]`, so a user can pick or drag several files
     * at once; each is added to the standalone library and dropped into the
     * current folder.
     */
    public function store(Request $request)
    {
        $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'max:10240'],
            'folder_id' => ['nullable', 'exists:media_folders,id'],
        ], [], ['files.*' => 'plik']);

        $folderId = $request->input('folder_id') ?: null;
        $library = MediaLibrary::instance();
        $count = 0;

        $userId = auth()->id();

        foreach ($request->file('files', []) as $file) {
            $media = $library->addMedia($file)->toMediaCollection('files');

            $media->update(array_filter([
                'uploaded_by_user_id' => $userId,
                'media_folder_id' => $folderId,
            ]));

            $count++;
        }

        return redirect()->route('admin.multimedia.index', ['folder' => $folderId])
            ->with('status', $count === 1 ? 'Plik został przesłany.' : "Przesłano plików: {$count}.");
    }

    /**
     * Applies a single action to a batch of selected files at once — delete,
     * archive, restore, or move to a folder. Only files whose owning model the
     * user may manage are touched; ids they aren't allowed to see are silently
     * dropped. Deletion goes through the model so Spatie also removes the file
     * from disk; the reversible actions use a mass update.
     */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'in:delete,archive,restore,move'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'folder_id' => ['nullable', 'exists:media_folders,id'],
        ]);

        $items = Media::query()
            ->whereIn('id', $data['ids'])
            ->whereIn('model_type', $this->accessibleModelTypes())
            ->get();

        if ($items->isEmpty()) {
            return redirect()->back()->with('error', 'Nie znaleziono plików do przetworzenia.');
        }

        $count = $items->count();
        $ids = $items->pluck('id');

        match ($data['action']) {
            'delete' => $items->each->delete(),
            'archive' => Media::whereIn('id', $ids)->update(['archived_at' => now()]),
            'restore' => Media::whereIn('id', $ids)->update(['archived_at' => null]),
            'move' => Media::whereIn('id', $ids)->update(['media_folder_id' => $data['folder_id'] ?? null]),
        };

        $message = match ($data['action']) {
            'delete' => "Usunięto plików: {$count}.",
            'archive' => "Schowano do archiwum plików: {$count}.",
            'restore' => "Przywrócono z archiwum plików: {$count}.",
            'move' => "Przeniesiono plików: {$count}.",
        };

        return redirect()->back()->with('status', $message);
    }

    /**
     * Streams a ZIP of the accessible, non-archived files as a backup/transfer
     * bundle. When a folder is given the export is scoped to that folder and
     * its descendants, and the folder itself becomes the archive root; the
     * on-disk folder structure is mirrored inside the ZIP so an import can
     * later recreate it.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $folder = $request->filled('folder') ? MediaFolder::findOrFail($request->integer('folder')) : null;

        $scopedFolderIds = $folder ? $this->descendantFolderIds($folder) : null;

        $query = Media::query()
            ->whereIn('model_type', $this->accessibleModelTypes())
            ->whereNull('archived_at')
            ->when($scopedFolderIds !== null, fn ($q) => $q->whereIn('media_folder_id', $scopedFolderIds));

        abort_if($query->clone()->doesntExist(), 404, 'Brak plików do wyeksportowania.');

        $folders = MediaFolder::all()->keyBy('id');

        $zipPath = $this->buildZip(function (ZipArchive $zip, array &$used) use ($query, $folders, $folder) {
            $query->orderBy('id')->chunk(100, function ($chunk) use ($zip, $folders, $folder, &$used) {
                foreach ($chunk as $media) {
                    $dir = $this->folderPath($media->media_folder_id, $folder?->id, $folders);
                    $this->addMediaToZip($zip, $media, $dir, $used);
                }
            });
        });

        $name = 'multimedia-'.($folder ? Str::slug($folder->name).'-' : '').now()->format('Y-m-d').'.zip';

        return response()->download($zipPath, $name, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    /**
     * Streams a ZIP of just the files the user ticked in the browser, mirroring
     * each file's full folder path (from the library root) inside the archive
     * so an ad-hoc selection spanning several folders stays organised. Only
     * accessible files are included.
     */
    public function exportSelected(Request $request): BinaryFileResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $media = Media::query()
            ->whereIn('id', $data['ids'])
            ->whereIn('model_type', $this->accessibleModelTypes())
            ->orderBy('id')
            ->get();

        abort_if($media->isEmpty(), 404, 'Brak plików do wyeksportowania.');

        $folders = MediaFolder::all()->keyBy('id');

        $zipPath = $this->buildZip(function (ZipArchive $zip, array &$used) use ($media, $folders) {
            foreach ($media as $item) {
                $dir = $this->folderPath($item->media_folder_id, null, $folders);
                $this->addMediaToZip($zip, $item, $dir, $used);
            }
        });

        $name = 'multimedia-zaznaczone-'.now()->format('Y-m-d').'.zip';

        return response()->download($zipPath, $name, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    /**
     * Opens a fresh temp ZIP, lets the caller add entries to it (sharing the
     * "unique entry name" bookkeeping via the by-reference $used map), then
     * closes it and returns the path on disk for streaming.
     */
    private function buildZip(\Closure $addEntries): string
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'media-export-').'.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $used = [];
        $addEntries($zip, $used);

        $zip->close();

        return $zipPath;
    }

    /**
     * Adds one media file to the ZIP at the given folder path, skipping files
     * whose source is missing on disk and de-duplicating colliding names.
     */
    private function addMediaToZip(ZipArchive $zip, Media $media, string $dir, array &$used): void
    {
        $source = $media->getPath();

        if (! is_file($source)) {
            return;
        }

        $entry = $this->uniqueEntry(trim(($dir !== '' ? $dir.'/' : '').$media->file_name, '/'), $used);

        $zip->addFile($source, $entry);
    }

    /**
     * Imports every file inside an uploaded ZIP into the library as standalone
     * files, recreating the archive's folder structure underneath the chosen
     * folder. Content is extracted to a temp file and handed to the media
     * library rather than written to a path derived from the entry name, so a
     * crafted archive cannot escape its directory (zip-slip). Executable/script
     * files are rejected since library files are served from the public disk.
     */
    public function import(Request $request)
    {
        $data = $request->validate([
            'archive' => ['required', 'file', 'mimes:zip', 'max:512000'],
            'folder_id' => ['nullable', 'exists:media_folders,id'],
        ], [], ['archive' => 'archiwum ZIP']);

        $zip = new ZipArchive;

        if ($zip->open($request->file('archive')->getRealPath()) !== true) {
            return redirect()->back()->withErrors(['archive' => 'Nie udało się otworzyć archiwum ZIP.']);
        }

        $library = MediaLibrary::instance();
        $baseFolderId = $data['folder_id'] ?? null;
        $folderCache = [];
        $imported = 0;
        $skipped = 0;
        $userId = auth()->id();

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->statIndex($i)['name'];
            $baseName = basename($entryName);

            // Skip directory entries, dotfiles, macOS resource forks and
            // anything with a script/executable extension.
            if (str_ends_with($entryName, '/')
                || $baseName === ''
                || str_starts_with($baseName, '.')
                || str_contains($entryName, '__MACOSX/')
                || $this->isBlockedFile($baseName)) {
                $skipped++;

                continue;
            }

            $stream = $zip->getStream($entryName);

            if (! $stream) {
                $skipped++;

                continue;
            }

            $tmp = tempnam(sys_get_temp_dir(), 'media-import-');
            file_put_contents($tmp, $stream);
            fclose($stream);

            try {
                $folderId = $this->resolveImportFolder(dirname($entryName), $baseFolderId, $folderCache);

                $media = $library->addMedia($tmp)
                    ->usingFileName($baseName)
                    ->toMediaCollection('files');

                $media->update(array_filter([
                    'uploaded_by_user_id' => $userId,
                    'media_folder_id' => $folderId,
                ]));

                $imported++;
            } catch (\Throwable) {
                @unlink($tmp);
                $skipped++;
            }
        }

        $zip->close();

        return redirect()->route('admin.multimedia.index', ['folder' => $baseFolderId])
            ->with('status', "Zaimportowano plików: {$imported}."
                .($skipped > 0 ? " Pominięto: {$skipped} (foldery, pliki ukryte lub niedozwolone)." : ''));
    }

    public function move(Request $request, Media $media)
    {
        abort_unless(in_array($media->model_type, $this->accessibleModelTypes()), 403);

        $data = $request->validate([
            'folder_id' => ['nullable', 'exists:media_folders,id'],
        ]);

        $media->update(['media_folder_id' => $data['folder_id'] ?? null]);

        return redirect()->back()->with('status', 'Plik został przeniesiony.');
    }

    /**
     * Hides a file from the library (and the editor's image picker) without
     * deleting it — a soft "archive" the user can undo via restore().
     */
    public function archive(Media $media)
    {
        abort_unless(in_array($media->model_type, $this->accessibleModelTypes()), 403);

        $media->update(['archived_at' => now()]);

        return redirect()->back()->with('status', 'Plik został schowany do archiwum.');
    }

    /**
     * Brings a previously archived file back into the active library.
     */
    public function restore(Media $media)
    {
        abort_unless(in_array($media->model_type, $this->accessibleModelTypes()), 403);

        $media->update(['archived_at' => null]);

        return redirect()->back()->with('status', 'Plik został przywrócony z archiwum.');
    }

    public function destroy(Media $media)
    {
        abort_unless(in_array($media->model_type, $this->accessibleModelTypes()), 403);

        $media->delete();

        return redirect()->route('admin.multimedia.index')->with('status', 'Plik został usunięty.');
    }

    public function storeFolder(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:media_folders,id'],
        ]);

        $folder = MediaFolder::create($data);

        return redirect()->route('admin.multimedia.index', ['folder' => $data['parent_id'] ?? null])
            ->with('status', "Folder „{$folder->name}” został utworzony.");
    }

    public function updateFolder(Request $request, MediaFolder $folder)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $folder->update($data);

        return redirect()->route('admin.multimedia.index', ['folder' => $folder->parent_id])
            ->with('status', 'Folder został zmieniony.');
    }

    /**
     * Removes a folder, moving its files and direct subfolders up to the
     * parent (or the root) first so nothing is lost — the files here may be
     * live content images, so we never delete them along with the folder.
     */
    public function destroyFolder(MediaFolder $folder)
    {
        $parentId = $folder->parent_id;
        $name = $folder->name;
        $hadContent = $folder->media()->exists() || $folder->children()->exists();

        $folder->media()->update(['media_folder_id' => $parentId]);
        $folder->children()->update(['parent_id' => $parentId]);

        $folder->delete();

        return redirect()->route('admin.multimedia.index', ['folder' => $parentId])
            ->with('status', $hadContent
                ? "Folder „{$name}” został usunięty, a jego zawartość przeniesiono wyżej."
                : "Folder „{$name}” został usunięty.");
    }

    /**
     * The id of a folder plus every folder nested beneath it, so an export
     * scoped to one folder can gather files from the whole subtree in a
     * single query.
     */
    private function descendantFolderIds(MediaFolder $folder): array
    {
        $byParent = MediaFolder::all()->groupBy('parent_id');
        $ids = [$folder->id];
        $queue = [$folder->id];

        while ($queue) {
            $parentId = array_shift($queue);
            foreach ($byParent->get($parentId, collect()) as $child) {
                $ids[] = $child->id;
                $queue[] = $child->id;
            }
        }

        return $ids;
    }

    /**
     * The slash-separated folder path a file should live at inside an export
     * ZIP, walking up the folder chain. Stops at $stopId (exclusive) so a
     * folder-scoped export is relative to that folder; walks to the root when
     * $stopId is null. Segment names are sanitised into safe path components.
     */
    private function folderPath(?int $folderId, ?int $stopId, $folders): string
    {
        $segments = [];

        while ($folderId !== null && $folderId !== $stopId && ($folder = $folders->get($folderId))) {
            array_unshift($segments, $this->sanitizeSegment($folder->name));
            $folderId = $folder->parent_id;
        }

        return implode('/', array_filter($segments));
    }

    /**
     * Finds or creates the folder chain described by a ZIP entry's directory
     * path, nested under the import's target folder, returning the id of the
     * deepest folder. Paths already resolved this run are cached so sibling
     * files don't re-query. Returns the base folder id for root-level files.
     */
    private function resolveImportFolder(string $dir, ?int $baseFolderId, array &$cache): ?int
    {
        $dir = trim(str_replace('\\', '/', $dir), '/');

        if ($dir === '' || $dir === '.') {
            return $baseFolderId;
        }

        $parentId = $baseFolderId;
        $key = (string) $baseFolderId;

        foreach (explode('/', $dir) as $rawSegment) {
            $segment = $this->sanitizeSegment($rawSegment);

            if ($segment === '' || $segment === '.' || $segment === '..') {
                continue;
            }

            $key .= '/'.$segment;

            if (isset($cache[$key])) {
                $parentId = $cache[$key];

                continue;
            }

            $folder = MediaFolder::firstOrCreate(['name' => $segment, 'parent_id' => $parentId]);
            $cache[$key] = $folder->id;
            $parentId = $folder->id;
        }

        return $parentId;
    }

    /**
     * Whether a file should be refused on import because it could be executed
     * when served from the public disk. Library files are static content, so
     * scripts and binaries have no legitimate place here.
     */
    private function isBlockedFile(string $fileName): bool
    {
        $blocked = ['php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'phps',
            'exe', 'sh', 'bat', 'cmd', 'com', 'cgi', 'pl', 'py', 'js', 'jsp',
            'asp', 'aspx', 'htaccess'];

        return in_array(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)), $blocked, true);
    }

    /**
     * Collapses a folder name into a single safe path segment (no slashes or
     * traversal), so a folder called "a/b" or ".." can't reshape the archive
     * layout on export or the folder tree on import.
     */
    private function sanitizeSegment(string $name): string
    {
        return trim(str_replace(['/', '\\', '..'], ['-', '-', ''], $name));
    }

    /**
     * Ensures each ZIP entry name is unique by suffixing " (2)", " (3)", …
     * before the extension when two files share a name within the same
     * exported folder.
     */
    private function uniqueEntry(string $entry, array &$used): string
    {
        if (! isset($used[$entry])) {
            $used[$entry] = true;

            return $entry;
        }

        $dir = str_contains($entry, '/') ? Str::beforeLast($entry, '/').'/' : '';
        $file = str_contains($entry, '/') ? Str::afterLast($entry, '/') : $entry;
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $stem = $ext !== '' ? Str::beforeLast($file, '.') : $file;
        $suffix = $ext !== '' ? '.'.$ext : '';

        $i = 2;
        do {
            $candidate = $dir.$stem.' ('.$i.')'.$suffix;
            $i++;
        } while (isset($used[$candidate]));

        $used[$candidate] = true;

        return $candidate;
    }

    /**
     * Model classes whose media the current user is allowed to see/manage,
     * based on their role and user group's module permissions. The media
     * library's own standalone uploads are always visible — they aren't
     * gated behind a toggleable content module.
     */
    private function accessibleModelTypes(): array
    {
        $user = auth()->user();

        return collect(self::OWNERS)
            ->filter(fn ($owner, $modelType) => $modelType === MediaLibrary::class
                ? true
                : ($owner['module'] === null ? $user->isAdmin() : $user->canAccessModule($owner['module'])))
            ->keys()
            ->all();
    }

    private function describeOwner(Media $media, $model): array
    {
        $owner = self::OWNERS[$media->model_type] ?? null;

        if (! $owner) {
            return ['label' => class_basename($media->model_type), 'url' => null, 'standalone' => false];
        }

        return [
            'label' => $owner['label'],
            'url' => $model && $owner['route']
                ? route($owner['route'], $owner['param'] ? [$owner['param'] => $media->model_id] : [])
                : null,
            'standalone' => $media->model_type === MediaLibrary::class,
        ];
    }

    /**
     * A screen-reader-friendly description of what the file shows. Falls back
     * to the owner label ("Zdjęcie w galerii") only when there is no real
     * description anywhere, so a file still has *something* in the picker.
     */
    private function describeAlt(Media $media, $model): string
    {
        $owner = self::OWNERS[$media->model_type] ?? null;
        $fallback = $owner['label'] ?? class_basename($media->model_type);

        return $this->resolveAltText($media, $model) ?: $fallback;
    }

    /**
     * The real, human-written alt description of a file, or null when none
     * exists (so the audit can tell a genuine description apart from the
     * generic owner-label fallback). Priority: an alt saved directly on the
     * file, then an imported Unsplash photo's credit, then the owning
     * record's own descriptive field.
     */
    private function resolveAltText(Media $media, $model): ?string
    {
        if (filled($own = $media->getCustomProperty('alt'))) {
            return $own;
        }

        if (filled($author = $media->getCustomProperty('unsplash_author'))) {
            return 'Zdjęcie: '.$author.' / Unsplash';
        }

        if (! $model) {
            return null;
        }

        $source = self::ALT_SOURCES[$media->model_type] ?? null;

        $text = match ($source) {
            'image_alt_or_title' => $model->image_alt ?: $model->title,
            null => null,
            default => $model->{$source} ?? null,
        };

        return filled($text) ? $text : null;
    }

    /**
     * Audyt dostępności: obrazy w bibliotece bez żadnego opisu alternatywnego
     * (ani zapisanego na pliku, ani wynikającego z rekordu właściciela).
     * Filtrujemy w PHP, bo opis bywa liczony z pól różnych modeli, więc
     * właścicieli doładowujemy hurtowo, żeby uniknąć zapytań N+1.
     */
    public function altAudit()
    {
        $missing = $this->missingAltMedia();
        $perPage = 30;
        $page = Paginator::resolveCurrentPage();

        $rows = $missing->forPage($page, $perPage)->map(function (Media $media) {
            $model = $media->relationLoaded('_owner') ? $media->getRelation('_owner') : null;

            return [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'file_name' => $media->file_name,
                'size' => $media->human_readable_size,
                'owner' => $this->describeOwner($media, $model),
            ];
        })->values();

        $paginator = new LengthAwarePaginator($rows, $missing->count(), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'query' => request()->query(),
        ]);

        return view('admin.media.alt-audit', [
            'rows' => $paginator,
            'total' => $missing->count(),
        ]);
    }

    /**
     * Zapisuje opis alternatywny bezpośrednio na pliku (custom property).
     * Ma pierwszeństwo nad opisem wyprowadzanym z rekordu właściciela, więc
     * działa też dla plików wgranych wprost do biblioteki, które nie mają
     * żadnego pola opisowego. Pusty opis usuwa właściwość.
     */
    public function updateAlt(Request $request, Media $media)
    {
        abort_unless(in_array($media->model_type, $this->accessibleModelTypes()), 403);

        $data = $request->validate([
            'alt' => ['nullable', 'string', 'max:255'],
        ]);

        $alt = trim((string) ($data['alt'] ?? ''));

        if ($alt === '') {
            $media->forgetCustomProperty('alt');
        } else {
            $media->setCustomProperty('alt', $alt);
        }

        $media->save();

        return redirect()->back()->with('status', 'Opis alternatywny został zapisany.');
    }

    /**
     * Dostępne, niezarchiwizowane obrazy bez realnego opisu alternatywnego,
     * z doładowanym (i podpiętym jako relacja `_owner`) rekordem właściciela.
     *
     * @return \Illuminate\Support\Collection<int, Media>
     */
    private function missingAltMedia()
    {
        $media = Media::query()
            ->whereIn('model_type', $this->accessibleModelTypes())
            ->where('mime_type', 'like', 'image/%')
            ->whereNull('archived_at')
            ->latest()
            ->get();

        $owners = [];
        foreach ($media->groupBy('model_type') as $type => $group) {
            $owners[$type] = $type::whereIn('id', $group->pluck('model_id')->unique())->get()->keyBy('id');
        }

        return $media->filter(function (Media $item) use ($owners) {
            $model = $owners[$item->model_type][$item->model_id] ?? null;
            $item->setRelation('_owner', $model);

            return blank($this->resolveAltText($item, $model));
        })->values();
    }
}
