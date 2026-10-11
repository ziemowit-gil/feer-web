<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class Partners extends PageType
{
    public function key(): string
    {
        return 'partners';
    }

    public function label(): string
    {
        return 'Partnerzy i sponsorzy (logotypy w grupach, z modułu Partnerzy lub własne)';
    }

    public function group(): string
    {
        return 'Organizacja';
    }

    public function order(): int
    {
        return 270;
    }

    public function groupOrder(): int
    {
        return 80;
    }

    public function icon(): ?string
    {
        return 'fa-handshake';
    }

    public function usesTypeData(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::sharedRules('lead') + [
            'type_data.partner_groups' => ['nullable', 'array', 'max:20'],
            'type_data.partner_groups.*.title' => ['nullable', 'string', 'max:120'],
            'type_data.partner_groups.*.text' => ['nullable', 'string', 'max:600'],
            'type_data.partners' => ['nullable', 'array', 'max:200'],
            'type_data.partners.*.group' => ['nullable', 'string', 'max:120'],
            'type_data.partners.*.partner_id' => ['nullable', 'integer', 'exists:partners,id'],
            'type_data.partners.*.name' => ['nullable', 'string', 'max:160'],
            'type_data.partners.*.url' => ['nullable', 'string', 'max:2048', 'regex:~^(https?://|/)~i'],
            'type_data.partners.*.logo' => ['nullable', 'string', 'max:2048', 'regex:~^(https?://|/)~i'],
            'type_data.partners.*.note' => ['nullable', 'string', 'max:300'],
            'type_data.partners_layout' => ['nullable', 'in:logos,cards'],
            'type_data.partners_all' => ['nullable', 'boolean'],
            'type_data.partners_cta_label' => ['nullable', 'string', 'max:80'],
            'type_data.partners_cta_url' => ['nullable', 'string', 'max:2048', 'regex:~^(https?://|/|mailto:)~i'],
            'type_data.partners_thanks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function listKeys(): array
    {
        return ['partners', 'partner_groups'];
    }

    public function frontView(): ?string
    {
        return 'page.partials.typed.partners';
    }

    public function adminView(): ?string
    {
        return 'admin.pages.partials.type-data.partners';
    }
}
