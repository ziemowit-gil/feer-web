{{-- Podgląd menu: layout serwisu z samym nagłówkiem (main) albo samą stopką (footer) — patrz $menuPreview w layouts/site. --}}
@extends('layouts.site')

@section('title', 'Podgląd menu — ' . $siteSettings->site_name)
@section('menu_preview', $menuPreviewLocation)
