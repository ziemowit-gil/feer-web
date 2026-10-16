<?php

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Szablon wiadomości: model Mosaico (metadata + content + HTML) albo surowy HTML. */
class NewsletterTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'newsletter_templates';

    protected $fillable = [
        'site_id', 'name', 'subject', 'kind', 'system_key', 'mosaico_template', 'editor_metadata', 'editor_content', 'html_body',
        'thumbnail_path', 'is_default', 'created_by',
    ];

    protected $casts = ['editor_metadata' => 'array', 'editor_content' => 'array', 'is_default' => 'boolean'];

    public const KINDS = ['mosaico' => 'Edytor Mosaico', 'html' => 'Własny HTML'];

    /** Szablon bazowy dla nowych szablonów i kampanii. Odczyt istniejących bez szablonu zostaje przy feer-1 (ich zapisana treść jest z feer-1). */
    public const DEFAULT_MOSAICO = 'feer-2';

    /** Szablony bazowe Mosaico (katalogi w public/vendor/mosaico/templates/). */
    public const MOSAICO_TEMPLATES = [
        'feer-1' => 'FEER klasyczny — ciemnoniebieskie przyciski, szare tło',
        'feer-2' => 'FEER — kolory marki, domyślny (niebieski #1E6DFF, pomarańczowe przyciski, tło #CBD5E7)',
    ];

    /** Maile systemowe, które można przeprojektować w Mosaico (puste = wbudowany widok Blade). */
    public const SYSTEM_MAILS = [
        'double_opt_in' => [
            'name'    => 'Mail potwierdzający zapis (double opt-in)',
            'subject' => 'Potwierdź zapis na newsletter — {{site_name}}',
            'tags'    => ['confirm_url' => 'Link potwierdzający (wstaw w przycisk)', 'first_name' => 'Imię', 'email' => 'Adres e-mail', 'topic_list' => 'Wybrane tematy', 'ttl_days' => 'Ważność linku (dni)', 'site_name' => 'Nazwa witryny', 'site_url' => 'Adres witryny', 'preferences_url' => 'Link do preferencji'],
            'required'=> 'confirm_url',
        ],
    ];

    public static function system(string $key): ?static
    {
        return static::query()->where('system_key', $key)->first();
    }

    public function isSystem(): bool
    {
        return $this->system_key !== null;
    }
}
