<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Prośba o powiadomienie o nowym terminie szkolenia (adres e-mail zainteresowanej osoby). */
class CourseNotification extends Model
{
    use \App\Models\Concerns\BelongsToSite;

    protected $fillable = ['site_id', 'course_id', 'email', 'notified_at'];

    protected $casts = ['notified_at' => 'datetime'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
