<?php

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterOpen extends Model
{
    protected $table = 'newsletter_opens';

    public $timestamps = false;

    protected $fillable = ['delivery_id', 'opened_at', 'ip_hash', 'client_family', 'is_proxy'];

    protected $casts = ['opened_at' => 'datetime', 'is_proxy' => 'boolean'];
}
