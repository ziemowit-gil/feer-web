<?php

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterClick extends Model
{
    protected $table = 'newsletter_clicks';

    public $timestamps = false;

    protected $fillable = ['delivery_id', 'link_id', 'clicked_at', 'ip_hash', 'user_agent'];

    protected $casts = ['clicked_at' => 'datetime'];
}
