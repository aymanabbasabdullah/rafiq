@php
    $rafiqLocale = \App\Enums\Locale::tryFrom(app()->getLocale()) ?? \App\Enums\Locale::default();
@endphp
<!DOCTYPE html>
<html lang="{{ $rafiqLocale->value }}" dir="{{ $rafiqLocale->direction() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="referrer" content="no-referrer">
        <title>{{ config('app.name', 'Rafiq') }}</title>

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
