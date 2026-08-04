<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Szablon maila edytowalny z panelu admina.
 * Temat i treść HTML mogą zawierać zmienne {{nazwa_zmiennej}}.
 */
class MailTemplate extends Model
{
    protected $fillable = ['slug', 'name', 'subject', 'body', 'variables'];

    protected $casts = ['variables' => 'array'];

    public static function findBySlug(string $slug): ?static
    {
        return static::where('slug', $slug)->first();
    }

    public function renderSubject(array $vars): string
    {
        return $this->interpolate($this->subject, $vars);
    }

    public function renderBody(array $vars): string
    {
        return $this->interpolate($this->body, $vars);
    }

    private function interpolate(string $template, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace('{{' . $key . '}}', (string) $value, $template);
        }

        return $template;
    }
}
