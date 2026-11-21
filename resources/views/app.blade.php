<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        @php
            $currentSetting = \App\Models\ElectionSetting::current();
            $faviconUrl = $currentSetting->logo_url ?? asset('images/logo-default.png');
        @endphp

        <!-- Favicon Dinamis Mengikuti Logo Yang Diunggah -->
        <link rel="icon" id="dynamic-favicon" href="{{ $faviconUrl }}">
        <link rel="apple-touch-icon" id="dynamic-apple-icon" href="{{ $faviconUrl }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
