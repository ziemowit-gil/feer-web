@extends('layouts.site')

@section('title', 'Dane usunięte — ' . $siteSettings->site_name)

@section('content')
    <section class="mx-auto max-w-xl px-4 py-16 text-center">
        <h1 class="mb-3 text-2xl font-bold text-ink">Twoje dane zostały usunięte</h1>
        <p class="mb-6 text-ink">Adres e-mail, imię i telefon zostały zanonimizowane. Przez 30 dni adres pozostaje na liście blokad, żebyśmy przypadkiem nie wysłali nic ponownie.</p>
        <a href="{{ route('home') }}" class="inline-block text-sm font-bold text-brand-dark underline underline-offset-4 hover:text-ink">← Wróć na stronę główną</a>
    </section>
@endsection
