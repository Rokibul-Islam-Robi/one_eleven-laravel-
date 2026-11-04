<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? ($siteTitle ?? 'bHerb') }}</title>
    <meta name="description" content="{{ $pageDesc ?? ($siteDesc ?? '') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <base href="{{ rtrim(url('/'), '/') }}/">

    {{-- Storefront stylesheets — cascade order: base → PHP-specific → homepage/nav rules --}}
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css?v=' . ($cssV ?? '1.0')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/site-php.css?v=' . ($cssV ?? '1.0')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/home.css?v=' . ($cssV ?? '1.0')) }}">
</head>
<body>
@include('layouts.partials.header')
{!! $content !!}
@include('layouts.partials.footer')
