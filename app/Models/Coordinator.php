<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Koordynator działań — osoba kontaktowa wybierana w działaniu (moduł „Koordynatorzy”). */
class Coordinator extends Model
{
    use \App\Models\Concerns\BelongsToSite;

    protected $fillable = ['site_id', 'name', 'email', 'phone', 'phone_hours', 'person_page_id', 'order'];

    public function personPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'person_page_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /** Zdjęcie: ze wskazanej strony osoby w zespole, a gdy jej brak — z osoby o tym samym e-mailu lub imieniu i nazwisku. */
    public function photoUrl(): ?string
    {
        if ($this->personPage?->content_image) {
            return $this->personPage->content_image;
        }
        $email = mb_strtolower(trim((string) $this->email));

        return Page::query()->forCurrentSite()->where('type', 'about_person')->where('is_published', true)
            ->whereNotNull('content_image')->where('content_image', '!=', '')
            ->where(fn ($q) => $q->when($email !== '', fn ($w) => $w->orWhereRaw('lower(person_email) = ?', [$email]))->orWhere('title', $this->name))
            ->value('content_image');
    }
}
