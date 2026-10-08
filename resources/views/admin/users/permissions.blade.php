@extends('admin.layout')

@section('title', 'Macierz uprawnień')

@section('content')
    <p class="mb-4 text-sm text-muted">Kto ma dostęp do których modułów panelu. Administratorzy mają dostęp do wszystkiego; edytorzy z grup — do modułów zaznaczonych w ich grupie (z ewentualnym zakresem treści).</p>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full min-w-max text-left text-sm">
            <caption class="sr-only">Uprawnienia użytkowników do modułów panelu</caption>
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th scope="col" class="sticky left-0 z-10 bg-gray-50 px-4 py-3">Użytkownik</th>
                    <th scope="col" class="px-4 py-3">Rola / grupa</th>
                    <th scope="col" class="px-4 py-3">Zakres treści</th>
                    @foreach ($modules as $label)
                        <th scope="col" class="px-3 py-3 text-center" style="max-width: 7rem">{{ \Illuminate\Support\Str::before($label, ' (') }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($users as $user)
                    @php
                        $scope = [];
                        if ($user->hasContentScope()) {
                            if ($user->group->own_content_only) { $scope[] = 'tylko własne wpisy'; }
                            if ($ids = $user->allowedProjectCategoryIds()) { $scope[] = 'projekty: '.collect($ids)->map(fn ($i) => $categories[$i] ?? '?')->implode(', '); }
                        }
                    @endphp
                    <tr>
                        <th scope="row" class="sticky left-0 z-10 bg-white px-4 py-3 text-left font-medium">{{ $user->name }}<span class="block text-xs font-normal text-muted">{{ $user->email }}</span></th>
                        <td class="px-4 py-3">{{ \App\Models\User::ROLES[$user->role] ?? $user->role }}@if ($user->group)<span class="block text-xs text-muted">{{ $user->group->name }}</span>@endif</td>
                        <td class="px-4 py-3 text-xs">{{ $scope ? implode('; ', $scope) : '—' }}</td>
                        @foreach ($modules as $key => $label)
                            <td class="px-3 py-3 text-center">
                                @if ($user->canAccessModule($key))
                                    <i class="fa-solid fa-check text-green-700" aria-hidden="true"></i><span class="sr-only">Ma dostęp</span>
                                @else
                                    <span class="text-gray-500" aria-hidden="true">—</span><span class="sr-only">Brak dostępu</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
