@extends('layouts.site')

@section('title', 'Twoje materiały — ' . $siteSettings->site_name)

@section('content')
    <section class="mx-auto max-w-3xl space-y-6 px-4 py-12">
        <h1 class="text-2xl font-bold text-ink">Twoje materiały</h1>

        @foreach ($order->items as $item)
            @include('sklep.partials.material-access', ['item' => $item])
        @endforeach
    </section>
@endsection
