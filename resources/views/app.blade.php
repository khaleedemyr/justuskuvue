<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Justus Group') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('logobulathitam.png') }}">

        @php
            $isSiteHome = !empty($isSiteHome);
            $isAuthArea = request()->is(
                'login', 'register', 'dashboard', 'profile', 'verify-email',
                'confirm-password', 'forgot-password', 'reset-password', 'password/*'
            );
        @endphp

        @if (!empty($lcpPreloadUrl))
            <link rel="preload" as="image" href="{{ $lcpPreloadUrl }}" fetchpriority="high" type="image/webp">
            <style>
                #lcp-shell{position:fixed;inset:0;z-index:0;background:#000;pointer-events:none;font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
                #lcp-shell img{width:100%;height:100%;object-fit:cover;object-position:center}
                #lcp-shell::after{content:'';position:absolute;inset:0;background:rgba(0,0,0,.5)}
                #lcp-shell .lcp-copy{position:absolute;inset:0;z-index:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:1.5rem;text-align:center;color:#fff}
                #lcp-shell .lcp-title{margin:0;font-size:clamp(.8125rem,4.1vw,1.125rem);font-weight:400;letter-spacing:.035em;line-height:1.2;text-transform:uppercase;white-space:nowrap}
                #lcp-shell .lcp-sub{margin:.5rem 0 0;font-size:clamp(.6875rem,3.45vw,.875rem);font-weight:400;font-style:italic;line-height:1.25;opacity:.9;white-space:nowrap}
            </style>
        @endif

        @unless ($isSiteHome)
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
        @endunless
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

        @unless(request()->routeIs('site.*'))
            @routes
        @endunless
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @if (!empty($lcpHeroUrl))
            <div id="lcp-shell" aria-hidden="true">
                <img
                    src="{{ $lcpHeroUrl }}"
                    alt=""
                    width="640"
                    height="853"
                    fetchpriority="high"
                    decoding="sync"
                    loading="eager"
                >
                @if (!empty($lcpTitle) || !empty($lcpSubtitle))
                    <div class="lcp-copy">
                        @if (!empty($lcpTitle))
                            <p class="lcp-title">{{ $lcpTitle }}</p>
                        @endif
                        @if (!empty($lcpSubtitle))
                            <p class="lcp-sub">{{ $lcpSubtitle }}</p>
                        @endif
                    </div>
                @endif
            </div>
        @endif
        @if ($isSiteHome)
            <script>
                window.addEventListener('load', function () {
                    var link = document.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = 'https://fonts.bunny.net/css?family=montserrat:300,400,500,600&display=swap';
                    document.head.appendChild(link);
                }, { once: true });
            </script>
        @endif
        @inertia
    </body>
</html>
