<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Subscriber extends Model
{
    protected $fillable = ['email', 'name', 'token', 'topics', 'confirmed_at'];

    protected $casts = [
        'topics' => 'array',
        'confirmed_at' => 'datetime',
    ];

    /** Dostępne tematy subskrypcji: klucz => etykieta wyświetlana. */
    public static array $availableTopics = [
        'news'      => 'Aktualności',
        'events'    => 'Szkolenia i wydarzenia',
        'blog'      => 'Blog Wiem FEER',
        'materials' => 'Materiały edukacyjne',
        'etr'       => 'Treści ETR (Łatwy Odczyt)',
        'projects'  => 'Projekty',
        'campaigns' => 'Kampanie zbiórkowe',
    ];

    public static function generateToken(): string
    {
        do {
            $token = Str::random(48);
        } while (static::where('token', $token)->exists());

        return $token;
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    public function hasTopic(string $topic): bool
    {
        return in_array($topic, $this->topics ?? [], true);
    }

    public function topicLabels(): array
    {
        return array_values(array_intersect_key(
            static::$availableTopics,
            array_flip($this->topics ?? [])
        ));
    }
}
