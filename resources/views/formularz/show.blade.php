@extends('layouts.site')

@section('title', $form->title . ' — ' . $settings->site_name)
@section('meta_description', $form->description)

@section('content')
<div class="mx-auto max-w-2xl px-4 py-10 sm:px-6">
    @include('formularz._embed', ['form' => $form])
</div>
@endsection
