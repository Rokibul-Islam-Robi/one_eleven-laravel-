<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? ($siteTitle ?? 'bHerb') }}</title>
    <meta name="description" content="{{ $pageDesc ?? ($siteDesc ?? '') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <base href="{{ rtrim(url('/'), '/') }}/">

    <style>
        {!! file_get_contents(public_path('assets/css/styles.css')) !!}
        {!! file_get_contents(public_path('assets/css/site-php.css')) !!}
        {!! file_get_contents(public_path('assets/css/home.css')) !!}
    </style>

    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/site-php.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/home.css') }}">
</head>
<body>
@include('layouts.partials.header')
{!! $content !!}
@include('layouts.partials.footer')
