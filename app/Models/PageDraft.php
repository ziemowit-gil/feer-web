<?php

namespace App\Models;

use App\Support\BlockRenderer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Wersja robocza opublikowanej strony (Workspaces w TYPO3): tytuł, opis i bloki przygotowane w edytorze blokowym,
 * niewidoczne publicznie do czasu publikacji. `submitted_at` — zgłoszona do akceptacji przez osobę bez prawa publikacji.
 */
class PageDraft extends Model
{
    protected $fillable = ['page_id', 'user_id', 'title', 'meta_description', 'blocks', 'submitted_at', 'submitted_by_id'];

    protected $casts = ['blocks' => 'array', 'submitted_at' => 'datetime'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    /** HTML treści wersji roboczej (jak `content` strony po zapisie z edytora). */
    public function renderedContent(): string
    {
        return BlockRenderer::render((array) ($this->blocks ?? []));
    }

    /** Czy kopia różni się od opublikowanej strony (tytuł, opis albo treść). */
    public function differsFromPage(): bool
    {
        $page = $this->page;

        return $page && ($this->title !== $page->title || (string) $this->meta_description !== (string) $page->meta_description
            || $this->renderedContent() !== (string) $page->content);
    }
}
