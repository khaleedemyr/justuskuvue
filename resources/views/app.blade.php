<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Justus Group') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('logobulathitam.png') }}">

        @php
            $isAuthArea = request()->is(
                'login', 'register', 'dashboard', 'profile', 'verify-email',
                'confirm-password', 'forgot-password', 'reset-password', 'password/*'
            );
        @endphp

        @if (!empty($lcpPreconnectHost))
            <link rel="preconnect" href="{{ $lcpPreconnectHost }}" crossorigin>
        @endif
        @if (!empty($lcpPreloadUrl))
            <link rel="preload" as="image" href="{{ $lcpPreloadUrl }}" fetchpriority="high" type="image/webp">
            <style>
                #lcp-shell{position:fixed;inset:0;z-index:0;background:#000;pointer-events:none}
                #lcp-shell img{width:100%;height:100%;object-fit:cover;object-position:center}
                #lcp-shell::after{content:'';position:absolute;inset:0;background:rgba(0,0,0,.5)}
            </style>
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link
            rel="preload"
            as="style"
            href="https://fonts.bunny.net/css?family=montserrat:300,400,500,600&display=swap"
            onload="this.onload=null;this.rel='stylesheet'"
        >
        <noscript>
            <link href="https://fonts.bunny.net/css?family=montserrat:300,400,500,600&display=swap" rel="stylesheet">
        </noscript>
        @if ($isAuthArea)
            <link
                rel="preload"
                as="style"
                href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap"
                onload="this.onload=null;this.rel='stylesheet'"
            >
            <noscript>
                <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet">
            </noscript>
        @endif

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @if (!empty($lcpHeroUrl))
            <div id="lcp-shell" aria-hidden="true">
                <img
                    src="{{ $lcpHeroUrl }}"
                    alt=""
                    width="768"
                    height="1024"
                    fetchpriority="high"
                    decoding="async"
                >
            </div>
        @endif
        @inertia
    </body>
</html>
