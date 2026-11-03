<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Support\Collection;

/**
 * Podgląd menu bocznego panelu dla roli, grupy edytora albo konkretnego użytkownika: to samo, co ta osoba zobaczy
 * ({@see AdminMenu}), bez logowania się na jej konto. Tylko odczyt — obiekty User tworzone dla roli i grupy nie są
 * zapisywane, a podgląd niczego nie zmienia.
 *
 * Wybór z formularza to „rola:coordinator”, „grupa:3” (edytor z grupy) albo „uzytkownik:12”.
 */
final class RoleMenuPreview
{
    /** @return ?User null, gdy wybór jest pusty, nieznany albo wskazuje rekord, którego nie ma */
    public static function subject(?string $choice): ?User
    {
        [$kind, $id] = array_pad(explode(':', (string) $choice, 2), 2, '');

        if ($kind === 'rola') {
            return array_key_exists($id, User::ROLES) ? self::syntheticUser($id) : null;
        }

        if (! ctype_digit($id)) {
            return null;
        }

        if ($kind === 'grupa') {
            $group = UserGroup::query()->find((int) $id);

            return $group ? self::syntheticUser(User::ROLE_EDITOR, $group) : null;
        }

        return $kind === 'uzytkownik' ? User::query()->with('group')->find((int) $id) : null;
    }

    private static function syntheticUser(string $role, ?UserGroup $group = null): User
    {
        $user = (new User)->forceFill(['role' => $role, 'name' => User::ROLES[$role] ?? $role]);

        if ($group) {
            $user->forceFill(['user_group_id' => $group->id])->setRelation('group', $group);
        }

        return $user;
    }

    /** Podpis podglądu: „Koordynator”, „Edytor (grupy): Redakcja” albo „Anna Nowak (Koordynator)”. */
    public static function title(User $subject): string
    {
        $role = User::ROLES[$subject->role] ?? (string) $subject->role;

        if ($subject->exists) {
            return $subject->name.' ('.$role.($subject->group ? ', '.$subject->group->name : '').')';
        }

        return $subject->group ? $role.': '.$subject->group->name : $role;
    }

    /**
     * Wybór do listy rozwijanej: role (edytor z grupy osobno dla każdej grupy, bo to grupa decyduje o modułach)
     * i użytkownicy.
     *
     * @return array<string, list<array{value: string, label: string}>>
     */
    public static function options(): array
    {
        $roles = collect(User::ROLES)->except(User::ROLE_EDITOR)
            ->map(fn (string $label, string $role) => ['value' => 'rola:'.$role, 'label' => $label])->values();
        $groups = UserGroup::query()->orderBy('name')->get(['id', 'name'])
            ->map(fn (UserGroup $group) => ['value' => 'grupa:'.$group->id, 'label' => (User::ROLES[User::ROLE_EDITOR] ?? 'Edytor').': '.$group->name]);
        $users = User::query()->orderBy('name')->get(['id', 'name', 'role'])
            ->map(fn (User $user) => ['value' => 'uzytkownik:'.$user->id, 'label' => $user->name.' — '.(User::ROLES[$user->role] ?? $user->role)]);

        return array_filter([
            'Role' => $roles->merge($groups)->values()->all(),
            'Użytkownicy' => $users->values()->all(),
        ]);
    }

    /**
     * Menu osoby w kształcie do pokazania (bez adresów, liczników i stanu „aktywna”) oraz to, czego ta osoba nie
     * widzi, a administrator tak.
     *
     * @return array{
     *     groups: list<array{key: string, label: string, icon: string, focus: bool, blocks: list<array{heading: ?string, items: list<array{key: string, label: string, icon: string, children: list<string>}>}>}>,
     *     hidden: list<array{label: string, items: list<string>}>,
     *     visible: int,
     *     total: int
     * }
     */
    public static function build(User $subject): array
    {
        $focus = AdminMenu::ROLE_FOCUS[(string) $subject->role] ?? [];
        $groups = array_map(fn (array $group) => [
            'key' => (string) $group['key'],
            'label' => (string) $group['label'],
            'icon' => (string) $group['icon'],
            'focus' => in_array($group['key'], $focus, true),
            'blocks' => array_map(fn (array $block) => [
                'heading' => $block['heading'],
                'items' => array_map(fn (array $item) => [
                    'key' => (string) $item['key'],
                    'label' => (string) $item['label'],
                    'icon' => (string) $item['icon'],
                    'children' => array_map(fn (array $child) => (string) $child['label'], $item['children'] ?? []),
                ], $block['items']),
            ], $group['blocks']),
        ], AdminMenu::for($subject));

        $seen = self::itemKeys($groups);
        $hidden = [];
        $total = 0;

        foreach (AdminMenu::for((new User)->forceFill(['role' => User::ROLE_ADMIN])) as $group) {
            $missing = [];

            foreach ($group['blocks'] as $block) {
                foreach ($block['items'] as $item) {
                    $total++;

                    if (! in_array($item['key'], $seen, true)) {
                        $missing[] = (string) $item['label'];
                    }
                }
            }

            if ($missing !== []) {
                $hidden[] = ['label' => (string) $group['label'], 'items' => $missing];
            }
        }

        return ['groups' => $groups, 'hidden' => $hidden, 'visible' => count($seen), 'total' => $total];
    }

    /** @return list<string> */
    private static function itemKeys(array $groups): array
    {
        return collect($groups)->flatMap(fn (array $group) => $group['blocks'])->flatMap(fn (array $block) => $block['items'])->pluck('key')->values()->all();
    }

    /**
     * Osoby, których menu dotyczy podgląd roli albo grupy (do przejścia na ich własny podgląd); dla konkretnego
     * użytkownika pusta lista.
     *
     * @return Collection<int, User>
     */
    public static function members(User $subject): Collection
    {
        if ($subject->exists) {
            return new Collection;
        }

        return User::query()->where('role', $subject->role)
            ->when($subject->group, fn ($query, UserGroup $group) => $query->where('user_group_id', $group->id))
            ->orderBy('name')->limit(40)->get(['id', 'name']);
    }
}
