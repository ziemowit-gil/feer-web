<?php

namespace Modules\Blog\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Blog\Models\BlogArticle;
use Modules\Blog\Models\BlogAuthor;
use Modules\Blog\Support\BlogImages;

/**
 * Autorzy Wiem FEER — lista, dodawanie, edycja, (de)aktywacja.
 * Autorów się nie usuwa (wpisy mają do nich odnośniki) — nieaktywny znika z list wyboru.
 */
class BlogAuthorController extends Controller
{
    private const PHOTO_DIR = 'wiem-feer/autorzy';

    public function index()
    {
        $authors = BlogAuthor::query()->orderByDesc('is_active')->orderBy('name')->get()
            ->each(function (BlogAuthor $author) {
                $author->setAttribute('articles_total', BlogArticle::query()->byAuthor($author)->count());
            });

        return view('blog::admin.authors.index', compact('authors'));
    }

    public function create()
    {
        return view('blog::admin.authors.form', ['author' => new BlogAuthor(['is_active' => true]), 'users' => $this->users()]);
    }

    public function store(Request $request)
    {
        $author = new BlogAuthor;
        $author->fill($this->validated($request, $author))->save();

        return redirect()->route('admin.autorzy-bloga.index')->with('status', 'Autor „' . $author->name . '" został dodany.');
    }

    public function edit(BlogAuthor $author)
    {
        return view('blog::admin.authors.form', ['author' => $author, 'users' => $this->users()]);
    }

    public function update(Request $request, BlogAuthor $author)
    {
        $author->update($this->validated($request, $author));

        return redirect()->route('admin.autorzy-bloga.index')->with('status', 'Zapisano autora „' . $author->name . '".');
    }

    public function toggleActive(BlogAuthor $author)
    {
        $author->update(['is_active' => ! $author->is_active]);

        return redirect()->route('admin.autorzy-bloga.index')->with('status', $author->is_active
            ? 'Autor „' . $author->name . '" jest znów aktywny.'
            : 'Autor „' . $author->name . '" został dezaktywowany — nie pojawi się na liście wyboru autorów.');
    }

    private function users()
    {
        return User::query()->orderBy('name')->get(['id', 'name', 'email']);
    }

    private function validated(Request $request, BlogAuthor $author): array
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:120'],
            'slug'             => ['nullable', 'string', 'max:120'],
            'role'             => ['nullable', 'string', 'max:160'],
            'bio'              => ['nullable', 'string', 'max:1500'],
            'email'            => ['nullable', 'email', 'max:255'],
            'links'            => ['nullable', 'string', 'max:2000'],
            'user_id'          => ['nullable', 'integer', Rule::exists('users', 'id')],
            'photo'            => ['nullable', 'image', 'max:4096'],
            'library_media_id' => ['nullable', 'integer'],
            'photo_alt'        => ['nullable', 'string', 'max:255'],
        ], [], [
            'name' => 'imię i nazwisko', 'role' => 'rola', 'bio' => 'nota', 'links' => 'linki',
            'user_id' => 'konto w CMS', 'photo' => 'zdjęcie', 'photo_alt' => 'tekst alternatywny zdjęcia',
        ]);

        $slug = Str::slug(trim((string) ($data['slug'] ?? '')) ?: $data['name']) ?: 'autor';
        if (BlogAuthor::where('slug', $slug)->when($author->exists, fn ($q) => $q->whereKeyNot($author->id))->exists()) {
            throw ValidationException::withMessages(['slug' => 'Autor o takim adresie już istnieje — podaj inny adres (slug).']);
        }

        $links = BlogAuthor::parseLinks($data['links'] ?? '');
        foreach ($links as $l) {
            if (! preg_match('~^https?://[^\s]+$~i', $l['url'])) {
                throw ValidationException::withMessages(['links' => 'Każdy link musi zaczynać się od https:// (format: „Etykieta | https://…").']);
            }
        }

        if ($data['user_id'] ?? null) {
            $taken = BlogAuthor::where('user_id', $data['user_id'])->when($author->exists, fn ($q) => $q->whereKeyNot($author->id))->exists();
            if ($taken) {
                throw ValidationException::withMessages(['user_id' => 'To konto jest już powiązane z innym autorem.']);
            }
        }

        $result = [
            'name'       => $data['name'],
            'slug'       => $slug,
            'role'       => trim((string) ($data['role'] ?? '')) ?: null,
            'bio'        => trim((string) ($data['bio'] ?? '')) ?: null,
            'email'      => $data['email'] ?? null,
            'show_email' => $request->boolean('show_email') && filled($data['email'] ?? null),
            'links'      => $links ?: null,
            'user_id'    => $data['user_id'] ?? null,
            'is_active'  => $request->boolean('is_active'),
            'photo_alt'  => trim((string) ($data['photo_alt'] ?? '')) ?: null,
        ];

        $old = $author->photo_path;
        if ($request->hasFile('photo')) {
            $result['photo_path'] = BlogImages::storeUpload($request->file('photo'), self::PHOTO_DIR);
        } elseif ($request->filled('library_media_id')) {
            $result['photo_path'] = BlogImages::copyFromLibrary($request->integer('library_media_id'), self::PHOTO_DIR)
                ?? throw ValidationException::withMessages(['photo' => 'Nie udało się skopiować zdjęcia z biblioteki multimediów.']);
        } elseif ($request->boolean('remove_photo')) {
            $result['photo_path'] = null;
        }

        if (array_key_exists('photo_path', $result) && $old && $old !== $result['photo_path']) {
            BlogImages::delete($old);
        }

        return $result;
    }
}
