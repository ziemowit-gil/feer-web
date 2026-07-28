<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebinarRegistration extends Model
{
    protected $fillable = ['landing_page_id', 'name', 'email', 'phone', 'consent', 'forwarded'];

    protected $casts = ['consent' => 'boolean', 'forwarded' => 'boolean'];

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }
}
