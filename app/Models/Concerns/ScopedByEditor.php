<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Zakres treści dla edytorów z grup: zapisuje autora (created_by) i — tylko w panelu administracyjnym —
 * zawęża zapytania do własnych wpisów (UserGroup::own_content_only). Model może dodatkowo zawęzić zakres
 * w metodzie `constrainForEditor()` (np. działania do wskazanych kategorii).
 *
 * Zakres nie dotyczy strony publicznej (zalogowany edytor widzi tam całą treść) ani administratorów.
 */
trait ScopedByEditor
{
    public static function bootScopedByEditor(): void
    {
        static::creating(function ($model) {
            if (empty($model->created_by) && ($user = auth('web')->user())) {
                $model->created_by = $user->id;
            }
        });

        static::addGlobalScope('editor_scope', function (Builder $query) {
            $user = auth('web')->user();
            if (! $user || ! request()->routeIs('admin.*') || ! $user->hasContentScope()) {
                return;
            }

            $model = $query->getModel();
            $group = $user->group;

            if ($group?->own_content_only) {
                $query->where($model->getTable().'.created_by', $user->id);
            }
            if (method_exists($model, 'constrainForEditor')) {
                $model->constrainForEditor($query, $user);
            }
        });
    }
}
