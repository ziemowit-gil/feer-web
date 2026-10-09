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
        'site_id', 'name', 'kind', 'mosaico_template', 'editor_metadata', 'editor_content', 'html_body',
        'thumbnail_path', 'is_default', 'created_by',
    ];

    protected $casts = ['editor_metadata' => 'array', 'editor_content' => 'array', 'is_default' => 'boolean'];

    public const KINDS = ['mosaico' => 'Edytor Mosaico', 'html' => 'Własny HTML'];
}
