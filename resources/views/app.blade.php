@php
    $rafiqLocale = \App\Enums\Locale::tryFrom(app()->getLocale()) ?? \App\Enums\Locale::default();
    $rafiqUrls = \App\Support\LocalizedUrls::forRequest(request());
    $rafiqComponent = (string) ($page['component'] ?? '');
    $rafiqNoIndex = str_starts_with($rafiqComponent, 'Errors/')
        || str_starts_with($rafiqComponent, 'Admin/')
        || str_starts_with($rafiqComponent, 'Public/Gate/')
        || in_array($rafiqComponent, ['Public/Under18', 'Public/JourneyEntry', 'Public/Assessment'], true);
@endphp
<!DOCTYPE html>
<html lang="{{ $rafiqLocale->value }}" dir="{{ $rafiqLocale->direction() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="referrer" content="no-referrer">
        <title>{{ config('app.name', 'Rafiq') }}</title>
        <meta name="description" content="{{ __('meta.description') }}">

        @if ($rafiqNoIndex)
            <meta name="robots" content="noindex">
        @else
            <link rel="canonical" href="{{ $rafiqUrls[$rafiqLocale->value] }}">
            @foreach ($rafiqUrls as $code => $url)
                <link rel="alternate" hreflang="{{ $code }}" href="{{ $url }}">
            @endforeach
            <link rel="alternate" hreflang="x-default" href="{{ $rafiqUrls[\App\Enums\Locale::default()->value] }}">
        @endif

        <meta property="og:site_name" content="{{ __('app.name') }}">
        <meta property="og:locale" content="{{ $rafiqLocale === \App\Enums\Locale::Arabic ? 'ar_AR' : 'en_US' }}">
        <meta property="og:description" content="{{ __('meta.description') }}">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
        @inertiaHead
    </head>
    <body class="min-h-screen bg-surface text-ink antialiased">
        <noscript>
            <p class="p-4">{{ __('noscript.message') }}</p>
        </noscript>
        @inertia
    </body>
</html>
