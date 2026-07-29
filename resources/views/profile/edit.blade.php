@extends('admin.layout')

@section('title', 'Mój profil')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-ink">Mój profil</h1>
            <p class="mt-1 text-sm text-muted">Zarządzaj danymi konta, hasłem i logowaniem dwuetapowym.</p>
        </div>

        <section class="rounded-lg border border-gray-200 bg-white p-6">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </section>

        <section id="dwuetapowe" class="scroll-mt-6 rounded-lg border border-gray-200 bg-white p-6">
            @include('profile.partials.two-factor-form')
        </section>

        <section class="rounded-lg border border-red-200 bg-white p-6">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </section>
    </div>
@endsection
