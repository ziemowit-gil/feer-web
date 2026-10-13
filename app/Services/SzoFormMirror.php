<?php

namespace App\Services;

use App\Models\FormDefinition;
use Illuminate\Support\Str;

/**
 * Lustro formularza SZO w CMS-ie: formularz CMS z polami i zgodami formularza CRM.
 *
 * Dzięki temu formularz z SZO można osadzić na stronie (np. w ogłoszeniu wolontariatu)
 * zwykłym mechanizmem formularzy CMS — zgłoszenie zapisuje się lokalnie, przechodzi
 * antyspam i trafia do SZO przez SzoClient (z ponawianiem `szo:push-submissions`).
 *
 * Każde pole pamięta swój klucz w SZO (`szo_name`), więc etykiety można zmieniać
 * bez psucia mapowania. Pola dodatkowe CRM bez klucza idą jako `custom:{id definicji}`.
 */
class SzoFormMirror
{
    public function __construct(private SzoClient $szo) {}

    /** Slug lustrzanego formularza CMS dla formularza SZO. */
    public static function mirrorSlug(string $szoSlug): string
    {
        return Str::limit('szo-' . Str::slug($szoSlug), 100, '');
    }

    /**
     * Dane zapisu treści z formularzem SZO (HasSzoForm): normalizuje `szo_form_slug`
     * i ustawia `form_definition_id` na odświeżone lustro (albo null, gdy bez formularza).
     */
    public function attach(array $data, ?string $notificationEmail = null): array
    {
        $data['szo_form_slug'] = trim((string) ($data['szo_form_slug'] ?? '')) ?: null;
        $data['form_definition_id'] = $data['szo_form_slug']
            ? $this->sync($data['szo_form_slug'], $notificationEmail)?->id
            : null;

        return $data;
    }

    /**
     * Tworzy albo odświeża lustro formularza SZO. Gdy SZO nie odpowiada, zwraca
     * istniejące lustro (jeśli jest) — strona dalej ma działający formularz.
     */
    public function sync(string $szoSlug, ?string $notificationEmail = null): ?FormDefinition
    {
        $szoSlug  = trim($szoSlug);
        $existing = FormDefinition::where('slug', self::mirrorSlug($szoSlug))->first();
        $remote   = $this->szo->form($szoSlug);

        if (! $remote) {
            return $existing;
        }

        // Ustawienia redaktora (powiadomienia, komunikat po wysłaniu) zostają — nadpisujemy tylko podpięcie do SZO.
        $settings = array_merge(
            ['delivery' => 'email', 'notification_email' => $notificationEmail],
            $existing?->settings ?? [],
            ['szo_form_slug' => $szoSlug],
        );

        $form = $existing ?? new FormDefinition(['slug' => self::mirrorSlug($szoSlug)]);
        $form->fill([
            'title'       => 'SZO: ' . ($remote['title'] ?? $szoSlug),
            'description' => $remote['description'] ?? null,
            'fields'      => $this->fields($remote, $existing),
            'settings'    => $settings,
            'is_active'   => true,
        ])->save();

        return $form;
    }

    /** Pola i zgody formularza SZO jako pola formularza CMS. */
    private function fields(array $remote, ?FormDefinition $existing): array
    {
        // Stałe uid pól między synchronizacjami (po kluczu SZO) — warunki i zgłoszenia ich nie gubią.
        $uids = collect($existing?->fields ?? [])->filter(fn ($f) => filled($f['szo_name'] ?? null))
            ->mapWithKeys(fn ($f) => [$f['szo_name'] => $f['uid'] ?? null])->all();

        $fields = [];

        foreach ((array) ($remote['fields'] ?? []) as $f) {
            $name = (string) ($f['name'] ?? '');
            $szoName = filled($f['field_def_id'] ?? null) ? 'custom:' . (int) $f['field_def_id'] : $name;
            if ($szoName === '') {
                continue;
            }
            $label = trim((string) ($f['label'] ?? '')) ?: Str::ucfirst(str_replace('_', ' ', $name));

            $options = array_values(array_filter(array_map('trim', (array) ($f['options'] ?? [])), 'strlen'));
            $type = $this->type($name, (string) ($f['type'] ?? 'text'));
            if ($type === 'select' && ! $options) {
                $type = 'text'; // lista wyboru bez opcji w SZO — pole tekstowe zamiast pustej listy
            }

            $fields[] = $this->field($szoName, $label, $type, ! empty($f['required']), $uids) + array_filter([
                'options'     => $type === 'select' ? implode(', ', $options) : null,
                'placeholder' => trim((string) ($f['placeholder'] ?? '')) ?: null,
            ]);
        }

        foreach ((array) ($remote['consents'] ?? []) as $c) {
            $id = (string) ($c['id'] ?? '');
            if ($id === '' || blank($c['text'] ?? null)) {
                continue;
            }
            $fields[] = $this->field($id, trim((string) $c['text']), 'checkbox', ! empty($c['required']), $uids);
        }

        return $fields;
    }

    private function field(string $szoName, string $label, string $type, bool $required, array $uids): array
    {
        return [
            'label'    => $label,
            'type'     => $type,
            'required' => $required,
            'uid'      => $uids[$szoName] ?? Str::lower(Str::random(8)),
            'szo_name' => $szoName,
        ];
    }

    /** Typ pola CMS: z typu w SZO, a dla pól wbudowanych kartoteki — z ich klucza. */
    private function type(string $name, string $type): string
    {
        $byType = ['email' => 'email', 'tel' => 'tel', 'url' => 'url', 'number' => 'number', 'date' => 'date', 'textarea' => 'textarea', 'select' => 'select'];
        if (isset($byType[$type])) {
            return $byType[$type];
        }

        return match (true) {
            $name === 'email'                                       => 'email',
            in_array($name, ['telefon', 'tel', 'phone'], true)      => 'tel',
            in_array($name, ['www', 'strona', 'url'], true)         => 'url',
            (bool) preg_match('/notatk|opis|wiadomo|motywac|uwagi|tresc/', $name) => 'textarea',
            default                                                 => 'text',
        };
    }
}
